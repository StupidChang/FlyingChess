<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 四個小遊戲的題庫。
 *
 * 誰最有可能、情侶撲克牌、國王遊戲、骰子的內容本來全寫死在 Service 的常數裡,
 * 只有真心話卡片與轉盤段落進得了後台 —— 同一件事有兩套做法,而寫死的那四個
 * 每次調整內容都要動程式碼、重新部署。
 *
 * 一張表裝四個遊戲而不是四張表:欄位完全一樣(屬於哪個遊戲、哪個分級、內容),
 * 拆開只會讓後台也要跟著長出四頁一模一樣的 CRUD。
 *
 * pool 是「分級或骰面類別」:
 *   誰最有可能 / 撲克牌 / 國王   mild | medium | intense
 *   骰子                        action.gentle、part.wild、prop.gentle、play.wild、time…
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_prompts', function (Blueprint $table) {
            $table->id();
            $table->string('game', 24);
            $table->string('pool', 24);
            $table->string('content', 200);
            // 後台排序用。同一池內由小到大,相同時退回 id。
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['game', 'pool']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_prompts');
    }
};
