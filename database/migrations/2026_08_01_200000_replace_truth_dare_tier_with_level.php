<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 真心話大冒險的卡片分級從「一般／18禁」換成「輕度／中度／重度」。
 *
 * 整個站都是成人向,所以「一般 vs 18禁」分不出東西 —— 免費的那批本來就寫著
 * 「成人向、曖昧級」,不是什麼普遍級。真正要分的是尺度輕重,而且要跟其他
 * 四個小遊戲的題庫用同一套詞彙(mild / medium / intense),不然同一個站裡
 * 兩種分級講法,後台跟玩家都要各記一套。
 *
 * 付費界線跟著題庫的慣例走:重度要付費或看廣告,輕度與中度人人有。
 * 界線本身寫在 TruthDareCard::PAID_LEVELS,要調整改那裡就好。
 */
return new class extends Migration
{
    /**
     * 原本 premium 那批裡,寫得最直接的那些。
     *
     * 只是把一次性的分類做完,不是什麼分類器 —— 分完之後單一事實來源就是
     * 資料表,後台可以逐題調整。
     */
    private const EXPLICIT = [
        '插入', '口交', '體位', '肛', '情趣', '高潮', '私密', '脫', '裸',
        '自慰', '自己解決', '做愛', '性愛', '玩具', '道具', '舔', '撫摸',
        '幾次', '叫', '潮', '束縛', '前戲',
    ];

    public function up(): void
    {
        /* 每一步都先確認還沒做過。第一次跑的時候 dropColumn 失敗了(SQLite 不讓
           你砍掉還被索引用著的欄位),遷移沒有被記錄下來,但欄位已經加好了 ——
           沒有這些檢查的話重跑就會卡在「duplicate column」,永遠補不完。 */
        if (! Schema::hasColumn('truth_dare_cards', 'level')) {
            Schema::table('truth_dare_cards', function (Blueprint $table) {
                $table->string('level', 16)->default('mild')->after('audience');
                $table->index(['category', 'level']);
            });
        }

        if (! Schema::hasColumn('truth_dare_cards', 'tier')) {
            return;   // 分類已經做完了
        }

        // 曖昧級 → 輕度
        DB::table('truth_dare_cards')->where('tier', 'free')->update(['level' => 'mild']);

        // 18+ 的先全部當中度,再把寫得最直接的挑成重度。
        DB::table('truth_dare_cards')->where('tier', 'premium')->update(['level' => 'medium']);

        DB::table('truth_dare_cards')
            ->where('level', 'medium')
            ->where(function ($q) {
                foreach (self::EXPLICIT as $word) {
                    $q->orWhere('content', 'like', "%{$word}%");
                }
            })
            ->update(['level' => 'intense']);

        if (Schema::hasColumn('truth_dare_cards', 'tier')) {
            // SQLite 不讓你砍掉還掛在索引上的欄位,舊索引要先拆掉。
            Schema::table('truth_dare_cards', function (Blueprint $table) {
                $table->dropIndex('truth_dare_cards_category_tier_index');
            });
            Schema::table('truth_dare_cards', function (Blueprint $table) {
                $table->dropColumn('tier');
            });
        }
    }

    public function down(): void
    {
        Schema::table('truth_dare_cards', function (Blueprint $table) {
            $table->string('tier', 16)->default('free')->after('audience');
        });

        DB::table('truth_dare_cards')->where('level', 'mild')->update(['tier' => 'free']);
        DB::table('truth_dare_cards')->where('level', '!=', 'mild')->update(['tier' => 'premium']);

        Schema::table('truth_dare_cards', function (Blueprint $table) {
            $table->dropIndex(['category', 'level']);
            $table->dropColumn('level');
        });
    }
};
