<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 自己記的瀏覽紀錄。
 *
 * 站上沒有 GA4,而且成人站掛 Google 的分析本來就有帳號風險。這張表只回答一個
 * 問題:「人進來之後往哪裡去」—— 所以刻意只存路徑層級的資料。
 *
 * ⚠ 不存 IP,也不存 User-Agent 原文。`visitor_hash` 是 IP+UA+當天日期+APP_KEY
 * 算出來的雜湊,只夠用來算「當天不重複訪客」,隔天同一個人就算不同雜湊,
 * 沒辦法拿來追人。這是刻意的取捨:算得出趨勢就夠,不需要個資。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_views', function (Blueprint $table) {
            $table->id();

            // 去掉語系前綴後的路徑(/tw/games → /games),不然同一個頁面會被
            // 拆成四筆,看不出哪個頁面熱門。語系另外存一欄。
            $table->string('path', 191)->index();
            $table->string('locale', 8)->nullable();

            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->char('visitor_hash', 64)->index();

            // 只留來源網域,不留完整網址 —— query string 常常夾帶個資。
            $table->string('referer_host', 191)->nullable();

            $table->timestamp('created_at')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_views');
    }
};
