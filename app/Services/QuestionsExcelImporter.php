<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Question;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Imports the questions sheet downloaded from the Questions list, where each row
 * names its own category instead of inheriting one from the page.
 */
class QuestionsExcelImporter extends BaseQuestionsExcelImporter
{
    /**
     * 1-based column indexes of the template downloaded from the Questions list.
     */
    private const COLUMN_CATEGORY = 1;

    private const COLUMN_QUESTION = 2;

    private const COLUMN_ANSWER = 3;

    private const COLUMN_HINT = 4;

    private const COLUMN_SCORE = 5;

    private const COLUMN_ANSWER_MEDIA = 7;

    /**
     * Categories keyed by id, plus a lowercased-name index, loaded once per import
     * so naming a category per row stays a single query.
     *
     * @var array<int, true>
     */
    private array $categoryIds = [];

    /**
     * @var array<string, list<int>>
     */
    private array $categoryIdsByName = [];

    public function __construct(protected QrCodeService $qrCodeService) {}

    public function import(string $absolutePath): void
    {
        $this->created = 0;
        $this->skipped = 0;
        $this->errors = [];

        $spreadsheet = IOFactory::load($absolutePath);
        $sheet = $spreadsheet->getActiveSheet();

        if (! $this->headerIsValid($this->readHeaderRow($sheet))) {
            $this->errors[] = [
                'row' => 1,
                'errors' => [__('panel.bulk_excel_invalid_header')],
            ];

            return;
        }

        $this->loadCategories();

        $imagesByRow = $this->mapDrawingsByRow($sheet, self::COLUMN_ANSWER_MEDIA);

        $highestRow = max((int) $sheet->getHighestDataRow(), 2);

        DB::transaction(function () use ($sheet, $highestRow, $imagesByRow): void {
            for ($excelRow = 2; $excelRow <= $highestRow; $excelRow++) {
                $this->importRow($sheet, $excelRow, $imagesByRow[$excelRow] ?? []);
            }
        });
    }

    /**
     * @param  array{media?: string, answer_media?: string}  $rowImages
     */
    private function importRow(Worksheet $sheet, int $excelRow, array $rowImages): void
    {
        $categoryCell = $this->stringCell($sheet, self::COLUMN_CATEGORY, $excelRow);
        $question = $this->stringCell($sheet, self::COLUMN_QUESTION, $excelRow);
        $answer = $this->stringCell($sheet, self::COLUMN_ANSWER, $excelRow);
        $hint = $this->stringCell($sheet, self::COLUMN_HINT, $excelRow);

        $scoreRaw = $sheet->getCell(Coordinate::stringFromColumnIndex(self::COLUMN_SCORE).$excelRow)->getValue();
        $score = null;
        if ($scoreRaw !== null && $scoreRaw !== '') {
            $score = is_numeric($scoreRaw) ? (int) $scoreRaw : (string) $scoreRaw;
        }

        if ($categoryCell === '' && $question === '' && $answer === '' && $hint === '' && $score === null && $rowImages === []) {
            $this->skipped++;

            return;
        }

        $categoryId = $this->resolveCategoryId($categoryCell);

        if ($categoryId === null) {
            $this->errors[] = [
                'row' => $excelRow,
                'errors' => [__('panel.excel_unknown_category', ['category' => $categoryCell])],
            ];
            $this->skipped++;

            return;
        }

        $mediaPath = $rowImages['media'] ?? null;
        $answerMediaPath = $rowImages['answer_media'] ?? null;

        $data = [
            'category_id' => $categoryId,
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

            return;
        }

        $created = Question::query()->create($validator->validated());
        $created->update([
            'qr_code' => $this->qrCodeService->generateForQuestion($created),
        ]);
        $this->created++;
    }

    /**
     * Accept either the category's id or its name, so an admin can type what they
     * see in the panel rather than look up a number.
     */
    private function resolveCategoryId(string $value): ?int
    {
        if ($value === '') {
            return null;
        }

        if (ctype_digit($value)) {
            return isset($this->categoryIds[(int) $value]) ? (int) $value : null;
        }

        $matches = $this->categoryIdsByName[mb_strtolower($value)] ?? [];

        // An ambiguous name is no better than a missing one: make them use the id.
        return count($matches) === 1 ? $matches[0] : null;
    }

    private function loadCategories(): void
    {
        $this->categoryIds = [];
        $this->categoryIdsByName = [];

        Category::query()
            ->select(['id', 'name'])
            ->get()
            ->each(function (Category $category): void {
                $this->categoryIds[(int) $category->id] = true;
                $this->categoryIdsByName[mb_strtolower(trim((string) $category->name))][] = (int) $category->id;
            });
    }

    /**
     * @param  list<mixed>  $headerRow
     */
    private function headerIsValid(array $headerRow): bool
    {
        $machineHeaders = ['category_id', 'question', 'answer', 'hint', 'score', 'media', 'answer_media'];

        $translationKeys = [
            'panel.excel_category',
            'panel.excel_question',
            'panel.excel_answer',
            'panel.excel_hint',
            'panel.excel_score',
            'panel.bulk_excel_media_column',
            'panel.bulk_excel_answer_media_column',
        ];

        return $this->headerMatches($headerRow, $machineHeaders, $translationKeys, 7);
    }
}
