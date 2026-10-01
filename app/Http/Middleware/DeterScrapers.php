<?php

namespace App\Http\Middleware;

use App\Support\VerifiedCrawler;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

/**
 * 讓整站被扒走變貴,但不碰搜尋引擎。
 *
 * 公開的文字擋不住真的想抄的人 —— Google 讀得到的,任何人都讀得到。這裡做的是
 * 兩件提高成本的事:
 *
 *   1. 明擺著是爬站工具的 UA(python-requests、Scrapy、HTTrack…)直接 403。
 *      真人的瀏覽器、搜尋引擎、社群預覽(facebookexternalhit 之類)都不會是這些。
 *   2. 同一個 IP 一分鐘最多 PAGES_PER_MINUTE 個頁面。真人作答翻頁、逛大廳遠遠
 *      用不到;一次扒完整站的程式會撞到。**經過驗證的** Googlebot / Bingbot 不算
 *      (見 VerifiedCrawler)—— 只看 UA 的話,改個 UA 就繞過去了。
 *
 * 只管 GET 的頁面。機器對機器的端點(健康檢查、SNS 回呼、金流回呼、sitemap、
 * 分享卡片圖)不經過這裡的判斷。更強的那一層在 Cloudflare。
 */
class DeterScrapers
{
    public const PAGES_PER_MINUTE = 120;

    private const SCRAPER_UA = '/python-requests|python-urllib|aiohttp|httpx|scrapy|httrack|wget|libwww-perl|'
        .'go-http-client|okhttp|java\/|axios|node-fetch|got \(|php-curl|guzzlehttp|mechanize|'
        .'phantomjs|headlesschrome|puppeteer|playwright|selenium|webcopier|offline explorer|'
        .'sitesucker|teleport ?pro|webzip|webripper|grab-site|colly|crawler4j/i';

    private const EXEMPT_PATHS = '#^(up|ads\.txt|robots\.txt|llms\.txt|sitemap.*\.xml|ses/feedback|premium/(callback|result)|.*\.png)$#';

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethod('GET') || preg_match(self::EXEMPT_PATHS, $request->path())) {
            return $next($request);
        }

        if (preg_match(self::SCRAPER_UA, (string) $request->userAgent())) {
            return response('403 Forbidden', 403, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        // 輪詢與 JSON 端點有自己的節流,不算頁面
        if ($request->expectsJson() || str_ends_with($request->path(), '/state')) {
            return $next($request);
        }

        $key = 'pages|'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, self::PAGES_PER_MINUTE) && ! VerifiedCrawler::check($request)) {
            return response('429 Too Many Requests', 429, [
                'Content-Type' => 'text/plain; charset=utf-8',
                'Retry-After' => (string) RateLimiter::availableIn($key),
            ]);
        }
        RateLimiter::hit($key, 60);

        return $next($request);
    }
}
