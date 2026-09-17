<?php

namespace Tests\Feature;

use App\Exports\QuestionsExport;
use App\Exports\QuestionsTemplateCategoriesSheet;
use App\Exports\QuestionsTemplateExport;
use App\Exports\QuestionsTemplateSheet;
use App\Models\Category;
use App\Models\Country;
use App\Models\Question;
use App\Services\QrCodeService;
use App\Services\QuestionsExcelImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class QuestionsExcelImportTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @var list<string>
     */
    private array $workbooks = [];

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    protected function tearDown(): void
    {
        foreach ($this->workbooks as $path) {
            @unlink($path);
        }

        parent::tearDown();
    }

    public function test_it_imports_a_row_that_names_its_category(): void
    {
        $category = $this->makeCategory('Geography');

        $this->import($this->buildWorkbook([
            ['Geography', 'Capital of France?', 'Paris', 'City of light', 200],
        ]));

        $question = Question::query()->firstOrFail();
        $this->assertSame($category->id, $question->category_id);
        $this->assertSame('Capital of France?', $question->question);
        $this->assertSame('Paris', $question->answer);
        $this->assertSame('City of light', $question->hint);
        $this->assertSame(200, $question->score);
        $this->assertNotNull($question->qr_code);
    }

    public function test_it_accepts_the_category_id_in_place_of_the_name(): void
    {
        $category = $this->makeCategory('Geography');

        $importer = $this->import($this->buildWorkbook([
            [(string) $category->id, 'Q1', 'A1', null, 400],
        ]));

        $this->assertSame(1, $importer->created);
        $this->assertSame($category->id, Question::query()->firstOrFail()->category_id);
    }

    public function test_it_matches_the_category_name_regardless_of_case(): void
    {
        $category = $this->makeCategory('Geography');

        $this->import($this->buildWorkbook([
            ['  geOGRAPhy ', 'Q1', 'A1', null, 600],
        ]));

        $this->assertSame($category->id, Question::query()->firstOrFail()->category_id);
    }

    public function test_it_splits_question_and_answer_pictures_by_column(): void
    {
        $this->makeCategory('Geography');

        $importer = $this->import($this->buildWorkbook(
            [['Geography', 'Q1', 'A1', 'H1', 200]],
            questionImage: true,
            answerImage: true,
        ));

        $this->assertSame([], $importer->errors);

        $question = Question::query()->firstOrFail();
        $this->assertStringStartsWith('questions/', $question->media);
        $this->assertSame('image', $question->media_type);
        $this->assertStringStartsWith('answers/', $question->answer_media);
        $this->assertSame('image', $question->answer_media_type);
        $this->assertNotSame($question->media, $question->answer_media);
    }

    public function test_it_reports_a_row_whose_category_is_unknown(): void
    {
        $this->makeCategory('Geography');

        $importer = $this->import($this->buildWorkbook([
            ['History', 'Q1', 'A1', null, 200],
        ]));

        $this->assertSame(0, $importer->created);
        $this->assertSame(1, $importer->skipped);
        // Errors are reported against the row number the admin sees in Excel.
        $this->assertSame(2, $importer->errors[0]['row'] ?? null);
        $this->assertStringContainsString('History', $importer->errors[0]['errors'][0] ?? '');
        $this->assertSame(0, Question::query()->count());
    }

    public function test_it_refuses_a_name_shared_by_two_categories_and_says_why(): void
    {
        $this->makeCategory('Geography');
        $this->makeCategory('Geography');

        $importer = $this->import($this->buildWorkbook([
            ['Geography', 'Q1', 'A1', null, 200],
        ]));

        $this->assertSame(0, $importer->created);
        // Every category name in this app is reused per country, so the message has to
        // point at the ID rather than claim the category does not exist.
        $this->assertSame(
            __('panel.excel_ambiguous_category', ['category' => 'Geography', 'count' => 2]),
            $importer->errors[0]['errors'][0] ?? null,
        );
    }

    public function test_the_category_reference_sheet_leads_with_the_id(): void
    {
        $category = $this->makeCategory('Geography');

        $sheet = new QuestionsTemplateCategoriesSheet;

        $this->assertSame([
            __('panel.excel_category_id'),
            __('panel.country'),
            __('panel.excel_category_name'),
        ], $sheet->headings());

        $this->assertSame([$category->id, 'Saudi Arabia', 'Geography'], $sheet->map($sheet->collection()->first()));
    }

    public function test_it_skips_a_blank_row_between_questions_without_reporting_an_error(): void
    {
        $this->makeCategory('Geography');

        $importer = $this->import($this->buildWorkbook([
            ['Geography', 'Q1', 'A1', null, 200],
            [null, null, null, null, null],
            ['Geography', 'Q2', 'A2', null, 400],
        ]));

        $this->assertSame([], $importer->errors);
        $this->assertSame(2, $importer->created);
        $this->assertSame(1, $importer->skipped);
    }

    public function test_it_rejects_a_sheet_that_is_not_the_template(): void
    {
        $this->makeCategory('Geography');

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        foreach (['nope', 'wrong', 'header'] as $i => $heading) {
            $sheet->setCellValue([$i + 1, 1], $heading);
        }
        $sheet->setCellValue([1, 2], 'Geography');

        $importer = $this->import($this->save($spreadsheet));

        $this->assertNotEmpty($importer->errors);
        $this->assertSame(0, $importer->created);
        $this->assertSame(0, Question::query()->count());
    }

    public function test_the_template_headings_are_panel_labels_and_include_both_picture_columns(): void
    {
        $headings = (new QuestionsTemplateSheet)->headings();

        $this->assertSame([
            __('panel.excel_category'),
            __('panel.excel_question'),
            __('panel.excel_answer'),
            __('panel.excel_hint'),
            __('panel.excel_score'),
            __('panel.bulk_excel_media_column'),
            __('panel.bulk_excel_answer_media_column'),
        ], $headings);

        // The database column names must not leak into what an admin reads.
        $this->assertSame([], array_intersect($headings, ['category_id', 'media', 'media_type', 'answer_media', 'answer_media_type']));
    }

    public function test_the_template_ships_a_category_reference_sheet(): void
    {
        $category = $this->makeCategory('Geography');

        $sheets = (new QuestionsTemplateExport)->sheets();

        $this->assertInstanceOf(QuestionsTemplateSheet::class, $sheets[0]);
        $this->assertInstanceOf(QuestionsTemplateCategoriesSheet::class, $sheets[1]);

        $reference = $sheets[1];
        $this->assertSame([$category->id, 'Saudi Arabia', $category->name], $reference->map($reference->collection()->first()));
    }

    public function test_the_downloaded_template_imports_once_it_is_filled_in(): void
    {
        $category = $this->makeCategory('Geography');

        $path = tempnam(sys_get_temp_dir(), 'xlsx').'.xlsx';
        $this->workbooks[] = $path;
        file_put_contents($path, Excel::raw(new QuestionsTemplateExport, ExcelWriter::XLSX));

        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getSheetByName(__('panel.excel_questions_sheet'));
        $sheet->setCellValue('A2', $category->name);
        $sheet->setCellValue('B2', 'Capital of France?');
        $sheet->setCellValue('C2', 'Paris');
        $sheet->setCellValue('E2', 200);
        (new Xlsx($spreadsheet))->save($path);

        $importer = $this->import($path);

        $this->assertSame([], $importer->errors);
        $this->assertSame(1, $importer->created);
        $this->assertSame($category->id, Question::query()->firstOrFail()->category_id);
    }

    public function test_a_row_that_keeps_its_id_edits_that_question_instead_of_adding_one(): void
    {
        $category = $this->makeCategory('Geography');
        $question = $this->makeQuestion($category, ['question' => 'Old text', 'answer' => 'Old answer', 'score' => 200]);

        $importer = $this->import($this->buildExportWorkbook([
            [$question->id, $category->id, $category->name, 'New text', 'New answer', 'New hint', 400],
        ]));

        $this->assertSame([], $importer->errors);
        $this->assertSame(1, $importer->updated);
        $this->assertSame(0, $importer->created);
        $this->assertSame(1, Question::query()->count());

        $question->refresh();
        $this->assertSame('New text', $question->question);
        $this->assertSame('New answer', $question->answer);
        $this->assertSame('New hint', $question->hint);
        $this->assertSame(400, $question->score);
    }

    public function test_re_uploading_an_untouched_export_changes_nothing(): void
    {
        $category = $this->makeCategory('Geography');
        $question = $this->makeQuestion($category, ['question' => 'Q1', 'answer' => 'A1', 'hint' => 'H1', 'score' => 200]);
        $before = $question->updated_at;

        $importer = $this->import($this->buildExportWorkbook([
            [$question->id, $category->id, $category->name, 'Q1', 'A1', 'H1', 200],
        ]));

        $this->assertSame([], $importer->errors);
        $this->assertSame(0, $importer->updated);
        $this->assertSame(0, $importer->created);
        $this->assertSame(1, $importer->unchanged);
        $this->assertEquals($before, $question->fresh()->updated_at);
    }

    public function test_a_row_with_no_id_is_added_alongside_the_edited_ones(): void
    {
        $category = $this->makeCategory('Geography');
        $question = $this->makeQuestion($category, ['question' => 'Q1', 'answer' => 'A1']);

        $importer = $this->import($this->buildExportWorkbook([
            [$question->id, $category->id, $category->name, 'Q1 edited', 'A1', null, 200],
            [null, $category->id, $category->name, 'Brand new', 'A2', null, 600],
        ]));

        $this->assertSame([], $importer->errors);
        $this->assertSame(1, $importer->updated);
        $this->assertSame(1, $importer->created);
        $this->assertSame(2, Question::query()->count());
        $this->assertSame('Q1 edited', $question->fresh()->question);
    }

    public function test_an_id_that_no_longer_exists_is_reported_and_creates_nothing(): void
    {
        $category = $this->makeCategory('Geography');

        $importer = $this->import($this->buildExportWorkbook([
            [4242, $category->id, $category->name, 'Q1', 'A1', null, 200],
        ]));

        $this->assertSame(0, $importer->created);
        $this->assertSame(0, $importer->updated);
        $this->assertSame(1, $importer->skipped);
        $this->assertStringContainsString('4242', $importer->errors[0]['errors'][0] ?? '');
        $this->assertSame(0, Question::query()->count());
    }

    public function test_an_edit_leaves_media_alone_when_the_row_carries_none(): void
    {
        $category = $this->makeCategory('Geography');
        Storage::disk('public')->put('questions/keep.png', 'fake');
        $question = $this->makeQuestion($category, [
            'question' => 'Q1',
            'answer' => 'A1',
            'media' => 'questions/keep.png',
            'media_type' => 'image',
        ]);

        $this->import($this->buildExportWorkbook([
            [$question->id, $category->id, $category->name, 'Q1 edited', 'A1', null, 200],
        ]));

        $question->refresh();
        $this->assertSame('Q1 edited', $question->question);
        // The export draws pictures onto the sheet, so a blank cell is not a removal.
        $this->assertSame('questions/keep.png', $question->media);
        $this->assertSame('image', $question->media_type);
    }

    public function test_an_edit_replaces_media_when_the_row_carries_a_new_picture(): void
    {
        $category = $this->makeCategory('Geography');
        Storage::disk('public')->put('questions/old.png', 'fake');
        $question = $this->makeQuestion($category, [
            'question' => 'Q1',
            'answer' => 'A1',
            'media' => 'questions/old.png',
            'media_type' => 'image',
        ]);

        $this->import($this->buildExportWorkbook(
            [[$question->id, $category->id, $category->name, 'Q1', 'A1', null, 200]],
            questionImage: true,
        ));

        $question->refresh();
        $this->assertNotSame('questions/old.png', $question->media);
        $this->assertStringStartsWith('questions/', $question->media);
        $this->assertSame('image', $question->media_type);
    }

    public function test_re_uploading_an_exported_picture_keeps_the_file_it_came_from(): void
    {
        $category = $this->makeCategory('Geography');

        $png = file_get_contents($this->makePng(3));
        Storage::disk('public')->put('questions/original.png', $png);

        $question = $this->makeQuestion($category, [
            'question' => 'Q1',
            'answer' => 'A1',
            'media' => 'questions/original.png',
            'media_type' => 'image',
        ]);

        // The export draws the stored picture onto the sheet; re-uploading it untouched
        // must not stack up a fresh copy of the same bytes on every round trip.
        $importer = $this->import($this->buildExportWorkbook(
            [[$question->id, $category->id, $category->name, 'Q1', 'A1', null, 200]],
            questionImage: true,
            questionImagePath: 'questions/original.png',
        ));

        $this->assertSame([], $importer->errors);
        $this->assertSame(0, $importer->updated);
        $this->assertSame(1, $importer->unchanged);
        $this->assertSame('questions/original.png', $question->fresh()->media);
        $this->assertCount(1, Storage::disk('public')->files('questions'));
    }

    public function test_an_edit_keeps_media_named_by_its_stored_path(): void
    {
        $category = $this->makeCategory('Geography');
        Storage::disk('public')->put('questions/clip.mp4', 'fake');
        $question = $this->makeQuestion($category, [
            'question' => 'Q1',
            'answer' => 'A1',
            'media' => 'questions/clip.mp4',
            'media_type' => 'video',
        ]);

        // The export writes the path as text for media it cannot draw.
        $this->import($this->buildExportWorkbook([
            [$question->id, $category->id, $category->name, 'Q1', 'A1', null, 200, 'questions/clip.mp4'],
        ]));

        $question->refresh();
        $this->assertSame('questions/clip.mp4', $question->media);
        $this->assertSame('video', $question->media_type);
    }

    public function test_an_edit_ignores_a_media_path_that_names_no_file(): void
    {
        $category = $this->makeCategory('Geography');
        Storage::disk('public')->put('questions/real.png', 'fake');
        $question = $this->makeQuestion($category, [
            'question' => 'Q1',
            'answer' => 'A1',
            'media' => 'questions/real.png',
            'media_type' => 'image',
        ]);

        $this->import($this->buildExportWorkbook([
            [$question->id, $category->id, $category->name, 'Q1', 'A1', null, 200, 'questions/typo.png'],
        ]));

        $this->assertSame('questions/real.png', $question->fresh()->media);
    }

    public function test_an_edit_moves_a_question_to_another_category(): void
    {
        $geography = $this->makeCategory('Geography');
        $history = $this->makeCategory('History');
        $question = $this->makeQuestion($geography, ['question' => 'Q1', 'answer' => 'A1']);

        $this->import($this->buildExportWorkbook([
            [$question->id, $history->id, $history->name, 'Q1', 'A1', null, 200],
        ]));

        $this->assertSame($history->id, $question->fresh()->category_id);
    }

    public function test_a_row_whose_category_id_and_name_disagree_is_refused(): void
    {
        $geography = $this->makeCategory('Geography');
        $history = $this->makeCategory('History');
        $question = $this->makeQuestion($geography, ['question' => 'Q1', 'answer' => 'A1']);

        // Editing the readable name but leaving the stale id would otherwise move the
        // question wherever the losing column pointed.
        $importer = $this->import($this->buildExportWorkbook([
            [$question->id, $geography->id, $history->name, 'Q1', 'A1', null, 200],
        ]));

        $this->assertSame(0, $importer->updated);
        $this->assertSame(1, $importer->skipped);
        $this->assertNotEmpty($importer->errors);
        $this->assertSame($geography->id, $question->fresh()->category_id);
    }

    public function test_an_edit_that_names_no_category_keeps_the_current_one(): void
    {
        $category = $this->makeCategory('Geography');
        $question = $this->makeQuestion($category, ['question' => 'Q1', 'answer' => 'A1']);

        $importer = $this->import($this->buildExportWorkbook([
            [$question->id, null, null, 'Q1 edited', 'A1', null, 200],
        ]));

        $this->assertSame([], $importer->errors);
        $this->assertSame(1, $importer->updated);
        $this->assertSame($category->id, $question->fresh()->category_id);
    }

    public function test_the_export_headings_are_the_layout_the_importer_reads(): void
    {
        $this->assertSame([
            __('panel.excel_id'),
            __('panel.excel_category_id'),
            __('panel.excel_category_name'),
            __('panel.excel_question'),
            __('panel.excel_answer'),
            __('panel.excel_hint'),
            __('panel.excel_score'),
            __('panel.bulk_excel_media_column'),
            __('panel.bulk_excel_answer_media_column'),
        ], (new QuestionsExport)->headings());
    }

    private function import(string $path): QuestionsExcelImporter
    {
        $importer = new QuestionsExcelImporter(app(QrCodeService::class));
        $importer->import($path);

        return $importer;
    }

    private function makeCategory(string $name = 'General'): Category
    {
        return Category::query()->create([
            'name' => $name,
            'country_id' => Country::query()->firstOrCreate(['name' => 'Saudi Arabia'])->id,
        ]);
    }

    /**
     * Write a workbook using the template's translated header row.
     *
     * @param  list<list<string|int|null>>  $rows  category, question, answer, hint, score
     */
    private function buildWorkbook(array $rows, bool $questionImage = false, bool $answerImage = false): string
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        $headings = (new QuestionsTemplateSheet)->headings();
        foreach ($headings as $i => $heading) {
            $sheet->setCellValue([$i + 1, 1], $heading);
        }

        foreach ($rows as $rowIndex => $row) {
            $excelRow = $rowIndex + 2;

            foreach ($row as $i => $value) {
                if ($value !== null) {
                    $sheet->setCellValue([$i + 1, $excelRow], $value);
                }
            }

            if ($rowIndex === 0 && $questionImage) {
                $this->attachDrawing($sheet, 'F'.$excelRow, $this->makePng(1));
            }

            if ($rowIndex === 0 && $answerImage) {
                $this->attachDrawing($sheet, 'G'.$excelRow, $this->makePng(2));
            }
        }

        return $this->save($spreadsheet);
    }

    /**
     * Write a workbook shaped like the sheet "Export questions to Excel" produces.
     *
     * @param  list<list<string|int|null>>  $rows  id, category id, category name, question, answer, hint, score, media path
     */
    private function buildExportWorkbook(array $rows, bool $questionImage = false, ?string $questionImagePath = null): string
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        foreach ((new QuestionsExport)->headings() as $i => $heading) {
            $sheet->setCellValue([$i + 1, 1], $heading);
        }

        foreach ($rows as $rowIndex => $row) {
            $excelRow = $rowIndex + 2;

            foreach ($row as $i => $value) {
                if ($value !== null) {
                    $sheet->setCellValue([$i + 1, $excelRow], $value);
                }
            }

            if ($rowIndex === 0 && $questionImage) {
                // Mirror the export, which draws the file already on disk onto the sheet.
                $source = $questionImagePath === null
                    ? $this->makePng(1)
                    : Storage::disk('public')->path($questionImagePath);

                $this->attachDrawing($sheet, 'H'.$excelRow, $source);
            }
        }

        return $this->save($spreadsheet);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function makeQuestion(Category $category, array $attributes = []): Question
    {
        return Question::query()->create(array_merge([
            'category_id' => $category->id,
            'question' => 'Q?',
            'answer' => 'A',
            'score' => 200,
            'qr_code' => 'qr-codes/existing.svg',
        ], $attributes));
    }

    private function save(Spreadsheet $spreadsheet): string
    {
        $path = tempnam(sys_get_temp_dir(), 'xlsx').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);
        $this->workbooks[] = $path;

        return $path;
    }

    private function attachDrawing(Worksheet $sheet, string $coordinates, string $imagePath): void
    {
        $drawing = new Drawing;
        $drawing->setPath($imagePath);
        $drawing->setCoordinates($coordinates);
        $drawing->setWorksheet($sheet);
    }

    /**
     * A tiny on-disk PNG; the seed keeps the two pictures distinguishable.
     */
    private function makePng(int $seed): string
    {
        $path = tempnam(sys_get_temp_dir(), 'png').'.png';
        $image = imagecreatetruecolor(2 + $seed, 2 + $seed);
        imagepng($image, $path);
        imagedestroy($image);
        $this->workbooks[] = $path;

        return $path;
    }
}
