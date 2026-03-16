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
        Schema::create('tournament_teams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')
                ->constrained('tournaments')
                ->cascadeOnDelete();
            $table->string('name');
            $table->foreignId('avatar_id')
                ->nullable()
                ->constrained('player_avatars')
                ->nullOnDelete();
            $table->integer('score')->default(0);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::table('tournaments', function (Blueprint $table) {
            $table->foreign('champion_id')
                ->references('id')
                ->on('tournament_teams')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::table('tournaments', function (Blueprint $table) {
            $table->dropForeign(['champion_id']);
        });
        Schema::dropIfExists('tournament_teams');
        Schema::enableForeignKeyConstraints();
    }
};
