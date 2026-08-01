<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 題庫與轉盤也把「收費」從分級裡拆出來,跟真心話大冒險的卡片一致。
 *
 * 之前是分級名稱直接寫著「重度(付費)」「狂野(付費)」,也就是整級一起收費。
 * 但尺度講的是內容多直接,收費講的是商業界線 —— 中度裡也會有想留給付費的
 * 題目,重度裡也會有想放出來當樣品的。
 *
 * 沿用目前的界線做初始值:原本標著(付費)的那幾級設為付費,其餘免費。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('game_prompts', function (Blueprint $table) {
            $table->boolean('is_paid')->default(false)->after('pool');
            $table->index(['game', 'is_paid']);
        });

        // 原本掛著「(付費)」的池:三個遊戲的 intense,以及骰子的各種 .wild
        DB::table('game_prompts')
            ->where('pool', 'intense')
            ->orWhere('pool', 'like', '%.wild')
            ->update(['is_paid' => true]);

        Schema::table('wheel_segments', function (Blueprint $table) {
            $table->boolean('is_paid')->default(false)->after('tier');
            $table->index(['tier', 'is_paid']);
        });

        DB::table('wheel_segments')->where('tier', 'intense')->update(['is_paid' => true]);
    }

    public function down(): void
    {
        Schema::table('game_prompts', function (Blueprint $table) {
            $table->dropIndex(['game', 'is_paid']);
            $table->dropColumn('is_paid');
        });

        Schema::table('wheel_segments', function (Blueprint $table) {
            $table->dropIndex(['tier', 'is_paid']);
            $table->dropColumn('is_paid');
        });
    }
};
