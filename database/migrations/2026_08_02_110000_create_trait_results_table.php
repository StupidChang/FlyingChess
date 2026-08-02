<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 屬性測驗的紀錄。
 *
 * 只存登入使用者的 —— 個人資料頁的時間軸是這張表唯一的用途,沒有帳號就沒有
 * 地方顯示。沒登入的人一樣測得到,只是結果看完就沒了。
 *
 * 不存原始作答,只存算完的分數:重算的需求不存在(權重改了,舊分數本來就
 * 不該被改寫成新的),而作答內容是這個站上最私密的資料之一,不留最安全。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trait_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('top_trait', 32);
            $table->json('traits');   // [{key, pct}, …] 由高到低
            $table->json('axes');     // {DS: -8…8, …}
            $table->timestamps();

            // 時間軸永遠是「某人的、依時間排序」
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trait_results');
    }
};
