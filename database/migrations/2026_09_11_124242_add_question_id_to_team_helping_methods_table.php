<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('team_helping_methods', function (Blueprint $table) {
            // Which question the help was spent on. Needed so revealing an answer
            // can forfeit that question's points for this team, and left nullable
            // because a help may be spent without naming a question.
            $table->foreignId('question_id')
                ->nullable()
                ->after('helping_method_id')
                ->constrained('questions')
                ->nullOnDelete();

            $table->index(['team_id', 'question_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('team_helping_methods', function (Blueprint $table) {
            $table->dropIndex(['team_id', 'question_id']);
            $table->dropConstrainedForeignId('question_id');
        });
    }
};
