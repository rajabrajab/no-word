<?php

namespace Tests\Feature;

use App\Exports\CategoryBulkQuestionsTemplateExport;
use App\Http\Resources\QuestionResource;
use App\Models\Category;
use App\Models\Country;
use App\Models\Question;
use App\Services\CategoryBulkQuestionsExcelImporter;
use App\Services\QrCodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class AnswerMediaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    public function test_qr_page_hides_the_answer_behind_a_reveal_button(): void
    {
        Storage::disk('public')->put('answers/paris.png', 'fake-png');
        $question = $this->makeQuestion([
            'question' => 'What is the capital of France?',
            'answer' => 'Paris',
            'answer_media' => 'answers/paris.png',
            'answer_media_type' => 'image',
        ]);

        $response = $this->get(route('question.show', $question));

        $response->assertOk();
        $response->assertSee('What is the capital of France?');
        // The answer ships with the page but starts collapsed behind the button.
        $response->assertSee('id="answerBlock" hidden', false);
        $response->assertSee('aria-expanded="false"', false);
        $response->assertSee('Paris');
        $response->assertSee('storage/answers/paris.png', false);
    }

    public function test_qr_page_renders_without_answer_media(): void
    {
        $question = $this->makeQuestion([
            'answer' => 'Paris',
            'answer_media' => null,
            'answer_media_type' => null,
        ]);

        $response = $this->get(route('question.show', $question));

        $response->assertOk();
        $response->assertSee('Paris');
        $response->assertDontSee('alt="Answer Media"', false);
    }

    public function test_qr_page_omits_the_media_box_when_the_question_has_no_picture(): void
    {
        $question = $this->makeQuestion(['media' => null, 'media_type' => null]);

        $response = $this->get(route('question.show', $question));

        $response->assertOk();
        $response->assertDontSee('<div class="media-container">', false);
    }

    public function test_qr_page_omits_the_media_box_when_the_stored_file_is_gone(): void
    {
        // The row still points at a picture, but nothing is on disk to render.
        $question = $this->makeQuestion([
            'media' => 'questions/deleted.png',
            'media_type' => 'image',
            'answer_media' => 'answers/deleted.png',
            'answer_media_type' => 'image',
        ]);

        $response = $this->get(route('question.show', $question));

        $response->assertOk();
        $response->assertDontSee('<div class="media-container">', false);
        $response->assertDontSee('questions/deleted.png', false);
        $response->assertDontSee('answers/deleted.png', false);
    }

    public function test_qr_page_renders_the_media_box_when_the_file_is_present(): void
    {
        Storage::disk('public')->put('questions/present.png', 'fake-png');
        $question = $this->makeQuestion(['media' => 'questions/present.png', 'media_type' => 'image']);

        $response = $this->get(route('question.show', $question));

        $response->assertOk();
        $response->assertSee('<div class="media-container">', false);
        $response->assertSee('storage/questions/present.png', false);
    }

    public function test_qr_page_plays_question_audio_in_an_audio_player(): void
    {
        Storage::disk('public')->put('questions/voice-note.m4a', 'fake-m4a');
        $question = $this->makeQuestion([
            'media' => 'questions/voice-note.m4a',
            'media_type' => 'audio',
        ]);

        $response = $this->get(route('question.show', $question));

        $response->assertOk();
        $response->assertSee('<audio controls', false);
        // A voice note used to land in the video branch's iframe fallback.
        $response->assertDontSee('<iframe', false);
        $response->assertDontSee('<video controls', false);
    }

    public function test_qr_page_plays_answer_audio_saved_without_a_type(): void
    {
        Storage::disk('public')->put('answers/clue.m4a', 'fake-m4a');
        $question = $this->makeQuestion([
            'answer_media' => 'answers/clue.m4a',
            'answer_media_type' => '',
        ]);

        $response = $this->get(route('question.show', $question));

        $response->assertOk();
        $response->assertSee('<audio controls', false);
        // A blank type used to drop answer media into the picture branch.
        $response->assertDontSee('<img src="'.asset('storage/answers/clue.m4a'), false);
    }

    public function test_qr_page_plays_answer_video_saved_without_a_type(): void
    {
        Storage::disk('public')->put('answers/reveal.mp4', 'fake-mp4');
        $question = $this->makeQuestion([
            'answer_media' => 'answers/reveal.mp4',
            'answer_media_type' => null,
        ]);

        $response = $this->get(route('question.show', $question));

        $response->assertOk();
        $response->assertSee('<video controls', false);
    }

    public function test_api_resource_reports_the_type_of_media_saved_without_one(): void
    {
        Storage::disk('public')->put('questions/voice.m4a', 'fake-m4a');
        $question = $this->makeQuestion([
            'media' => 'questions/voice.m4a',
            'media_type' => '',
        ]);

        $payload = (new QuestionResource($question))->toArray(request());

        $this->assertSame('audio', $payload['media_type']);
    }

    public function test_api_resource_exposes_answer_media_url(): void
    {
        $question = $this->makeQuestion([
            'answer_media' => 'answers/paris.png',
            'answer_media_type' => 'image',
        ]);

        $payload = (new QuestionResource($question))
            ->toArray(request());

        $this->assertSame('image', $payload['answer_media_type']);
        $this->assertStringEndsWith('storage/answers/paris.png', $payload['answer_media']);
    }

    public function test_api_resource_leaves_answer_media_null_when_unset(): void
    {
        $payload = (new QuestionResource($this->makeQuestion()))
            ->toArray(request());

        $this->assertNull($payload['answer_media']);
        $this->assertNull($payload['answer_media_type']);
    }

    public function test_excel_import_splits_question_and_answer_pictures_by_column(): void
    {
        $category = $this->makeCategory();
        $path = $this->buildWorkbook([
            'question' => 'Q1',
            'answer' => 'A1',
            'hint' => 'H1',
            'score' => 200,
        ], questionImage: true, answerImage: true);

        $importer = new CategoryBulkQuestionsExcelImporter($category->id, app(QrCodeService::class));
        $importer->import($path);

        $this->assertSame([], $importer->errors);
        $this->assertSame(1, $importer->created);

        $question = Question::query()->firstOrFail();
        $this->assertSame('Q1', $question->question);
        $this->assertSame('A1', $question->answer);
        $this->assertStringStartsWith('questions/', $question->media);
        $this->assertSame('image', $question->media_type);
        $this->assertStringStartsWith('answers/', $question->answer_media);
        $this->assertSame('image', $question->answer_media_type);
        $this->assertNotSame($question->media, $question->answer_media);
    }

    public function test_excel_import_accepts_a_row_with_only_a_question_picture(): void
    {
        $category = $this->makeCategory();
        $path = $this->buildWorkbook([
            'question' => 'Q1',
            'answer' => 'A1',
            'hint' => null,
            'score' => 400,
        ], questionImage: true, answerImage: false);

        $importer = new CategoryBulkQuestionsExcelImporter($category->id, app(QrCodeService::class));
        $importer->import($path);

        $this->assertSame([], $importer->errors);

        $question = Question::query()->firstOrFail();
        $this->assertStringStartsWith('questions/', $question->media);
        $this->assertNull($question->answer_media);
        $this->assertNull($question->answer_media_type);
    }

    public function test_excel_import_still_accepts_the_legacy_five_column_sheet(): void
    {
        $category = $this->makeCategory();
        $path = $this->buildLegacyWorkbook();

        $importer = new CategoryBulkQuestionsExcelImporter($category->id, app(QrCodeService::class));
        $importer->import($path);

        @unlink($path);

        $this->assertSame([], $importer->errors);
        $this->assertSame(1, $importer->created);

        $question = Question::query()->firstOrFail();
        // With no answer column on the sheet, the picture stays with the question.
        $this->assertStringStartsWith('questions/', $question->media);
        $this->assertNull($question->answer_media);
    }

    public function test_excel_import_rejects_an_unrecognised_header(): void
    {
        $category = $this->makeCategory();

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        foreach (['nope', 'wrong', 'header'] as $i => $heading) {
            $sheet->setCellValue([$i + 1, 1], $heading);
        }
        $sheet->setCellValue([1, 2], 'Q1');

        $path = tempnam(sys_get_temp_dir(), 'xlsx').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        $importer = new CategoryBulkQuestionsExcelImporter($category->id, app(QrCodeService::class));
        $importer->import($path);

        @unlink($path);

        $this->assertNotEmpty($importer->errors);
        $this->assertSame(0, $importer->created);
        $this->assertSame(0, Question::query()->count());
    }

    public function test_template_export_ships_the_answer_picture_column(): void
    {
        $headings = (new CategoryBulkQuestionsTemplateExport)->headings();

        $this->assertCount(6, $headings);
        $this->assertSame(__('panel.bulk_excel_answer_media_column'), $headings[5]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function makeQuestion(array $attributes = []): Question
    {
        return Question::query()->create(array_merge([
            'category_id' => $this->makeCategory()->id,
            'question' => 'Q?',
            'answer' => 'A',
            'score' => 200,
        ], $attributes));
    }

    private function makeCategory(): Category
    {
        return Category::query()->create([
            'name' => 'General',
            'country_id' => Country::query()->create(['name' => 'Saudi Arabia'])->id,
        ]);
    }

    /**
     * Write a single-row workbook using the machine header, optionally embedding
     * a picture in the question column (E) and/or the answer column (F).
     *
     * @param  array{question: string, answer: string, hint: ?string, score: ?int}  $row
     */
    private function buildWorkbook(array $row, bool $questionImage, bool $answerImage): string
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        foreach (['question', 'answer', 'hint', 'score', 'media', 'answer_media'] as $i => $heading) {
            $sheet->setCellValue([$i + 1, 1], $heading);
        }

        $sheet->setCellValue([1, 2], $row['question']);
        $sheet->setCellValue([2, 2], $row['answer']);
        $sheet->setCellValue([3, 2], $row['hint']);
        $sheet->setCellValue([4, 2], $row['score']);

        if ($questionImage) {
            $this->attachDrawing($sheet, 'E2', $this->makePng(1));
        }

        if ($answerImage) {
            $this->attachDrawing($sheet, 'F2', $this->makePng(2));
        }

        $path = tempnam(sys_get_temp_dir(), 'xlsx').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return $path;
    }

    /**
     * A sheet in the shape the template had before the answer-picture column existed.
     */
    private function buildLegacyWorkbook(): string
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        foreach (['question', 'answer', 'hint', 'score', 'media'] as $i => $heading) {
            $sheet->setCellValue([$i + 1, 1], $heading);
        }

        $sheet->setCellValue([1, 2], 'Q1');
        $sheet->setCellValue([2, 2], 'A1');
        $this->attachDrawing($sheet, 'E2', $this->makePng(1));

        $path = tempnam(sys_get_temp_dir(), 'xlsx').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return $path;
    }

    private function attachDrawing(Worksheet $sheet, string $coordinates, string $imagePath): void
    {
        $drawing = new Drawing;
        $drawing->setPath($imagePath);
        $drawing->setCoordinates($coordinates);
        $drawing->setWorksheet($sheet);
    }

    private function makePng(int $seed): string
    {
        $image = imagecreatetruecolor(4, 4);
        imagefill($image, 0, 0, imagecolorallocate($image, $seed * 40, $seed * 20, $seed * 10));

        $path = tempnam(sys_get_temp_dir(), 'png').'.png';
        imagepng($image, $path);
        imagedestroy($image);

        return $path;
    }
}
