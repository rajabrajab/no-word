<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Country;
use App\Models\Question;
use App\Services\LandingShowcaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingShowcaseApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_answers_in_the_api_envelope(): void
    {
        $this->makeFullCategory('جغرافيا');

        $response = $this->getJson('/api/landing');

        $response->assertOk();
        $response->assertJsonStructure([
            'state',
            'code',
            'message',
            'data' => [
                'board' => [['category', 'tone', 'questions' => [['score', 'question', 'answer']]]],
                'marquee',
                'countries' => [['name', 'categories']],
            ],
        ]);
        $this->assertTrue($response->json('state'));
    }

    public function test_the_board_columns_carry_the_real_questions_of_real_categories(): void
    {
        $category = $this->makeFullCategory('جغرافيا');

        $data = $this->getJson('/api/landing')->json('data.board');

        $this->assertCount(1, $data);
        $this->assertSame('جغرافيا', $data[0]['category']);

        $this->assertSame([200, 400, 600], array_column($data[0]['questions'], 'score'));

        foreach ($data[0]['questions'] as $question) {
            $this->assertTrue(
                Question::query()
                    ->where('category_id', $category->id)
                    ->where('score', $question['score'])
                    ->where('question', $question['question'])
                    ->where('answer', $question['answer'])
                    ->exists(),
                'The board showed a question and answer that are not a row in the database.'
            );
        }
    }

    public function test_it_fills_the_board_with_no_more_columns_than_it_has_room_for(): void
    {
        foreach (['جغرافيا', 'رياضة', 'علوم', 'تاريخ', 'مشاهير'] as $name) {
            $this->makeFullCategory($name);
        }

        $board = $this->getJson('/api/landing')->json('data.board');

        $this->assertCount(LandingShowcaseService::BOARD_CATEGORIES, $board);
        // Painted by position, since no colour is stored: three columns, three tones.
        $this->assertSame(['orange', 'green', 'blue'], array_column($board, 'tone'));
    }

    public function test_a_category_missing_a_score_tier_never_reaches_the_board(): void
    {
        $full = $this->makeFullCategory('جغرافيا');
        $thin = $this->makeCategory('رياضة');
        // Only two of the three tiers: the third square would open onto nothing.
        $this->makeQuestion($thin, 200);
        $this->makeQuestion($thin, 400);

        $board = $this->getJson('/api/landing')->json('data.board');

        $this->assertSame([$full->name], array_column($board, 'category'));
    }

    public function test_a_question_an_admin_deleted_is_not_shown(): void
    {
        $category = $this->makeCategory('جغرافيا');
        $this->makeQuestion($category, 200);
        $this->makeQuestion($category, 400);
        $keep = $this->makeQuestion($category, 600, 'Kept');
        $this->makeQuestion($category, 600, 'Deleted')->delete();

        $board = $this->getJson('/api/landing')->json('data.board');

        $sixHundred = collect($board[0]['questions'])->firstWhere('score', 600);
        $this->assertSame($keep->question, $sixHundred['question']);
    }

    public function test_the_ticker_lists_distinct_category_names_from_the_database(): void
    {
        $gulf = Country::query()->create(['name' => 'الكويت']);
        $saudi = Country::query()->create(['name' => 'السعودية']);

        // Every country runs its own copy of the popular names.
        Category::query()->create(['name' => 'جغرافيا', 'country_id' => $gulf->id]);
        Category::query()->create(['name' => 'جغرافيا', 'country_id' => $saudi->id]);
        Category::query()->create(['name' => 'رياضة', 'country_id' => $gulf->id]);

        $marquee = $this->getJson('/api/landing')->json('data.marquee');

        sort($marquee);
        $this->assertSame(['جغرافيا', 'رياضة'], $marquee);
    }

    public function test_the_ticker_is_cut_to_the_length_it_scrolls(): void
    {
        $country = Country::query()->create(['name' => 'الكويت']);

        foreach (range(1, LandingShowcaseService::MARQUEE_CATEGORIES + 4) as $i) {
            Category::query()->create(['name' => "فئة {$i}", 'country_id' => $country->id]);
        }

        $this->assertCount(
            LandingShowcaseService::MARQUEE_CATEGORIES,
            $this->getJson('/api/landing')->json('data.marquee')
        );
    }

    public function test_countries_carry_their_own_categories(): void
    {
        $kuwait = Country::query()->create(['name' => 'الكويت']);
        $qatar = Country::query()->create(['name' => 'قطر']);

        Category::query()->create(['name' => 'تاريخ الكويت', 'country_id' => $kuwait->id]);
        Category::query()->create(['name' => 'معالم قطر', 'country_id' => $qatar->id]);

        $countries = collect($this->getJson('/api/landing')->json('data.countries'))
            ->pluck('categories', 'name');

        $this->assertSame(['تاريخ الكويت'], $countries['الكويت']);
        $this->assertSame(['معالم قطر'], $countries['قطر']);
    }

    public function test_a_country_that_is_switched_off_or_empty_is_left_out(): void
    {
        $live = Country::query()->create(['name' => 'الكويت']);
        $hidden = Country::query()->create(['name' => 'قطر', 'is_active' => false]);
        Country::query()->create(['name' => 'السعودية']);

        Category::query()->create(['name' => 'تاريخ الكويت', 'country_id' => $live->id]);
        Category::query()->create(['name' => 'معالم قطر', 'country_id' => $hidden->id]);

        $names = array_column($this->getJson('/api/landing')->json('data.countries'), 'name');

        // السعودية has no categories, so it would render as an empty strip.
        $this->assertSame(['الكويت'], $names);
    }

    public function test_an_empty_database_falls_back_to_the_demo_the_bundle_shipped_with(): void
    {
        $response = $this->getJson('/api/landing');

        $response->assertOk();

        $board = $response->json('data.board');
        $this->assertSame(['جغرافيا', 'رياضة', 'علوم'], array_column($board, 'category'));
        $this->assertSame(['orange', 'green', 'blue'], array_column($board, 'tone'));
        $this->assertSame([200, 400, 600], array_column($board[0]['questions'], 'score'));
        $this->assertSame('ما عاصمة الكويت؟', $board[0]['questions'][0]['question']);
        $this->assertSame('مدينة الكويت', $board[0]['questions'][0]['answer']);

        $this->assertContains('أفلام ومسلسلات', $response->json('data.marquee'));

        $countries = array_column($response->json('data.countries'), 'name');
        sort($countries);
        $this->assertSame(['السعودية', 'الكويت', 'قطر'], $countries);
    }

    public function test_each_block_falls_back_on_its_own(): void
    {
        // Categories exist, but none has a full set of tiers, so only the board is
        // short — the real names must still reach the ticker and the country strip.
        $thin = $this->makeCategory('فئة حقيقية');
        $this->makeQuestion($thin, 200);

        $response = $this->getJson('/api/landing');

        $this->assertSame(['جغرافيا', 'رياضة', 'علوم'], array_column($response->json('data.board'), 'category'));
        $this->assertSame(['فئة حقيقية'], $response->json('data.marquee'));
        $this->assertSame(['الكويت'], array_column($response->json('data.countries'), 'name'));
    }

    public function test_an_english_visitor_is_not_shown_arabic_only_categories(): void
    {
        $country = Country::query()->create(['name' => 'الكويت']);
        Category::query()->create(['name' => 'جغرافيا', 'country_id' => $country->id, 'language' => Category::LANGUAGE_AR]);
        Category::query()->create(['name' => 'Geography', 'country_id' => $country->id, 'language' => Category::LANGUAGE_EN]);
        Category::query()->create(['name' => 'Sports', 'country_id' => $country->id, 'language' => Category::LANGUAGE_BOTH]);

        $marquee = $this->getJson('/api/landing', ['X-Locale' => 'en'])->json('data.marquee');

        sort($marquee);
        $this->assertSame(['Geography', 'Sports'], $marquee);
    }

    private function makeCategory(string $name): Category
    {
        return Category::query()->create([
            'name' => $name,
            'country_id' => Country::query()->firstOrCreate(['name' => 'الكويت'])->id,
        ]);
    }

    /**
     * A category with a question in every tier, so it can fill a board column.
     */
    private function makeFullCategory(string $name): Category
    {
        $category = $this->makeCategory($name);

        foreach (Category::SCORES as $score) {
            $this->makeQuestion($category, $score);
        }

        return $category;
    }

    private function makeQuestion(Category $category, int $score, ?string $text = null): Question
    {
        return Question::query()->create([
            'category_id' => $category->id,
            'question' => $text ?? "{$category->name} بـ {$score}؟",
            'answer' => "إجابة {$score}",
            'score' => $score,
        ]);
    }
}
