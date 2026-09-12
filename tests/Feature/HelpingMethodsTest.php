<?php

namespace Tests\Feature;

use App\Http\Resources\TeamResource;
use App\Models\Category;
use App\Models\Country;
use App\Models\Game;
use App\Models\HelpingMethod;
use App\Models\Question;
use App\Models\Team;
use App\Models\User;
use App\Services\GameService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HelpingMethodsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    public function test_the_live_set_is_the_four_expected_methods_in_order(): void
    {
        $methods = HelpingMethod::ordered()->get();

        $this->assertSame([
            HelpingMethod::EXTRA_TIME,
            HelpingMethod::CHANGE_QUESTION,
            HelpingMethod::ANSWER_HINT,
            HelpingMethod::REVEAL_ANSWER,
        ], $methods->pluck('key')->all());

        $this->assertSame('زيادة وقت الإجابة', $methods->first()->name);
        $this->assertSame('عطنا الإجابة', $methods->last()->name);
    }

    public function test_every_live_method_ships_an_icon_file(): void
    {
        foreach (HelpingMethod::defaults() as $method) {
            $this->assertNotNull(
                $method['icon_file'],
                "{$method['key']} has no icon declared."
            );

            $this->assertFileExists(
                public_path('helping_methods/'.$method['icon_file']),
                "{$method['key']} points at an icon that is not in public/helping_methods."
            );
        }
    }

    public function test_calling_a_friend_is_retired_but_still_resolvable(): void
    {
        // Nothing offers it any more...
        $this->assertFalse(
            HelpingMethod::query()->where('key', HelpingMethod::CALL_FRIEND)->exists()
        );

        // ...but a historic team_helping_methods row can still name it.
        $retired = HelpingMethod::withTrashed()
            ->where('key', HelpingMethod::CALL_FRIEND)
            ->first();

        if ($retired !== null) {
            $this->assertNotNull($retired->deleted_at);
            $this->assertSame('اتصال بصديق', $retired->name);
        } else {
            // A database built from scratch never had the row at all.
            $this->assertTrue(true);
        }
    }

    public function test_reveal_answer_returns_the_answer_and_its_picture(): void
    {
        Storage::disk('public')->put('answers/paris.png', 'fake-png');
        [$team, $question] = $this->makeGame([
            'answer' => 'باريس',
            'answer_media' => 'answers/paris.png',
            'answer_media_type' => 'image',
        ]);

        $payload = app(GameService::class)->applyHelpingMethod(
            $team,
            $this->method(HelpingMethod::REVEAL_ANSWER),
            $question->id
        );

        $this->assertSame($question->id, $payload['question_id']);
        $this->assertSame('باريس', $payload['answer']);
        $this->assertStringEndsWith('storage/answers/paris.png', $payload['answer_media']);
        $this->assertSame('image', $payload['answer_media_type']);
    }

    public function test_reveal_answer_omits_a_picture_whose_file_is_gone(): void
    {
        [$team, $question] = $this->makeGame([
            'answer' => 'باريس',
            'answer_media' => 'answers/deleted.png',
            'answer_media_type' => 'image',
        ]);

        $payload = app(GameService::class)->applyHelpingMethod(
            $team,
            $this->method(HelpingMethod::REVEAL_ANSWER),
            $question->id
        );

        $this->assertSame('باريس', $payload['answer']);
        $this->assertNull($payload['answer_media']);
        $this->assertNull($payload['answer_media_type']);
    }

    public function test_extra_time_adds_thirty_seconds_to_the_category_tier(): void
    {
        [$team, $question] = $this->makeGame(['score' => 200], answerTime200: 45);

        $payload = app(GameService::class)->applyHelpingMethod(
            $team,
            $this->method(HelpingMethod::EXTRA_TIME),
            $question->id
        );

        $this->assertSame(GameService::EXTRA_TIME_SECONDS, $payload['extra_seconds']);
        $this->assertSame(45, $payload['base_seconds']);
        $this->assertSame(75, $payload['total_seconds']);
    }

    public function test_extra_time_still_reports_the_bonus_without_a_question(): void
    {
        [$team] = $this->makeGame();

        $payload = app(GameService::class)->applyHelpingMethod(
            $team,
            $this->method(HelpingMethod::EXTRA_TIME),
            null
        );

        $this->assertSame(30, $payload['extra_seconds']);
        $this->assertNull($payload['base_seconds']);
        $this->assertNull($payload['total_seconds']);
    }

    public function test_a_method_can_only_be_spent_once_per_team(): void
    {
        [$team, $question] = $this->makeGame();
        $service = app(GameService::class);

        $service->applyHelpingMethod($team, $this->method(HelpingMethod::EXTRA_TIME), $question->id);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('This helping method has already been used.');

        $service->applyHelpingMethod($team, $this->method(HelpingMethod::EXTRA_TIME), $question->id);
    }

    public function test_a_failed_effect_does_not_consume_the_method(): void
    {
        [$team] = $this->makeGame();
        $service = app(GameService::class);
        $method = $this->method(HelpingMethod::REVEAL_ANSWER);

        // A question that is not on this game's board.
        $stray = $this->makeQuestion($this->makeCategory(), []);

        try {
            $service->applyHelpingMethod($team, $method, $stray->id);
            $this->fail('Expected the stray question to be rejected.');
        } catch (\Exception $e) {
            $this->assertSame('Question not found in this game.', $e->getMessage());
        }

        // The transaction rolled back, so the team keeps the help.
        $this->assertSame(0, $team->usedHelpingMethods()->count());
    }

    public function test_change_question_swaps_the_board_question(): void
    {
        [$team, $question] = $this->makeGame(['score' => 200]);
        // An alternative of the same category and score for it to swap to.
        $this->makeQuestion($question->category, ['score' => 200, 'question' => 'بديل']);

        app(GameService::class)->applyHelpingMethod(
            $team,
            $this->method(HelpingMethod::CHANGE_QUESTION),
            $question->id
        );

        $this->assertDatabaseMissing('game_questions', [
            'game_id' => $team->game_id,
            'question_id' => $question->id,
        ]);
    }

    public function test_revealing_the_answer_still_earns_the_question_s_points(): void
    {
        [$team, $question] = $this->makeGame(['score' => 400]);
        $service = app(GameService::class);

        $service->applyHelpingMethod($team, $this->method(HelpingMethod::REVEAL_ANSWER), $question->id);

        $result = $service->awardQuestionScore($team->game, $question, $team->id);

        $this->assertSame(400, $result['score_awarded']);
        $this->assertSame(400, $team->fresh()->score);

        $this->assertDatabaseHas('game_questions', [
            'game_id' => $team->game_id,
            'question_id' => $question->id,
            'is_answered' => 1,
        ]);
    }

    public function test_scoring_is_unaffected_by_any_helping_method(): void
    {
        foreach ([HelpingMethod::EXTRA_TIME, HelpingMethod::ANSWER_HINT, HelpingMethod::REVEAL_ANSWER] as $key) {
            [$team, $question] = $this->makeGame(['score' => 600]);
            $service = app(GameService::class);

            $service->applyHelpingMethod($team, $this->method($key), $question->id);
            $result = $service->awardQuestionScore($team->game, $question, $team->id);

            $this->assertSame(600, $result['score_awarded'], "{$key} changed the score awarded.");
            $this->assertSame(600, $team->fresh()->score, "{$key} changed the team's score.");
        }
    }

    public function test_a_team_scores_without_spending_any_help(): void
    {
        [$team, $question] = $this->makeGame(['score' => 400]);

        $result = app(GameService::class)->awardQuestionScore($team->game, $question, $team->id);

        $this->assertSame(400, $result['score_awarded']);
        $this->assertSame(400, $team->fresh()->score);
    }

    public function test_no_team_is_credited_when_nobody_takes_the_question(): void
    {
        [$team, $question] = $this->makeGame(['score' => 400]);

        $result = app(GameService::class)->awardQuestionScore($team->game, $question, null);

        $this->assertSame(0, $result['score_awarded']);
        $this->assertSame(0, $team->fresh()->score);

        // The question still closes out so the board can move on.
        $this->assertDatabaseHas('game_questions', [
            'game_id' => $team->game_id,
            'question_id' => $question->id,
            'is_answered' => 1,
        ]);
    }

    public function test_the_question_a_help_was_spent_on_is_recorded(): void
    {
        [$team, $question] = $this->makeGame();

        app(GameService::class)->applyHelpingMethod(
            $team,
            $this->method(HelpingMethod::REVEAL_ANSWER),
            $question->id
        );

        $this->assertDatabaseHas('team_helping_methods', [
            'team_id' => $team->id,
            'helping_method_id' => $this->method(HelpingMethod::REVEAL_ANSWER)->id,
            'question_id' => $question->id,
        ]);
    }

    public function test_the_team_payload_lists_every_live_method(): void
    {
        [$team] = $this->makeGame();
        $team->load('usedHelpingMethods');

        $payload = (new TeamResource($team))->toArray(request());

        $this->assertCount(4, $payload['helping_methods']);
        $this->assertSame(
            [
                HelpingMethod::EXTRA_TIME,
                HelpingMethod::CHANGE_QUESTION,
                HelpingMethod::ANSWER_HINT,
                HelpingMethod::REVEAL_ANSWER,
            ],
            collect($payload['helping_methods'])->pluck('key')->all()
        );
        $this->assertFalse($payload['helping_methods'][0]['used']);
    }

    private function method(string $key): HelpingMethod
    {
        return HelpingMethod::query()->where('key', $key)->firstOrFail();
    }

    /**
     * A one-team game with a single question on its board.
     *
     * @param  array<string, mixed>  $questionAttributes
     * @return array{0: Team, 1: Question}
     */
    private function makeGame(array $questionAttributes = [], int $answerTime200 = 30): array
    {
        $user = User::query()->create([
            'name' => 'Host',
            'email' => 'host'.uniqid().'@example.com',
            'password' => 'secret',
        ]);

        $category = $this->makeCategory($answerTime200);
        $question = $this->makeQuestion($category, $questionAttributes);

        $game = Game::query()->create(['name' => 'Game', 'user_id' => $user->id]);
        $game->questions()->attach($question->id);

        $team = Team::query()->create(['game_id' => $game->id, 'name' => 'Team A']);
        $team->setRelation('game', $game);

        return [$team, $question];
    }

    private function makeCategory(int $answerTime200 = 30): Category
    {
        return Category::query()->create([
            'name' => 'General',
            'country_id' => Country::query()->create(['name' => 'Saudi Arabia'])->id,
            'answer_time_200' => $answerTime200,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function makeQuestion(Category $category, array $attributes): Question
    {
        return Question::query()->create(array_merge([
            'category_id' => $category->id,
            'question' => 'سؤال',
            'answer' => 'جواب',
            'score' => 200,
        ], $attributes));
    }
}
