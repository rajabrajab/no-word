<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Question;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Imports the questions sheet from the Questions list.
 *
 * Two sheets are accepted: the blank template, where every row is a new question,
 * and the sheet produced by "Export questions to Excel", which carries each
 * question's id. A row that keeps its id edits that question in place, bringing it
 * back when an admin has since deleted it; a row with no id — the template, or a
 * line typed under the exported ones — adds a new one, as does a row whose id names
 * no question at all. Nothing is ever deleted, and a live question is only written
 * when the row actually changes it.
 */
class QuestionsExcelImporter extends BaseQuestionsExcelImporter
{
    /**
     * 1-based column indexes of the blank template.
     */
    private const LAYOUT_TEMPLATE = [
        'id' => null,
        'category_id' => null,
        'category_name' => 1,
        'question' => 2,
        'answer' => 3,
        'hint' => 4,
        'score' => 5,
        'media' => 6,
        'answer_media' => 7,
    ];

    /**
     * 1-based column indexes of the exported sheet, which leads with the id.
     */
    private const LAYOUT_EXPORT = [
        'id' => 1,
        'category_id' => 2,
        'category_name' => 3,
        'question' => 4,
        'answer' => 5,
        'hint' => 6,
        'score' => 7,
        'media' => 8,
        'answer_media' => 9,
    ];

    /**
     * Questions edited in place.
     */
    public int $updated = 0;

    /**
     * Rows that named an existing question but asked for no change.
     */
    public int $unchanged = 0;

    /**
     * @var array<int, true>
     */
    private array $categoryIds = [];

    /**
     * @var array<string, list<int>>
     */
    private array $categoryIdsByName = [];

    /**
     * @var array<string, int|null>
     */
    private array $layout = self::LAYOUT_TEMPLATE;

    public function __construct(protected QrCodeService $qrCodeService) {}

    public function import(string $absolutePath): void
    {
        $this->created = 0;
        $this->updated = 0;
        $this->unchanged = 0;
        $this->skipped = 0;
        $this->errors = [];

        $spreadsheet = IOFactory::load($absolutePath);
        $sheet = $spreadsheet->getActiveSheet();

        $layout = $this->detectLayout($this->readHeaderRow($sheet));

        if ($layout === null) {
            $this->errors[] = [
                'row' => 1,
                'errors' => [__('panel.bulk_excel_invalid_header')],
            ];

            return;
        }

        $this->layout = $layout;
        $this->loadCategories();

        $imagesByRow = $this->mapDrawingsByRow($sheet, $layout['answer_media']);

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
        $id = $this->cell($sheet, 'id', $excelRow);
        $categoryId = $this->cell($sheet, 'category_id', $excelRow);
        $categoryName = $this->cell($sheet, 'category_name', $excelRow);
        $question = $this->cell($sheet, 'question', $excelRow);
        $answer = $this->cell($sheet, 'answer', $excelRow);
        $hint = $this->cell($sheet, 'hint', $excelRow);
        $score = $this->scoreCell($sheet, $excelRow);

        $hasRowImages = $rowImages !== [];

        $isBlankRow = $id === '' && $categoryId === '' && $categoryName === '' && $question === ''
            && $answer === '' && $hint === '' && $score === null && ! $hasRowImages
            && $this->cell($sheet, 'media', $excelRow) === ''
            && $this->cell($sheet, 'answer_media', $excelRow) === '';

        if ($isBlankRow) {
            $this->skipped++;

            return;
        }

        $existing = $id === '' ? null : $this->findQuestion($id, $excelRow);

        if ($existing === false) {
            return;
        }

        $media = $this->rowMedia($sheet, $excelRow, 'media', $rowImages, $existing);
        $answerMedia = $this->rowMedia($sheet, $excelRow, 'answer_media', $rowImages, $existing);

        $resolvedCategoryId = $this->resolveCategoryId($categoryId, $categoryName);

        if ($resolvedCategoryId === false) {
            $this->errors[] = [
                'row' => $excelRow,
                'errors' => [__('panel.excel_category_mismatch', ['id' => $categoryId, 'name' => $categoryName])],
            ];
            $this->skipped++;

            return;
        }

        // An edit that names no category keeps the one the question already has.
        $resolvedCategoryId ??= $existing?->category_id;

        if ($resolvedCategoryId === null) {
            $shared = $this->categoryCandidates($categoryName);

            $this->errors[] = [
                'row' => $excelRow,
                'errors' => [count($shared) > 1
                    ? __('panel.excel_ambiguous_category', [
                        'category' => $categoryName,
                        'count' => count($shared),
                    ])
                    : __('panel.excel_unknown_category', ['category' => $categoryName !== '' ? $categoryName : $categoryId]),
                ],
            ];
            $this->skipped++;

            return;
        }

        $data = [
            'category_id' => $resolvedCategoryId,
            'question' => $question,
            'answer' => $answer,
            'hint' => $hint !== '' ? $hint : null,
            'score' => $score,
        ];

        // Media is left alone unless the row carries one: an exported picture that an
        // admin never touched must not blank the column it came from.
        if ($media !== null) {
            $data['media'] = $media;
            $data['media_type'] = MediaTypeResolver::resolve($media) ?? MediaTypeResolver::IMAGE;
        }

        if ($answerMedia !== null) {
            $data['answer_media'] = $answerMedia;
            $data['answer_media_type'] = MediaTypeResolver::resolve($answerMedia) ?? MediaTypeResolver::IMAGE;
        }

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

        $validated = $validator->validated();

        if ($existing !== null && $existing->trashed()) {
            $this->restoreQuestion($existing, $validated);

            return;
        }

        if ($existing !== null) {
            $this->updateQuestion($existing, $validated);

            return;
        }

        $created = Question::query()->create($validated);
        $created->update([
            'qr_code' => $this->qrCodeService->generateForQuestion($created),
        ]);
        $this->created++;
    }

    /**
     * Bring back the question an id names after an admin deleted it, carrying the
     * row's edits in with it.
     *
     * Restoring in place rather than adding a copy keeps the id the sheet is built
     * around, the qr token already printed on the cards, and the games the question
     * belongs to. It counts as a creation because the question was gone from the
     * panel before the import, and it is written even when no field changed — the
     * row's point was to undo the deletion.
     *
     * @param  array<string, mixed>  $validated
     */
    private function restoreQuestion(Question $question, array $validated): void
    {
        $question->fill($validated);
        $question->restore();

        // A question deleted before it was ever drawn a code comes back without one,
        // and every other path that makes a question guarantees one.
        if (blank($question->qr_code)) {
            $question->update([
                'qr_code' => $this->qrCodeService->generateForQuestion($question),
            ]);
        }

        $this->created++;
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function updateQuestion(Question $question, array $validated): void
    {
        // Compared as text so that 200 and '200', or null and '', do not read as an
        // edit and rewrite every row of a round-tripped export.
        $changes = array_filter(
            $validated,
            fn (mixed $value, string $key): bool => (string) $question->{$key} !== (string) $value,
            ARRAY_FILTER_USE_BOTH,
        );

        if ($changes === []) {
            $this->unchanged++;

            return;
        }

        $question->update($changes);
        $this->updated++;
    }

    /**
     * Look up the question a row claims to edit.
     *
     * The recycle bin is searched too: an admin who deletes a question and uploads a
     * sheet still carrying its id means to bring it back, so a deleted question is
     * handed over to be restored rather than refused.
     *
     * An id matching nothing at all is not an error either — the row is simply added
     * as a new question, under a fresh id. Forcing the sheet's id onto it would leave
     * the table's auto-increment behind and collide with the next insert.
     *
     * @return Question|false|null The question, false when the cell is not an id at all, null when no question has it.
     */
    private function findQuestion(string $id, int $excelRow): Question|false|null
    {
        if (! ctype_digit($id)) {
            $this->errors[] = [
                'row' => $excelRow,
                'errors' => [__('panel.excel_invalid_id', ['id' => $id])],
            ];
            $this->skipped++;

            return false;
        }

        return Question::withTrashed()->find((int) $id);
    }

    /**
     * The media a row supplies, or null when it supplies none.
     *
     * A pasted picture wins. Failing that, the exported sheet writes the stored path
     * as text for files it cannot draw — audio, video, PDFs — and that path is taken
     * only when it still names a file, so a typo cannot point a question at nothing.
     *
     * @param  array{media?: string, answer_media?: string}  $rowImages
     */
    private function rowMedia(Worksheet $sheet, int $excelRow, string $slot, array $rowImages, ?Question $existing): ?string
    {
        $current = $existing?->{$slot};

        if (isset($rowImages[$slot])) {
            $incoming = $rowImages[$slot];

            // An export draws the stored picture onto the sheet, so re-uploading one
            // untouched hands the same bytes back. Keep the file already on disk
            // rather than piling up a copy on every round trip.
            if (filled($current) && $this->isSameFile($incoming, $current)) {
                Storage::disk('public')->delete($incoming);

                return $current;
            }

            return $incoming;
        }

        $text = $this->cell($sheet, $slot, $excelRow);

        if ($text === '') {
            return null;
        }

        return Storage::disk('public')->exists($text) ? $text : null;
    }

    private function isSameFile(string $a, string $b): bool
    {
        $disk = Storage::disk('public');

        if (! $disk->exists($a) || ! $disk->exists($b)) {
            return false;
        }

        return md5_file($disk->path($a)) === md5_file($disk->path($b));
    }

    /**
     * Accept either the category's id or its name, so an admin can type what they
     * see in the panel rather than look up a number.
     *
     * The exported sheet carries both. Editing one and leaving the other stale would
     * otherwise move the question wherever the losing column happened to point, so a
     * disagreement is refused rather than guessed at.
     *
     * @return int|false|null The category, false when the two columns disagree, null when neither names one.
     */
    private function resolveCategoryId(string $id, string $name): int|false|null
    {
        $byId = $this->categoryIdFromValue($id);
        $byName = $this->categoryIdFromValue($name);

        if ($byId !== null && $byName !== null && $byId !== $byName) {
            return false;
        }

        return $byName ?? $byId;
    }

    /**
     * One cell's category, by id or by name; null when it names none or is ambiguous.
     */
    private function categoryIdFromValue(string $value): ?int
    {
        $candidates = $this->categoryCandidates($value);

        // An ambiguous name is no better than a missing one: make them use the id.
        return count($candidates) === 1 ? $candidates[0] : null;
    }

    /**
     * Every category one cell could mean — none, exactly one, or several sharing a name.
     *
     * @return list<int>
     */
    private function categoryCandidates(string $value): array
    {
        if ($value === '') {
            return [];
        }

        if (ctype_digit($value)) {
            return isset($this->categoryIds[(int) $value]) ? [(int) $value] : [];
        }

        return $this->categoryIdsByName[mb_strtolower($value)] ?? [];
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
     * Read one of the layout's columns, or '' when this sheet does not have it.
     */
    private function cell(Worksheet $sheet, string $column, int $excelRow): string
    {
        $index = $this->layout[$column] ?? null;

        return $index === null ? '' : $this->stringCell($sheet, $index, $excelRow);
    }

    private function scoreCell(Worksheet $sheet, int $excelRow): int|string|null
    {
        $index = $this->layout['score'];

        if ($index === null) {
            return null;
        }

        $raw = $sheet->getCell(Coordinate::stringFromColumnIndex($index).$excelRow)->getValue();

        if ($raw === null || $raw === '') {
            return null;
        }

        return is_numeric($raw) ? (int) $raw : (string) $raw;
    }

    /**
     * @param  list<mixed>  $headerRow
     * @return array<string, int|null>|null
     */
    private function detectLayout(array $headerRow): ?array
    {
        $exportHeaders = ['id', 'category_id', 'category_name', 'question', 'answer', 'hint', 'score', 'media', 'answer_media'];
        $exportKeys = [
            'panel.excel_id',
            'panel.excel_category_id',
            'panel.excel_category_name',
            'panel.excel_question',
            'panel.excel_answer',
            'panel.excel_hint',
            'panel.excel_score',
            'panel.bulk_excel_media_column',
            'panel.bulk_excel_answer_media_column',
        ];

        if ($this->headerMatches($headerRow, $exportHeaders, $exportKeys, 9)) {
            return self::LAYOUT_EXPORT;
        }

        $templateHeaders = ['category_id', 'question', 'answer', 'hint', 'score', 'media', 'answer_media'];
        $templateKeys = [
            'panel.excel_category',
            'panel.excel_question',
            'panel.excel_answer',
            'panel.excel_hint',
            'panel.excel_score',
            'panel.bulk_excel_media_column',
            'panel.bulk_excel_answer_media_column',
        ];

        if ($this->headerMatches($headerRow, $templateHeaders, $templateKeys, 7)) {
            return self::LAYOUT_TEMPLATE;
        }

        return null;
    }
}
