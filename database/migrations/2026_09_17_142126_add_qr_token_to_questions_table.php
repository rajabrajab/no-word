<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The public question page used to be addressed by the question's id, so anyone
     * holding one QR code could walk the ids and read every question and answer.
     * The page is addressed by this unguessable token instead.
     */
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->char('qr_token', 32)->nullable()->unique()->after('qr_code');
        });

        DB::table('questions')
            ->select('id')
            ->orderBy('id')
            ->chunk(500, function ($questions): void {
                foreach ($questions as $question) {
                    DB::table('questions')
                        ->where('id', $question->id)
                        ->update(['qr_token' => bin2hex(random_bytes(16))]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropUnique(['qr_token']);
            $table->dropColumn('qr_token');
        });
    }
};
