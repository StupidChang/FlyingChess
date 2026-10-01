<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 每張棋盤的推薦人數。2 = 一男一女(情侶適合),4 = 兩男兩女(多人適合)。
 *
 * 兩種題目寫法不同:情侶版寫「對方／另一半」,多人版寫「在場的異性／左邊的人」,
 * 兩人玩到多人版會卡在「找兩位異性」這種格子。播放頁預設就開這個人數。
 *
 * 內建的五張多人棋盤在這裡回填;seeder 也會寫同一份(見 BoardTemplateSeeder::GROUP_BOARDS),
 * 全新安裝不靠這支 migration。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('boards', function (Blueprint $table) {
            $table->unsignedTinyInteger('recommended_players')->default(2);
        });

        DB::table('boards')
            ->where('is_template', true)
            ->whereIn('name', [
                '飲酒派對版',
                '成人派對互動版',
                '極限派對挑戰版',
                '情侶互換飛行棋 V8.0（四人版）',
                '情侶飛行棋 V8.0 原版復刻（四人版）',
            ])
            ->update(['recommended_players' => 4]);
    }

    public function down(): void
    {
        Schema::table('boards', function (Blueprint $table) {
            $table->dropColumn('recommended_players');
        });
    }
};
