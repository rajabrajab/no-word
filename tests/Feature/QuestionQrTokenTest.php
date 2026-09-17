<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Country;
use App\Models\Question;
use App\Services\QrCodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class QuestionQrTokenTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    public function test_the_public_page_cannot_be_reached_by_the_question_id(): void
    {
        $question = $this->makeQuestion(['answer' => 'Paris']);

        // The whole point: a printed card must not let anyone walk /question/1, /2, /3.
        $this->get('/question/'.$question->id)->assertNotFound();
        $this->get('/question/1')->assertNotFound();
    }

    public function test_the_public_page_is_reached_by_the_token(): void
    {
        $question = $this->makeQuestion(['question' => 'Capital of France?', 'answer' => 'Paris']);

        $response = $this->get('/question/'.$question->qr_token);

        $response->assertOk();
        $response->assertSee('Capital of France?');
    }

    public function test_a_token_that_belongs_to_nobody_is_not_found(): void
    {
        $this->makeQuestion();

        $this->get('/question/'.Question::newQrToken())->assertNotFound();
    }

    public function test_knowing_one_token_does_not_reveal_its_neighbours(): void
    {
        $category = $this->makeCategory();
        $first = $this->makeQuestion(['category_id' => $category->id]);
        $second = $this->makeQuestion(['category_id' => $category->id]);

        $this->assertNotSame($first->qr_token, $second->qr_token);
        $this->assertSame(32, strlen($first->qr_token));
        $this->assertMatchesRegularExpression('/^[0-9a-f]{32}$/', $first->qr_token);

        // Tokens are random, so a neighbouring id says nothing about a neighbouring token.
        $this->assertNotSame(1, levenshtein($first->qr_token, $second->qr_token));
    }

    public function test_every_new_question_is_given_a_token(): void
    {
        $question = $this->makeQuestion();

        $this->assertNotEmpty($question->qr_token);
        $this->assertDatabaseHas('questions', ['id' => $question->id, 'qr_token' => $question->qr_token]);
    }

    public function test_the_generated_code_encodes_the_token_url_and_hides_the_id(): void
    {
        $question = $this->makeQuestion();

        $path = app(QrCodeService::class)->generateForQuestion($question);

        // The file name must not carry the id either: the disk is public, so an
        // id-named code could be read in order and every token recovered from it.
        $this->assertSame('qr-codes/'.$question->qr_token.'.svg', $path);
        $this->assertStringNotContainsString('question-'.$question->id, $path);
        Storage::disk('public')->assertExists($path);
    }

    public function test_regenerating_a_code_deletes_the_one_it_replaces(): void
    {
        $question = $this->makeQuestion();
        Storage::disk('public')->put('qr-codes/question-'.$question->id.'.svg', 'old-svg');
        $question->update(['qr_code' => 'qr-codes/question-'.$question->id.'.svg']);

        app(QrCodeService::class)->generateForQuestion($question);

        Storage::disk('public')->assertMissing('qr-codes/question-'.$question->id.'.svg');
    }

    public function test_the_refresh_command_redraws_codes_and_clears_the_old_id_named_files(): void
    {
        $question = $this->makeQuestion();
        Storage::disk('public')->put('qr-codes/question-'.$question->id.'.svg', 'old-svg');
        $question->update(['qr_code' => 'qr-codes/question-'.$question->id.'.svg']);

        $this->artisan('questions:refresh-qr-codes')->assertSuccessful();

        $question->refresh();
        $this->assertSame('qr-codes/'.$question->qr_token.'.svg', $question->qr_code);
        Storage::disk('public')->assertExists($question->qr_code);
        Storage::disk('public')->assertMissing('qr-codes/question-'.$question->id.'.svg');
    }

    public function test_the_refresh_command_keeps_tokens_unless_asked_to_rotate_them(): void
    {
        $question = $this->makeQuestion();
        $token = $question->qr_token;

        $this->artisan('questions:refresh-qr-codes')->assertSuccessful();

        // Printed cards keep working: a redraw must not silently re-issue tokens.
        $this->assertSame($token, $question->fresh()->qr_token);
    }

    public function test_the_refresh_command_can_rotate_tokens_when_confirmed(): void
    {
        $question = $this->makeQuestion();
        $token = $question->qr_token;

        $this->artisan('questions:refresh-qr-codes --new-tokens')
            ->expectsConfirmation('Issuing new tokens breaks every QR code already printed. Continue?', 'yes')
            ->assertSuccessful();

        $this->assertNotSame($token, $question->fresh()->qr_token);
    }

    public function test_rotating_tokens_is_abandoned_when_not_confirmed(): void
    {
        $question = $this->makeQuestion();
        $token = $question->qr_token;

        $this->artisan('questions:refresh-qr-codes --new-tokens')
            ->expectsConfirmation('Issuing new tokens breaks every QR code already printed. Continue?', 'no')
            ->assertSuccessful();

        $this->assertSame($token, $question->fresh()->qr_token);
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
