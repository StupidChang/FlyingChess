<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 玩家的性別。
 *
 * 情侶撲克牌一直有這個設定(它要湊男女配對),但那是純前端的,沒有存下來。
 * 真心話大冒險的題目裡有「讓大家指定一位異性…」這類指令,桌上看不出誰是誰
 * 的話那題就沒辦法玩,所以這裡把它存進玩家列並在遊戲中顯示。
 *
 * 可以不填 —— 不是每一桌都想標這個,也不是每個人都想被標。沒填就不顯示。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('game_players', function (Blueprint $table) {
            $table->string('gender', 8)->nullable()->after('player_name');
        });
    }

    public function down(): void
    {
        Schema::table('game_players', function (Blueprint $table) {
            $table->dropColumn('gender');
        });
    }
};
