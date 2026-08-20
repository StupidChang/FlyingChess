<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * 補上免費範本缺的 share_code,讓它們進得了索引。
 *
 * Board::isPubliclyIndexable() 規定「非預設棋盤一定要有 share_code」—— 沒有的話
 * 只能用數字網址打開,不在 sitemap 裡,頁面自己也標 noindex。但遊戲大廳有連結
 * 過去,所以 Googlebot 爬得到、卻只拿到一句「別收錄我」,在 Search Console 就是
 * 一筆「遭到 noindex 標記排除」。
 *
 * 缺碼的原因是 Board 的 creating hook 才會產碼,而這幾列是那個 hook 存在之前由
 * seeder 直接建的。新環境不會有這個問題(seeder 走 Eloquent,hook 會跑)。
 *
 * 只補**免費**範本。付費範本刻意留白:它們對沒付費的訪客(含 Googlebot)一律
 * 302 轉去付費頁,是 isPubliclyIndexable() 裡更前面那道 is_premium_template
 * 判斷擋掉的,給了 share_code 也還是 noindex —— 補了只是多發一組沒人用的碼。
 */
return new class extends Migration
{
    public function up(): void
    {
        $ids = DB::table('boards')
            ->where('is_template', true)
            ->where('is_premium_template', false)
            ->whereNull('share_code')
            ->pluck('id');

        foreach ($ids as $id) {
            DB::table('boards')->where('id', $id)->update([
                'share_code' => $this->uniqueCode(),
            ]);
        }
    }

    /**
     * 不可逆,而且是刻意的。
     *
     * share_code 一旦存在就是一個公開網址:它會進 sitemap、可能被人存成書籤或
     * 貼給朋友。把它抽掉會讓那些連結變成 404,而 rollback 這支 migration 的
     * 理由不可能值得那個代價。要讓某張範本退出索引的話,拿掉 is_template 或
     * 標成付費範本,不要動碼。
     */
    public function down(): void
    {
        // no-op
    }

    private function uniqueCode(): string
    {
        // 和 Board 的 creating hook 同一套規則:8 碼大寫、撞號重抽。
        for ($i = 0; $i < 10; $i++) {
            $code = strtoupper(Str::random(8));
            if (! DB::table('boards')->where('share_code', $code)->exists()) {
                return $code;
            }
        }

        throw new RuntimeException('Unable to generate unique share_code after 10 attempts');
    }
};
