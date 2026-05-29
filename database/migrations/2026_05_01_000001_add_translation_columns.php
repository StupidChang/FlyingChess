<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // truth_dare_cards.content → content_translations
        Schema::table('truth_dare_cards', function (Blueprint $table) {
            $table->json('content_translations')->nullable()->after('content');
            $table->timestamp('machine_translated_at')->nullable()->after('content_translations');
        });
        DB::table('truth_dare_cards')->orderBy('id')->each(function ($row) {
            DB::table('truth_dare_cards')->where('id', $row->id)->update([
                'content_translations' => json_encode(['zh_TW' => $row->content], JSON_UNESCAPED_UNICODE),
            ]);
        });

        // board_squares.text → text_translations
        Schema::table('board_squares', function (Blueprint $table) {
            $table->json('text_translations')->nullable()->after('text');
            $table->timestamp('machine_translated_at')->nullable()->after('text_translations');
        });
        DB::table('board_squares')->orderBy('id')->each(function ($row) {
            DB::table('board_squares')->where('id', $row->id)->update([
                'text_translations' => json_encode(['zh_TW' => (string) $row->text], JSON_UNESCAPED_UNICODE),
            ]);
        });

        // wheel_segments.content → content_translations
        Schema::table('wheel_segments', function (Blueprint $table) {
            $table->json('content_translations')->nullable()->after('content');
            $table->timestamp('machine_translated_at')->nullable()->after('content_translations');
        });
        DB::table('wheel_segments')->orderBy('id')->each(function ($row) {
            DB::table('wheel_segments')->where('id', $row->id)->update([
                'content_translations' => json_encode(['zh_TW' => $row->content], JSON_UNESCAPED_UNICODE),
            ]);
        });

        // boards.name → name_translations
        Schema::table('boards', function (Blueprint $table) {
            $table->json('name_translations')->nullable()->after('name');
            $table->timestamp('machine_translated_at')->nullable()->after('name_translations');
        });
        DB::table('boards')->orderBy('id')->each(function ($row) {
            DB::table('boards')->where('id', $row->id)->update([
                'name_translations' => json_encode(['zh_TW' => $row->name], JSON_UNESCAPED_UNICODE),
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('truth_dare_cards', function (Blueprint $table) {
            $table->dropColumn(['content_translations', 'machine_translated_at']);
        });
        Schema::table('board_squares', function (Blueprint $table) {
            $table->dropColumn(['text_translations', 'machine_translated_at']);
        });
        Schema::table('wheel_segments', function (Blueprint $table) {
            $table->dropColumn(['content_translations', 'machine_translated_at']);
        });
        Schema::table('boards', function (Blueprint $table) {
            $table->dropColumn(['name_translations', 'machine_translated_at']);
        });
    }
};
