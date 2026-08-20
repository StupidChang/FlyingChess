<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * 讓撞名的範本改名,和預設棋盤區隔開。
 *
 * 站上有兩張都叫「輕度暖身版」的棋盤:BoardSeeder 建的預設棋盤(44 格)與
 * BoardTemplateSeeder 建的範本(32 格)。棋盤頁的 <title> 完全來自 name,所以
 * 兩頁的標題一模一樣 —— 兩個可索引的頁面共用一個標題,等於自己跟自己搶排名,
 * 而 Google 通常只會收錄其中一個。
 *
 * BoardTemplateSeeder 的 updateOrCreate 是用 ['name', 'is_template'] 當 key,
 * 光改 seeder 的字串會在既有環境「多建一張新的」而把舊的留在原地。所以這支
 * migration 負責把現有那一列改名,讓下一次 seed 對得上、就地更新而不是新增。
 *
 * 只動範本那一張,不動預設棋盤 —— 預設棋盤的網址是 /play,是站上最重要的頁面。
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('boards')
            ->where('name', '輕度暖身版')
            ->where('is_template', true)
            ->where('is_default', false)
            ->update(['name' => '浪漫暖身版']);
    }

    public function down(): void
    {
        DB::table('boards')
            ->where('name', '浪漫暖身版')
            ->where('is_template', true)
            ->update(['name' => '輕度暖身版']);
    }
};
