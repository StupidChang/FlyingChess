<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * 通用的累計計數表。第一個用途是「你是第 N 位做完屬性測驗的人」。
 *
 * 為什麼不能拿 trait_results 去 count():那張表**只存登入者**的結果(匿名的人
 * 沒有地方顯示時間軸,所以刻意不存),用它算人數會漏掉大多數受測者。
 *
 * 為什麼不用 settings 表:那張表的讀取有一小時快取(見 App\Models\Setting),
 * 計數器每次都要拿到剛加完的真值,兩者的需求正好相反。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('counters', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->unsignedBigInteger('count')->default(0);
            $table->timestamps();
        });

        /* 起始值用已經存下來的登入者結果筆數。這是**真實下限**而不是漂亮的假
           基數 —— 匿名的那些補不回來,但寧可少算也不要憑空生一個數字出來。 */
        $seed = Schema::hasTable('trait_results')
            ? (int) DB::table('trait_results')->count()
            : 0;

        DB::table('counters')->insert([
            'key' => 'trait_test_completions',
            'count' => $seed,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('counters');
    }
};
