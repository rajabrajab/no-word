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
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            $table->string('name');

            $table->string('number');
            $table->string('country_code')->nullable();
            $table->string('iso_code')->nullable();
            $table->string('normalized')->nullable();


            $table->string('profile_image')
                ->nullable();

            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');

            $table->enum('type', ['admin', 'user'])
                ->default('admin');

            $table->string('fcm')->nullable();
            $table->string('language')->nullable();

            $table->timestamp('joined_at')->nullable();
            $table->boolean('is_blocked')->default(false);

            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });


        Schema::create('password_resets', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('otp');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
