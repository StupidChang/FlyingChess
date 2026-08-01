<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 把真心話大冒險的「類型」與「人數」拆成兩個軸。
 *
 * 原本 category 有四個值:truth / dare / couple / party。那是兩個軸被壓成一個 ——
 * truth 與 dare 講的是題目類型,couple 與 party 講的是幾個人在玩。壓在一起的結果是
 * 題目對不上場合:dare 的 24 張裡有 23 張寫著「另一半」,三五好友玩的時候按下
 * 「大冒險」會抽到「在另一半耳邊吹一口氣」;反過來情侶按「派對」會抽到
 * 「對在場你覺得最性感的人放電」。
 *
 * 拆完之後 category 只剩 truth / dare,人數放在 audience:
 *   both   兩種場合都合用(沒有指名對象的題目)
 *   couple 情侶(兩人)
 *   party  多人
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('truth_dare_cards', function (Blueprint $table) {
            $table->string('audience', 16)->default('both')->after('category');
            $table->index(['category', 'audience']);
        });

        /* 順序有意義:dare 要在 party 改名成 dare 之前先標成 couple,
           不然 party 的題目會被一起標成情侶。 */

        // couple 原本裝的是情侶版真心話(說出…、回憶…、告訴對方…)
        DB::table('truth_dare_cards')->where('category', 'couple')
            ->update(['category' => 'truth', 'audience' => 'couple']);

        // dare 實際上是情侶版大冒險
        DB::table('truth_dare_cards')->where('category', 'dare')
            ->update(['audience' => 'couple']);

        // party 是多人版大冒險
        DB::table('truth_dare_cards')->where('category', 'party')
            ->update(['category' => 'dare', 'audience' => 'party']);

        /* 剩下的 truth 多半沒有指名對象,兩種場合都能用;有提到另一半／對方的
           那幾張留給情侶場。 */
        DB::table('truth_dare_cards')
            ->where('category', 'truth')
            ->where('audience', 'both')
            ->where(function ($q) {
                $q->where('content', 'like', '%另一半%')
                    ->orWhere('content', 'like', '%對方%');
            })
            ->update(['audience' => 'couple']);
    }

    public function down(): void
    {
        DB::table('truth_dare_cards')->where('category', 'truth')->where('audience', 'couple')
            ->update(['category' => 'couple']);
        DB::table('truth_dare_cards')->where('category', 'dare')->where('audience', 'party')
            ->update(['category' => 'party']);

        Schema::table('truth_dare_cards', function (Blueprint $table) {
            $table->dropIndex(['category', 'audience']);
            $table->dropColumn('audience');
        });
    }
};
