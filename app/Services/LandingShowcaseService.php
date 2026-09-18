<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Country;
use App\Models\Question;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Picks the sample of real categories and questions the public landing page shows
 * in place of the hand-written examples its bundle used to ship with.
 *
 * Every pick is fresh per request, so the marketing page advertises the question
 * bank as it actually stands rather than a snapshot that ages out of it.
 *
 * Each of the three blocks falls back to the demo the bundle shipped with when the
 * database has nothing to fill it: an unseeded install, a staging copy, or a country
 * whose categories are not written yet must still render a finished-looking page
 * rather than an empty board. The blocks fall back one at a time, so a real ticker is
 * not thrown away just because no category can fill a board column yet.
 */
class LandingShowcaseService
{
    /**
     * Columns the demo board has room for.
     */
    public const BOARD_CATEGORIES = 3;

    /**
     * Names the scrolling ticker shows before it repeats.
     */
    public const MARQUEE_CATEGORIES = 8;

    /**
     * Names shown under each country.
     */
    public const COUNTRY_CATEGORIES = 8;

    /**
     * Countries the strip has buttons for.
     *
     * The bundle lays these out as one row of radio buttons and shipped with three.
     * Every country in the table would wrap that row into a wall, so the strip is cut
     * to the width the design was drawn for — raise this only alongside the CSS.
     */
    public const COUNTRIES = 3;

    /**
     * The board the landing bundle shipped with, used when no category can fill one.
     *
     * @var array<string, array<int, array{string, string}>>
     */
    private const DEMO_BOARD = [
        'جغرافيا' => [
            200 => ['ما عاصمة الكويت؟', 'مدينة الكويت'],
            400 => ['على أي بحر تطل مدينة جدة؟', 'البحر الأحمر'],
            600 => ['ما أطول نهر في العالم العربي؟', 'نهر النيل'],
        ],
        'رياضة' => [
            200 => ['كم لاعباً لكل فريق داخل ملعب كرة القدم؟', '11 لاعباً'],
            400 => ['في أي دولة أقيمت كأس العالم 2022؟', 'قطر'],
            600 => ['كم دقيقة مدة الربع في مباريات كرة السلة الدولية؟', '10 دقائق'],
        ],
        'علوم' => [
            200 => ['ما أقرب كوكب إلى الشمس؟', 'عطارد'],
            400 => ['ما الرمز الكيميائي للذهب؟', 'Au'],
            600 => ['كم عظمة في جسم الإنسان البالغ؟', '206 عظمة'],
        ],
    ];

    /**
     * @var list<string>
     */
    private const DEMO_MARQUEE = [
        'تاريخ', 'جغرافيا', 'رياضة', 'أفلام ومسلسلات', 'أكلات شعبية', 'علوم', 'أمثال', 'مشاهير',
    ];

    /**
     * @var array<string, list<string>>
     */
    private const DEMO_COUNTRIES = [
        'الكويت' => ['تاريخ الكويت', 'أكلات شعبية', 'مسلسلات خليجية', 'رياضة', 'جغرافيا', 'أمثال كويتية', 'علوم', 'مشاهير'],
        'قطر' => ['معالم قطر', 'كأس العالم', 'تاريخ', 'علوم', 'تراث قطري', 'جغرافيا', 'رياضة', 'أفلام'],
        'السعودية' => ['تاريخ المملكة', 'أكلات شعبية', 'جغرافيا', 'رياضة', 'شعر ونبط', 'علوم', 'مسلسلات', 'معالم'],
    ];

    /**
     * @return array{
     *     board: list<array{category: Category, questions: Collection<int, Question>}>,
     *     marquee: list<string>,
     *     countries: Collection<int, Country>,
     * }
     */
    public function showcase(): array
    {
        $board = $this->board();
        $marquee = $this->marquee();
        $countries = $this->countries();

        return [
            'board' => $board !== [] ? $board : $this->demoBoard(),
            'marquee' => $marquee !== [] ? $marquee : self::DEMO_MARQUEE,
            'countries' => $countries->isNotEmpty() ? $countries : $this->demoCountries(),
        ];
    }

    /**
     * The shipped board, as models that are never saved.
     *
     * Handing the fallback back in the same shape as a real pick leaves the resource
     * one thing to render, rather than a second shape to special-case on the way out.
     *
     * @return list<array{category: Category, questions: Collection<int, Question>}>
     */
    private function demoBoard(): array
    {
        return collect(self::DEMO_BOARD)
            ->map(fn (array $questions, string $name): array => [
                'category' => new Category(['name' => $name]),
                'questions' => collect($questions)
                    ->map(fn (array $pair, int $score): Question => new Question([
                        'score' => $score,
                        'question' => $pair[0],
                        'answer' => $pair[1],
                    ]))
                    ->values(),
            ])
            ->values()
            ->all();
    }

    /**
     * @return Collection<int, Country>
     */
    private function demoCountries(): Collection
    {
        return collect(self::DEMO_COUNTRIES)
            ->map(fn (array $categories, string $name): Country => (new Country(['name' => $name]))->setRelation(
                'categories',
                collect($categories)->map(fn (string $category): Category => new Category(['name' => $category])),
            ))
            ->values();
    }

    /**
     * Categories that can fill a whole board column, each with one question per tier.
     *
     * A category short of a tier is passed over rather than shown with a dead square:
     * the board is the page's "this is how it plays" demo, so a hole in it reads as a
     * broken game rather than a thin category.
     *
     * @return list<array{category: Category, questions: Collection<int, Question>}>
     */
    private function board(): array
    {
        $categories = Category::query()
            ->byLanguage(app()->getLocale())
            ->tap(function (Builder $query): void {
                foreach (Category::SCORES as $score) {
                    $query->whereHas('questions', fn (Builder $questions) => $questions->where('score', $score));
                }
            })
            ->get(['id', 'name', 'country_id'])
            // Shuffled before the names are deduplicated, so both which name wins and
            // which country's copy of it is drawn stay random. Deduplicating matters:
            // every country runs its own "الفنون", and three columns picked purely at
            // random will often be the same word twice, which reads as a broken board.
            ->shuffle()
            ->unique(fn (Category $category): string => mb_strtolower(trim((string) $category->name)))
            ->take(self::BOARD_CATEGORIES)
            ->values();

        if ($categories->isEmpty()) {
            return [];
        }

        // One query for every question the chosen columns could use, shuffled by the
        // database, so taking the first row of a tier is already a random pick and
        // the board costs two queries instead of one per square.
        $questions = Question::query()
            ->whereIn('category_id', $categories->modelKeys())
            ->whereIn('score', Category::SCORES)
            ->inRandomOrder()
            ->get(['id', 'category_id', 'question', 'answer', 'score'])
            ->groupBy('category_id');

        return $categories
            ->map(fn (Category $category): array => [
                'category' => $category,
                'questions' => $this->oneQuestionPerScore($questions->get($category->id) ?? collect()),
            ])
            ->all();
    }

    /**
     * @param  Collection<int, Question>  $questions  Already in random order.
     * @return Collection<int, Question>
     */
    private function oneQuestionPerScore(Collection $questions): Collection
    {
        return collect(Category::SCORES)
            ->map(fn (int $score): ?Question => $questions->firstWhere('score', $score))
            ->filter()
            ->values();
    }

    /**
     * Distinct category names for the ticker.
     *
     * Names repeat across countries — every country has its own "جغرافيا" — so the
     * list is deduplicated before it is cut down, or the ticker scrolls the same word
     * past three times.
     *
     * @return list<string>
     */
    private function marquee(): array
    {
        return Category::query()
            ->byLanguage(app()->getLocale())
            ->pluck('name')
            ->map(fn (string $name): string => trim($name))
            ->filter()
            ->unique()
            ->shuffle()
            ->take(self::MARQUEE_CATEGORIES)
            ->values()
            ->all();
    }

    /**
     * Active countries that actually have categories to show under them.
     *
     * @return Collection<int, Country>
     */
    private function countries(): Collection
    {
        return Country::query()
            ->where('is_active', true)
            ->whereHas('categories', fn (Builder $categories) => $categories->byLanguage(app()->getLocale()))
            ->with([
                'categories' => fn ($categories) => $categories
                    ->byLanguage(app()->getLocale())
                    ->select(['id', 'name', 'country_id']),
            ])
            // Taken in the table's own order rather than at random: this strip is a
            // picker the visitor clicks through, and a set that reshuffled on every
            // reload would read as a glitch rather than as variety.
            ->limit(self::COUNTRIES)
            ->get();
    }
}
