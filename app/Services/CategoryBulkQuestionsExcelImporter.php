<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Question;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;

class CategoryBulkQuestionsExcelImporter extends BaseQuestionsExcelImporter
{
    /**
     * 1-based index of the "picture for this question" column; the answer picture sits to its right.
     */
    private const ANSWER_MEDIA_COLUMN = 6;

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

        $hasAnswerMediaColumn = $this->detectAnswerMediaColumn($this->readHeaderRow($sheet));

        if ($hasAnswerMediaColumn === null) {
            $this->errors[] = [
                'row' => 1,
                'errors' => [__('panel.bulk_excel_invalid_header')],
            ];

            return;
        }

        $imagesByRow = $this->mapDrawingsByRow($sheet, $hasAnswerMediaColumn ? self::ANSWER_MEDIA_COLUMN : null);

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

        $translationKeys = [
            'panel.excel_question',
            'panel.excel_answer',
            'panel.excel_hint',
            'panel.excel_score',
            'panel.bulk_excel_media_column',
            'panel.bulk_excel_answer_media_column',
        ];

        foreach ([6 => true, 5 => false] as $width => $hasAnswerMediaColumn) {
            if ($this->headerMatches($headerRow, $machineHeaders, $translationKeys, $width)) {
                return $hasAnswerMediaColumn;
            }
        }

        return null;
    }
}
