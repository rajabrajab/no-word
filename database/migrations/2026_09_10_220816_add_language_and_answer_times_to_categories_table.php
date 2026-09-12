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
        Schema::table('categories', function (Blueprint $table) {
            $table->string('language', 10)->default('both')->after('description');

            $table->unsignedSmallInteger('answer_time_200')->default(90)->after('language');
            $table->unsignedSmallInteger('answer_time_400')->default(60)->after('answer_time_200');
            $table->unsignedSmallInteger('answer_time_600')->default(30)->after('answer_time_400');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn([
                'language',
                'answer_time_200',
                'answer_time_400',
                'answer_time_600',
            ]);
        });
    }
};
