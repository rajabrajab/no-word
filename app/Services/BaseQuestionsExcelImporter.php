<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Worksheet\BaseDrawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\MemoryDrawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Shared plumbing for the question sheets: reading cells and lifting the pictures
 * an admin pasted into the workbook onto the public disk.
 */
abstract class BaseQuestionsExcelImporter
{
    public int $created = 0;

    public int $skipped = 0;

    /**
     * @var list<array{row: int, errors: list<string>}>
     */
    public array $errors = [];

    /**
     * Group each row's embedded pictures into the question slot and the answer slot.
     *
     * Drawings anchored before the answer column belong to the question; anything at
     * or after it belongs to the answer. Sheets from an older template have no answer
     * column, so every picture on the row stays with the question.
     *
     * @param  int|null  $answerMediaColumn  1-based index of the answer picture column, or null when the sheet has none.
     * @return array<int, array{media?: string, answer_media?: string}>
     */
    protected function mapDrawingsByRow(Worksheet $sheet, ?int $answerMediaColumn): array
    {
        $candidates = [];

        foreach ($sheet->getDrawingCollection() as $drawing) {
            if (! $drawing instanceof BaseDrawing) {
                continue;
            }

            try {
                [$colIndex, $row] = Coordinate::indexesFromString($drawing->getCoordinates());
            } catch (\Throwable) {
                continue;
            }

            if ($row <= 1) {
                continue;
            }

            $slot = $answerMediaColumn !== null && $colIndex >= $answerMediaColumn ? 'answer_media' : 'media';
            $candidates[$row][$slot][] = ['col' => $colIndex, 'drawing' => $drawing];
        }

        $map = [];
        foreach ($candidates as $row => $slots) {
            foreach ($slots as $slot => $items) {
                usort($items, fn (array $a, array $b): int => $b['col'] <=> $a['col']);
                foreach ($items as $item) {
                    $path = $this->persistDrawing($item['drawing'], $slot === 'answer_media' ? 'answers' : 'questions');
                    if ($path !== null) {
                        $map[$row][$slot] = $path;
                        break;
                    }
                }
            }
        }

        return $map;
    }

    protected function persistDrawing(BaseDrawing $drawing, string $dir = 'questions'): ?string
    {
        $base = Str::uuid()->toString();

        if ($drawing instanceof MemoryDrawing) {
            $resource = $drawing->getImageResource();
            if (! $resource) {
                return null;
            }

            $ext = match ($drawing->getMimeType()) {
                MemoryDrawing::MIMETYPE_JPEG => 'jpg',
                MemoryDrawing::MIMETYPE_GIF => 'gif',
                default => 'png',
            };

            $relative = "{$dir}/{$base}.{$ext}";
            $fullPath = Storage::disk('public')->path($relative);
            if (! is_dir(dirname($fullPath))) {
                mkdir(dirname($fullPath), 0755, true);
            }

            $fn = $drawing->getRenderingFunction();
            if ($fn === MemoryDrawing::RENDERING_JPEG) {
                \call_user_func($fn, $resource, $fullPath, 90);
            } else {
                \call_user_func($fn, $resource, $fullPath);
            }

            return $relative;
        }

        if ($drawing instanceof Drawing) {
            $binary = $this->readDrawingBinary($drawing);
            if ($binary === null || $binary === '') {
                return null;
            }

            $ext = strtolower($drawing->getExtension() ?: '');
            if ($ext === '' || ! preg_match('/^[a-z0-9]{2,5}$/', $ext)) {
                $ext = $this->guessImageExtensionFromBinary($binary);
            }

            $relative = "{$dir}/{$base}.{$ext}";
            Storage::disk('public')->put($relative, $binary);

            return $relative;
        }

        return null;
    }

    protected function readDrawingBinary(Drawing $drawing): ?string
    {
        $src = $drawing->getPath();
        if ($src === '') {
            return null;
        }

        if (str_starts_with($src, 'data:image') && str_contains($src, ';base64,')) {
            $comma = strpos($src, ',');
            if ($comma === false) {
                return null;
            }
            $decoded = base64_decode(substr($src, $comma + 1), true);

            return $decoded !== false ? $decoded : null;
        }

        $binary = @file_get_contents($src);

        return $binary !== false ? $binary : null;
    }

    protected function guessImageExtensionFromBinary(string $binary): string
    {
        if (function_exists('finfo_open')) {
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->buffer($binary) ?: '';

            return match ($mime) {
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/gif' => 'gif',
                'image/webp' => 'webp',
                default => 'png',
            };
        }

        return 'png';
    }

    protected function stringCell(Worksheet $sheet, int $columnIndex1Based, int $row): string
    {
        $coord = Coordinate::stringFromColumnIndex($columnIndex1Based).$row;
        $value = $sheet->getCell($coord)->getValue();

        return is_string($value) ? trim($value) : trim((string) $value);
    }

    /**
     * Match a sheet's header row against the template, in the machine names the old
     * sheets used and in every locale's labels.
     *
     * @param  list<mixed>  $headerRow
     * @param  list<string>  $machineHeaders
     * @param  list<string>  $translationKeys
     */
    protected function headerMatches(array $headerRow, array $machineHeaders, array $translationKeys, int $width): bool
    {
        $slice = array_values(array_slice($headerRow, 0, $width));

        if ($slice === array_slice($machineHeaders, 0, $width)) {
            return true;
        }

        foreach (array_unique([app()->getLocale(), 'en', 'ar']) as $locale) {
            $labels = array_map(fn (string $key): string => trans($key, [], $locale), $translationKeys);

            if ($slice === array_slice($labels, 0, $width)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Read the sheet's header row as a flat list of trimmed values.
     *
     * @return list<mixed>
     */
    protected function readHeaderRow(Worksheet $sheet): array
    {
        $headerRow = [];

        foreach ($sheet->getRowIterator(1, 1) as $row) {
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);
            foreach ($cellIterator as $cell) {
                $headerRow[] = is_string($cell->getValue()) ? trim($cell->getValue()) : $cell->getValue();
            }
            break;
        }

        return $headerRow;
    }
}
