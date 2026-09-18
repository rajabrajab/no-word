<?php

namespace App\Http\Resources;

use App\Models\Category;
use App\Models\Country;
use App\Models\Question;
use App\Services\LandingShowcaseService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The landing page's three sample blocks: the demo board, the category ticker, and
 * the categories grouped under each country.
 *
 * Every list here is a list. A map keyed by score — 200, 400, 600 — looks tidier but
 * JsonResource runs array_values() over any nested array whose keys are all numeric,
 * which silently renumbers the tiers to 0, 1, 2 on the way out.
 */
class LandingShowcaseResource extends JsonResource
{
    /**
     * Column colours the landing bundle styles as `board__col--{tone}`. The database
     * has no colour of its own, so the board is painted by position and the three
     * columns stay as distinct as the hand-written demo they replaced.
     */
    private const TONES = ['orange', 'green', 'blue'];

    /**
     * @return array{board: list<mixed>, marquee: list<string>, countries: list<mixed>}
     */
    public function toArray(Request $request): array
    {
        return [
            'board' => $this->board(),
            'marquee' => $this->resource['marquee'],
            'countries' => $this->countries(),
        ];
    }

    /**
     * @return list<array{category: string, tone: string, questions: list<array{score: int, question: string, answer: string}>}>
     */
    private function board(): array
    {
        return collect($this->resource['board'])
            ->values()
            ->map(fn (array $column, int $index): array => [
                'category' => $column['category']->name,
                'tone' => self::TONES[$index % count(self::TONES)],
                'questions' => $column['questions']
                    ->map(fn (Question $question): array => [
                        'score' => (int) $question->score,
                        'question' => $question->question,
                        'answer' => $question->answer,
                    ])
                    ->values()
                    ->all(),
            ])
            ->all();
    }

    /**
     * @return list<array{name: string, categories: list<string>}>
     */
    private function countries(): array
    {
        return collect($this->resource['countries'])
            ->map(fn (Country $country): array => [
                'name' => $country->name,
                'categories' => $country->categories
                    ->map(fn (Category $category): string => trim((string) $category->name))
                    ->filter()
                    ->unique()
                    ->shuffle()
                    ->take(LandingShowcaseService::COUNTRY_CATEGORIES)
                    ->values()
                    ->all(),
            ])
            ->values()
            ->all();
    }
}
