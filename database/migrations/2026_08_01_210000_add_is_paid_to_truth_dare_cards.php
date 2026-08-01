<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 付費與否改成每張卡片自己的欄位,不再由尺度推導。
 *
 * 原本是「重度 = 付費」一條規則。但尺度講的是內容多直接,付費講的是商業界線,
 * 兩件事不一定重疊 —— 中度裡也會有想留給付費的題目,而把它整級變成付費又太多。
 * 分開之後 level 只管尺度(升溫的階梯照樣用它),is_paid 只管收費。
 *
 * 沿用目前的界線做初始值:重度付費、其餘免費。之後在後台逐題調整。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('truth_dare_cards', function (Blueprint $table) {
            $table->boolean('is_paid')->default(false)->after('level');
            $table->index(['category', 'is_paid']);
        });

        DB::table('truth_dare_cards')->where('level', 'intense')->update(['is_paid' => true]);
    }

    public function down(): void
    {
        Schema::table('truth_dare_cards', function (Blueprint $table) {
            $table->dropIndex(['category', 'is_paid']);
            $table->dropColumn('is_paid');
        });
    }
};
