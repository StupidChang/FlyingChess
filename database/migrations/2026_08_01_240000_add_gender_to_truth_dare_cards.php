<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 題目也分男／女／不限。
 *
 * 抽牌時看的是**輪到的那個人**的性別:男玩家只抽得到「不限」與「男」的題目。
 * 沒填性別的玩家不過濾 —— 不指定的人應該看得到全部,而不是只剩不限。
 *
 * 預設全部是「不限」,所以在後台把題目標起來之前,行為跟改版前一樣。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('truth_dare_cards', function (Blueprint $table) {
            $table->string('gender', 8)->default('any')->after('audience');
            $table->index(['category', 'gender']);
        });
    }

    public function down(): void
    {
        Schema::table('truth_dare_cards', function (Blueprint $table) {
            $table->dropIndex(['category', 'gender']);
            $table->dropColumn('gender');
        });
    }
};
