<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 站內回報。
 *
 * 在這之前唯一的回報管道是 SUPPORT_EMAIL —— 要開信箱、要打地址,而且使用者
 * 通常正在氣頭上或正好想到一個題目,那一步就足以讓他放棄。表單留在站內,
 * 順手就送得出來。
 *
 * 刻意不存 IP:abuse 由路由的 throttle:5,60 擋掉,而這是一個成人站,能少留
 * 一份可識別資料就少留一份。user_agent 留著,因為「按鈕在我手機上壞掉」這種
 * 回報沒有它幾乎查不動。
 *
 * contact 是自由文字而不是 email 欄位:很多人只想留 IG 或 LINE,強迫填 email
 * 只會讓他們乾脆留空,那我們就完全回不了了。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feedback', function (Blueprint $table) {
            $table->id();
            $table->string('type', 16);                  // bug|prompt|feature|other
            $table->text('message');
            $table->string('contact', 120)->nullable();   // email / IG / LINE,選填
            $table->string('page_path', 200)->nullable(); // 從哪一頁按過來的(站內相對路徑)
            $table->string('locale', 8)->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('user_agent', 255)->nullable();
            $table->string('status', 16)->default('new'); // new|doing|done|spam
            $table->timestamps();

            // 後台預設就是「未處理的、最新的在上面」
            $table->index(['status', 'created_at']);
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feedback');
    }
};
