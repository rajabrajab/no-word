<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Question;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\BaseDrawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\MemoryDrawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CategoryBulkQuestionsExcelImporter
{
    public int $created = 0;

    public int $skipped = 0;

    public array $errors = [];

    public function __construct(
        protected int $categoryId,
        protected QrCodeService $qrCodeService,
    ) {}

    public function import(string $absolutePath): void
    {
        $this->created = 0;
        $this->skipped = 0;
        $this->errors = [];

        if (! Category::query()->whereKey($this->categoryId)->exists()) {
            $this->errors[] = [
                'row' => 0,
                'errors' => [__('panel.bulk_excel_invalid_category')],
            ];

            return;
        }

        $spreadsheet = IOFactory::load($absolutePath);
        $sheet = $spreadsheet->getActiveSheet();

        $headerRow = [];
        foreach ($sheet->getRowIterator(1, 1) as $row) {
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);
            foreach ($cellIterator as $cell) {
                $headerRow[] = is_string($cell->getValue()) ? trim($cell->getValue()) : $cell->getValue();
            }
            break;
        }

        $machineHeaders = ['question', 'answer', 'hint', 'score', 'media'];
        $headerSlice = array_values(array_slice($headerRow, 0, 5));

        $headerOk = $headerSlice === $machineHeaders;
        if (! $headerOk) {
            foreach (array_unique([app()->getLocale(), 'en', 'ar']) as $locale) {
                $expected = [
                    trans('panel.excel_question', [], $locale),
                    trans('panel.excel_answer', [], $locale),
                    trans('panel.excel_hint', [], $locale),
                    trans('panel.excel_score', [], $locale),
                    trans('panel.bulk_excel_media_column', [], $locale),
                ];
                if ($headerSlice === $expected) {
                    $headerOk = true;
                    break;
                }
            }
        }

        if (! $headerOk) {
            $this->errors[] = [
                'row' => 1,
                'errors' => [__('panel.bulk_excel_invalid_header')],
            ];

            return;
        }

        $imagesByRow = $this->mapDrawingsByRow($sheet);

        $highestRow = (int) $sheet->getHighestDataRow();
        $highestRow = max($highestRow, 2);

        DB::transaction(function () use ($sheet, $highestRow, $imagesByRow): void {
            for ($excelRow = 2; $excelRow <= $highestRow; $excelRow++) {
                $question = $this->stringCell($sheet, 1, $excelRow);
                $answer = $this->stringCell($sheet, 2, $excelRow);
                $hint = $this->stringCell($sheet, 3, $excelRow);
                $scoreRaw = $sheet->getCell(Coordinate::stringFromColumnIndex(4).$excelRow)->getValue();
                $score = null;
                if ($scoreRaw !== null && $scoreRaw !== '') {
                    $score = is_numeric($scoreRaw) ? (int) $scoreRaw : (string) $scoreRaw;
                }

                if ($question === '' && $answer === '' && ($hint === '' || $hint === null) && ($score === null || $score === '') && ! isset($imagesByRow[$excelRow])) {
                    $this->skipped++;

                    continue;
                }

                $mediaPath = $imagesByRow[$excelRow] ?? null;

                $data = [
                    'category_id' => $this->categoryId,
                    'question' => $question,
                    'answer' => $answer,
                    'hint' => $hint !== '' ? $hint : null,
                    'score' => $score,
                    'media' => $mediaPath,
                    'media_type' => $mediaPath ? 'image' : null,
                ];

                $validator = Validator::make($data, [
                    'category_id' => ['required', 'integer', 'exists:categories,id'],
                    'question' => ['required', 'string'],
                    'answer' => ['required', 'string'],
                    'hint' => ['nullable', 'string'],
                    'score' => ['nullable', 'integer'],
                    'media' => ['nullable', 'string'],
                    'media_type' => ['nullable', 'string'],
                ]);

                if ($validator->fails()) {
                    $this->errors[] = [
                        'row' => $excelRow,
                        'errors' => $validator->errors()->all(),
                    ];
                    $this->skipped++;

                    continue;
                }

                $validated = $validator->validated();
                $q = Question::query()->create($validated);
                $q->update([
                    'qr_code' => $this->qrCodeService->generateForQuestion($q),
                ]);
                $this->created++;
            }
        });
    }

    protected function mapDrawingsByRow(Worksheet $sheet): array
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

            $candidates[$row][] = ['col' => $colIndex, 'drawing' => $drawing];
        }

        $map = [];
        foreach ($candidates as $row => $items) {
            usort($items, fn (array $a, array $b): int => $b['col'] <=> $a['col']);
            foreach ($items as $item) {
                $path = $this->persistDrawing($item['drawing']);
                if ($path !== null) {
                    $map[$row] = $path;
                    break;
                }
            }
        }

        return $map;
    }

    protected function persistDrawing(BaseDrawing $drawing): ?string
    {
        $dir = 'questions';
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
}
