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
        Schema::create('policies_conditions', function (Blueprint $table) {
            $table->id();
            $table->json('title');
            $table->json('content');
            $table->date('last_updated');
            $table->enum('type', ['privacy_policy', 'terms_and_conditions'])->default('privacy_policy');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('privacy_policies');
    }
};
