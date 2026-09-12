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
    /**
     * 1-based index of the "picture for this question" column; the answer picture sits to its right.
     */
    private const ANSWER_MEDIA_COLUMN = 6;

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

        $hasAnswerMediaColumn = $this->detectAnswerMediaColumn($headerRow);

        if ($hasAnswerMediaColumn === null) {
            $this->errors[] = [
                'row' => 1,
                'errors' => [__('panel.bulk_excel_invalid_header')],
            ];

            return;
        }

        $imagesByRow = $this->mapDrawingsByRow($sheet, $hasAnswerMediaColumn);

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

                $rowImages = $imagesByRow[$excelRow] ?? [];

                if ($question === '' && $answer === '' && ($hint === '' || $hint === null) && ($score === null || $score === '') && $rowImages === []) {
                    $this->skipped++;

                    continue;
                }

                $mediaPath = $rowImages['media'] ?? null;
                $answerMediaPath = $rowImages['answer_media'] ?? null;

                $data = [
                    'category_id' => $this->categoryId,
                    'question' => $question,
                    'answer' => $answer,
                    'hint' => $hint !== '' ? $hint : null,
                    'score' => $score,
                    'media' => $mediaPath,
                    'media_type' => $mediaPath ? 'image' : null,
                    'answer_media' => $answerMediaPath,
                    'answer_media_type' => $answerMediaPath ? 'image' : null,
                ];

                $validator = Validator::make($data, [
                    'category_id' => ['required', 'integer', 'exists:categories,id'],
                    'question' => ['required', 'string'],
                    'answer' => ['required', 'string'],
                    'hint' => ['nullable', 'string'],
                    'score' => ['nullable', 'integer'],
                    'media' => ['nullable', 'string'],
                    'media_type' => ['nullable', 'string'],
                    'answer_media' => ['nullable', 'string'],
                    'answer_media_type' => ['nullable', 'string'],
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

    /**
     * Validate the header row against the current template and the one that predates
     * the answer-picture column.
     *
     * @param  list<mixed>  $headerRow
     * @return bool|null True when the answer-picture column is present, false for the
     *                   legacy five-column sheet, null when the header is not recognised.
     */
    protected function detectAnswerMediaColumn(array $headerRow): ?bool
    {
        $machineHeaders = ['question', 'answer', 'hint', 'score', 'media', 'answer_media'];

        $localised = [];
        foreach (array_unique([app()->getLocale(), 'en', 'ar']) as $locale) {
            $localised[] = [
                trans('panel.excel_question', [], $locale),
                trans('panel.excel_answer', [], $locale),
                trans('panel.excel_hint', [], $locale),
                trans('panel.excel_score', [], $locale),
                trans('panel.bulk_excel_media_column', [], $locale),
                trans('panel.bulk_excel_answer_media_column', [], $locale),
            ];
        }

        foreach ([6 => true, 5 => false] as $width => $hasAnswerMediaColumn) {
            $slice = array_values(array_slice($headerRow, 0, $width));

            if ($slice === array_slice($machineHeaders, 0, $width)) {
                return $hasAnswerMediaColumn;
            }

            foreach ($localised as $expected) {
                if ($slice === array_slice($expected, 0, $width)) {
                    return $hasAnswerMediaColumn;
                }
            }
        }

        return null;
    }

    /**
     * Group each row's embedded pictures into the question slot and the answer slot.
     *
     * Drawings anchored at or before the media column (E) belong to the question;
     * anything to its right belongs to the answer. Sheets from the older template have
     * no answer column, so every picture on the row stays with the question.
     *
     * @return array<int, array{media?: string, answer_media?: string}>
     */
    protected function mapDrawingsByRow(Worksheet $sheet, bool $hasAnswerMediaColumn): array
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

            $slot = $hasAnswerMediaColumn && $colIndex >= self::ANSWER_MEDIA_COLUMN ? 'answer_media' : 'media';
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
}
