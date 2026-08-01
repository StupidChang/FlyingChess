<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 記下每一場是從哪裡開的。
 *
 * 後台看到一場訪客開的遊戲時,除了訪客代號之外還想知道「他是從哪來的」。
 * 與 page_views 一樣只存 referer 的主機名與語系,不存完整網址、IP 或 UA。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->string('origin_locale', 8)->nullable()->after('game_state');
            $table->string('origin_referer', 191)->nullable()->after('origin_locale');
        });
    }

    public function down(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->dropColumn(['origin_locale', 'origin_referer']);
        });
    }
};
