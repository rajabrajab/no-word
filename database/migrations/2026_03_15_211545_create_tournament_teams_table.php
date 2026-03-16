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
        Schema::table('tournaments', function (Blueprint $table) {
            $table->foreign('champion_id')
                ->references('id')
                ->on('teams')
                ->nullOnDelete();
        });

       Schema::dropIfExists('tournament_teams');
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
        Schema::enableForeignKeyConstraints();
    }
};
