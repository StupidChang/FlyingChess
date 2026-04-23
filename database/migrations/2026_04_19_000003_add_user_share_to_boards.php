<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('boards', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')
                  ->constrained('users')->nullOnDelete();
            $table->string('share_code', 10)->nullable()->unique()->after('user_id');
        });

        // Backfill share_code for existing boards
        DB::table('boards')->whereNull('share_code')->get()->each(function ($board) {
            do {
                $code = strtoupper(Str::random(8));
            } while (DB::table('boards')->where('share_code', $code)->exists());

            DB::table('boards')->where('id', $board->id)->update(['share_code' => $code]);
        });
    }

    public function down(): void
    {
        Schema::table('boards', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn(['user_id', 'share_code']);
        });
    }
};
