<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * 站台驗證檔。
 *
 * 這些檔案沒有畫面、沒有人會點,所以掉了不會有人發現 —— 直到某天發現
 * Search Console 或廣告後台把網站標成「未驗證」。deploy.sh 會 git reset --hard,
 * 所以它們必須留在版控裡;這支測試就是在確認它們還在、內容還對。
 */
class SiteVerificationTest extends TestCase
{
    public static function files(): array
    {
        return [
            // Google Search Console:內容固定就是 "google-site-verification: 檔名"
            'Google Search Console' => [
                'google947c90296236813b.html',
                'google-site-verification: google947c90296236813b.html',
            ],
            // ExoClick:內容是檔名去掉副檔名的那串雜湊
            'ExoClick' => [
                '31e11d6b2ba10a4e7666452a52016dbd.html',
                '31e11d6b2ba10a4e7666452a52016dbd',
            ],
        ];
    }

    #[DataProvider('files')]
    public function test_the_verification_file_is_still_there(string $file, string $expected): void
    {
        $path = public_path($file);

        $this->assertFileExists($path, "{$file} 不見了 —— 網站會被標成未驗證");
        $this->assertSame($expected, trim(file_get_contents($path)));
    }
}
