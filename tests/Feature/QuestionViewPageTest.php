<?php

namespace Tests\Feature;

use App\Filament\Resources\Questions\Pages\ListQuestions;
use App\Filament\Resources\Questions\Pages\ViewQuestion;
use App\Filament\Resources\Questions\QuestionResource;
use App\Models\Category;
use App\Models\Country;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

class QuestionViewPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->actingAs(User::factory()->create());
    }

    public function test_the_resource_exposes_a_view_page_of_its_own(): void
    {
        $question = $this->makeQuestion();

        // A registered view page is what turns the table's view action from a modal
        // into a page of its own, at its own URL.
        $this->assertArrayHasKey('view', QuestionResource::getPages());
        $this->assertStringEndsWith('/admin/questions/'.$question->id, QuestionResource::getUrl('view', ['record' => $question]));

        // The page is exercised over Livewire rather than HTTP: outside the local
        // environment Filament refuses panel requests for a user model that does not
        // implement FilamentUser, so every panel URL 403s under `testing`.
        Livewire::test(ViewQuestion::class, ['record' => $question->getRouteKey()])->assertOk();
    }

    public function test_the_table_view_action_navigates_instead_of_opening_a_modal(): void
    {
        $question = $this->makeQuestion();

        Livewire::test(ListQuestions::class)
            ->assertTableActionHasUrl('view', QuestionResource::getUrl('view', ['record' => $question]), record: $question);
    }

    public function test_the_page_shows_the_question_and_its_category(): void
    {
        $question = $this->makeQuestion([
            'question' => 'Capital of France?',
            'answer' => 'Paris',
            'hint' => 'City of light',
        ]);

        Livewire::test(ViewQuestion::class, ['record' => $question->getRouteKey()])
            ->assertOk()
            ->assertSee('Capital of France?')
            ->assertSee('Paris')
            ->assertSee('City of light')
            ->assertSee('General');
    }

    public function test_an_image_is_shown_as_a_picture(): void
    {
        $question = $this->makeQuestionWithMedia('questions/map.png', 'image');

        $this->renderPage($question)
            ->assertSee('storage/questions/map.png', false)
            ->assertSee('<img', false);
    }

    public function test_a_video_gets_a_player_instead_of_a_file_name(): void
    {
        $question = $this->makeQuestionWithMedia('questions/clip.mp4', 'video');

        $this->renderPage($question)
            ->assertSee('<video', false)
            ->assertSee('storage/questions/clip.mp4', false);
    }

    public function test_an_audio_file_gets_a_player(): void
    {
        $question = $this->makeQuestionWithMedia('questions/voice-note.m4a', 'audio');

        $this->renderPage($question)
            ->assertSee('<audio', false)
            ->assertDontSee('<video', false);
    }

    public function test_media_saved_without_a_type_still_reaches_the_right_player(): void
    {
        // Rows written before the media_type column was filled in carry a blank type.
        $question = $this->makeQuestionWithMedia('questions/voice-note.m4a', '');

        $this->renderPage($question)
            ->assertSee('<audio', false)
            ->assertDontSee('<video', false)
            ->assertDontSee('<img src="'.asset('storage/questions/voice-note.m4a'), false);
    }

    public function test_a_type_that_disagrees_with_the_file_is_ignored(): void
    {
        // An .mp4-branded voice note that was stored as video renders as audio.
        $question = $this->makeQuestionWithMedia('questions/note.m4a', 'video');

        $this->assertSame('audio', $question->fresh()->questionMediaType());
    }

    public function test_a_file_with_no_player_is_offered_as_a_link(): void
    {
        $question = $this->makeQuestionWithMedia('questions/rules.pdf', '');

        $this->renderPage($question)
            ->assertSee(__('panel.media_open_file'))
            ->assertDontSee('<video', false)
            ->assertDontSee('<audio', false);
    }

    public function test_media_missing_from_storage_is_reported_rather_than_rendered(): void
    {
        $question = $this->makeQuestion([
            'media' => 'questions/deleted.png',
            'media_type' => 'image',
        ]);

        $this->renderPage($question)
            ->assertSee(__('panel.media_file_missing'))
            ->assertDontSee('<img src="'.asset('storage/questions/deleted.png'), false);
    }

    public function test_the_answer_picture_is_shown_beside_the_question_one(): void
    {
        Storage::disk('public')->put('questions/q.png', 'fake');
        Storage::disk('public')->put('answers/a.mp3', 'fake');

        $question = $this->makeQuestion([
            'media' => 'questions/q.png',
            'media_type' => 'image',
            'answer_media' => 'answers/a.mp3',
            'answer_media_type' => 'audio',
        ]);

        $this->renderPage($question)
            ->assertSee('storage/questions/q.png', false)
            ->assertSee('storage/answers/a.mp3', false)
            ->assertSee('<audio', false);
    }

    public function test_a_question_without_media_says_so(): void
    {
        $question = $this->makeQuestion();

        $this->renderPage($question)->assertSee(__('panel.media_none'));
    }

    private function renderPage(Question $question): Testable
    {
        return Livewire::test(ViewQuestion::class, ['record' => $question->getRouteKey()])->assertOk();
    }

    private function makeQuestionWithMedia(string $path, string $type): Question
    {
        Storage::disk('public')->put($path, 'fake-media');

        return $this->makeQuestion(['media' => $path, 'media_type' => $type]);
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
            'country_id' => Country::query()->firstOrCreate(['name' => 'Saudi Arabia'])->id,
        ]);
    }
}
