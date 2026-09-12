<?php

namespace App\Models;

use App\Models\Builders\BaseBuilder;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends BaseModel
{
    use SoftDeletes;

    public const LANGUAGE_AR = 'ar';

    public const LANGUAGE_EN = 'en';

    public const LANGUAGE_BOTH = 'both';

    public const LANGUAGES = [
        self::LANGUAGE_AR,
        self::LANGUAGE_EN,
        self::LANGUAGE_BOTH,
    ];

    /**
     * The score tiers every category's questions are split across.
     */
    public const SCORES = [200, 400, 600];

    /**
     * Mirrors the column defaults so an unsaved category reports its timers too.
     */
    protected $attributes = [
        'language' => self::LANGUAGE_BOTH,
        'answer_time_200' => 90,
        'answer_time_400' => 60,
        'answer_time_600' => 30,
    ];

    protected function casts(): array
    {
        return [
            'answer_time_200' => 'integer',
            'answer_time_400' => 'integer',
            'answer_time_600' => 'integer',
        ];
    }

    public function newEloquentBuilder($query): BaseBuilder
    {
        return new BaseBuilder($query);
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function questions()
    {
        return $this->hasMany(Question::class);
    }

    public function scopeByCountry($query, $countryId = null)
    {
        return $query->when($countryId, function ($q) use ($countryId) {
            $q->where('country_id', $countryId);
        });
    }

    /**
     * Asking for a single language also returns the bilingual categories.
     * Anything else (including "both" or a missing value) returns everything.
     */
    public function scopeByLanguage($query, $language = null)
    {
        if (! in_array($language, [self::LANGUAGE_AR, self::LANGUAGE_EN], true)) {
            return $query;
        }

        return $query->whereIn('language', [$language, self::LANGUAGE_BOTH]);
    }

    /**
     * Seconds allowed to answer a question worth the given score.
     */
    public function answerTimeForScore($score): ?int
    {
        return match ((int) $score) {
            200 => $this->answer_time_200,
            400 => $this->answer_time_400,
            600 => $this->answer_time_600,
            default => null,
        };
    }

    /**
     * @return array<int, int> score => seconds
     */
    public function answerTimes(): array
    {
        return array_combine(
            self::SCORES,
            array_map(fn ($score) => $this->answerTimeForScore($score), self::SCORES)
        );
    }
}
