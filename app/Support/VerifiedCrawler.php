<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * 「這個請求真的是搜尋引擎的爬蟲」—— 不是只看 User-Agent。
 *
 * UA 誰都能填。爬站的人第一件事就是把 UA 改成 Googlebot,所以任何「爬蟲可以
 * 不受限」的規則只看 UA 的話,等於對抄站的人開了後門。
 *
 * 驗證方式是 Google 與 Bing 官方文件寫的那一套:IP 反查網域 → 網域必須是它們的 →
 * 再正查回同一個 IP。結果以 IP 快取一天,DNS 只有第一次會查。
 */
final class VerifiedCrawler
{
    /** UA 子字串 => 反查網域必須結尾的後綴 */
    private const BOTS = [
        'Googlebot' => ['.googlebot.com', '.google.com', '.googleusercontent.com'],
        'Google-InspectionTool' => ['.googlebot.com', '.google.com'],
        'bingbot' => ['.search.msn.com'],
    ];

    public static function check(Request $request): bool
    {
        $ua = (string) $request->userAgent();
        $suffixes = null;
        foreach (self::BOTS as $needle => $allowed) {
            if (stripos($ua, $needle) !== false) {
                $suffixes = $allowed;
                break;
            }
        }
        if ($suffixes === null) {
            return false;
        }

        $ip = (string) $request->ip();

        return Cache::remember('verified-crawler:'.$ip, now()->addDay(), function () use ($ip, $suffixes) {
            $host = @gethostbyaddr($ip);
            if (! $host || $host === $ip) {
                return false;
            }
            $host = strtolower($host);
            $owned = false;
            foreach ($suffixes as $suffix) {
                if (str_ends_with($host, $suffix)) {
                    $owned = true;
                    break;
                }
            }

            // 正查回來要是同一個 IP,不然反查記錄是對方自己填的
            return $owned && in_array($ip, (array) @gethostbynamel($host), true);
        });
    }
}
