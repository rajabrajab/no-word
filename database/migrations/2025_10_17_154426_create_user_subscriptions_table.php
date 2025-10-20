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
        Schema::create('user_subscriptions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                  ->constrained()
                  ->cascadeOnUpdate()
                  ->cascadeOnDelete();

            $table->foreignId('package_id')
                  ->constrained('packages')
                  ->cascadeOnUpdate()
                  ->cascadeOnDelete();

            $table->dateTime('started_at')->useCurrent();
            $table->dateTime('ends_at')->nullable();
            $table->string('status')->default('active');

            $table->unique('user_id');

            $table->timestamps();

            $table->index(['package_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_subscriptions');
    }
};
