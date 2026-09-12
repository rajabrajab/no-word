<?php

namespace App\Models;

use App\Models\Builders\BaseBuilder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

class HelpingMethod extends BaseModel
{
    use SoftDeletes;

    /**
     * Grants the team extra seconds on the question they are answering.
     */
    public const EXTRA_TIME = 'extra_time';

    /**
     * Swaps the current question for another of the same category and score.
     */
    public const CHANGE_QUESTION = 'change_question';

    /**
     * Surfaces the question's written hint.
     */
    public const ANSWER_HINT = 'answer_hint';

    /**
     * Reveals the correct answer outright.
     */
    public const REVEAL_ANSWER = 'reveal_answer';

    /**
     * Retired in favour of {@see self::EXTRA_TIME}; kept so historic
     * team_helping_methods rows still resolve to a name.
     */
    public const CALL_FRIEND = 'call_friend';

    /**
     * Display order of the live methods.
     *
     * @var array<string, int>
     */
    public const SORT_ORDER = [
        self::EXTRA_TIME => 1,
        self::CHANGE_QUESTION => 2,
        self::ANSWER_HINT => 3,
        self::REVEAL_ANSWER => 4,
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function newEloquentBuilder($query): BaseBuilder
    {
        return new BaseBuilder($query);
    }

    /**
     * The methods a team is offered, in the order they should be shown.
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    /**
     * The live set of helping methods, keyed for both the migration and the seeder.
     *
     * @return list<array{key: string, name: string, description: string, icon_file: ?string, sort_order: int}>
     */
    public static function defaults(): array
    {
        return [
            [
                'key' => self::EXTRA_TIME,
                'name' => 'زيادة وقت الإجابة',
                'description' => 'يمنح الفريق وقتاً إضافياً (30 ثانية) للإجابة على السؤال الحالي، مع استخدام واحد فقط لكل لعبة.',
                'icon_file' => 'extra_time.png',
                'sort_order' => self::SORT_ORDER[self::EXTRA_TIME],
            ],
            [
                'key' => self::CHANGE_QUESTION,
                'name' => 'تغيير السؤال',
                'description' => 'يسمح للفريق باستبدال السؤال الحالي بسؤال جديد من نفس الفئة، مع استخدام واحد فقط لكل لعبة.',
                'icon_file' => 'change_qustion.png',
                'sort_order' => self::SORT_ORDER[self::CHANGE_QUESTION],
            ],
            [
                'key' => self::ANSWER_HINT,
                'name' => 'تلميح عن الإجابة',
                'description' => 'يقدم تلميحاً نصياً أو بصرياً لتوجيه الفريق نحو الإجابة الصحيحة دون كشفها بالكامل.',
                'icon_file' => 'anwser_hint.png',
                'sort_order' => self::SORT_ORDER[self::ANSWER_HINT],
            ],
            [
                'key' => self::REVEAL_ANSWER,
                'name' => 'عطنا الإجابة',
                'description' => 'يكشف الإجابة الصحيحة للسؤال الحالي، مع استخدام واحد فقط لكل لعبة.',
                'icon_file' => 'reveal_answer.png',
                'sort_order' => self::SORT_ORDER[self::REVEAL_ANSWER],
            ],
        ];
    }
}
