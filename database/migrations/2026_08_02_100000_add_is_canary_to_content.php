<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 標記題(canary)。
 *
 * 用途不是防止被抄,是**抄了之後證明得了**。挑幾題寫成獨一無二的句子並標起來,
 * 之後在別人的站上搜到那幾句,就是可以直接拿去發 DMCA 的證據 —— 不用爭論
 * 「這種題目本來就大同小異」。
 *
 * 標記題本身就是正常題目,照樣會被抽到 —— 不然它永遠不會出現在送出去的內容裡,
 * 也就永遠不會被抄走,那就失去意義了。這個欄位只是讓後台找得到它們。
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['truth_dare_cards', 'game_prompts', 'wheel_segments'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->boolean('is_canary')->default(false);
            });
        }
    }

    public function down(): void
    {
        foreach (['truth_dare_cards', 'game_prompts', 'wheel_segments'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->dropColumn('is_canary');
            });
        }
    }
};
