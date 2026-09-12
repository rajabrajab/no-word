<?php

use App\Models\HelpingMethod;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Names the three original rows were seeded with, so existing installs can be
     * matched to a stable key instead of relying on their auto-increment ids.
     *
     * @var array<string, string>
     */
    private const LEGACY_NAMES = [
        'اتصال بصديق' => HelpingMethod::CALL_FRIEND,
        'تغيير السؤال' => HelpingMethod::CHANGE_QUESTION,
        'تلميح عن الإجابة' => HelpingMethod::ANSWER_HINT,
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('helping_methods', function (Blueprint $table) {
            $table->string('key')->nullable()->unique()->after('id');
            $table->unsignedTinyInteger('sort_order')->default(0)->after('key');
        });

        foreach (self::LEGACY_NAMES as $name => $key) {
            DB::table('helping_methods')
                ->where('name', $name)
                ->whereNull('key')
                ->update([
                    'key' => $key,
                    'sort_order' => HelpingMethod::SORT_ORDER[$key] ?? 0,
                ]);
        }

        foreach (HelpingMethod::defaults() as $default) {
            DB::table('helping_methods')->updateOrInsert(
                ['key' => $default['key']],
                [
                    'name' => $default['name'],
                    'description' => $default['description'],
                    'sort_order' => $default['sort_order'],
                    'deleted_at' => null,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        // Calling a friend is retired. Soft delete rather than remove it, so any
        // team_helping_methods row that already points at it stays resolvable.
        DB::table('helping_methods')
            ->where('key', HelpingMethod::CALL_FRIEND)
            ->whereNull('deleted_at')
            ->update(['deleted_at' => now()]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('helping_methods')
            ->whereIn('key', [HelpingMethod::EXTRA_TIME, HelpingMethod::REVEAL_ANSWER])
            ->delete();

        DB::table('helping_methods')
            ->where('key', HelpingMethod::CALL_FRIEND)
            ->update(['deleted_at' => null]);

        Schema::table('helping_methods', function (Blueprint $table) {
            $table->dropUnique(['key']);
            $table->dropColumn(['key', 'sort_order']);
        });
    }
};
