<?php

namespace App\Http\Middleware;

use App\Models\PageView;
use App\Support\LocaleHelper;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * 記錄一次頁面瀏覽,給後台的流量頁用。
 *
 * 為什麼自己記而不用 GA4:成人站掛 Google Analytics 有帳號風險(同一個 Google
 * 帳號下的其他服務會被連坐),而我們要回答的問題其實很單純 ——「人進來之後
 * 往哪裡去、在哪一步不見了」。這種站內動線用自己的資料反而更準,不會被
 * 廣告攔截器擋掉(擋掉 GA 的人比例很高,尤其是會逛成人站的族群)。
 *
 * 只記真的「一個人看了一頁」:
 *   - 只記 GET,而且只記回 200 的 HTML(重導、404、JSON、下載都不算)
 *   - 爬蟲不記,不然排行榜前幾名會是 Googlebot
 *   - 後台自己不記,不然我每看一次流量頁就替它加一筆
 *
 * 寫入包在 try/catch 裡:統計壞掉不該讓使用者看不到頁面。
 */
class TrackPageView
{
    /** 這些前綴不記錄:後台、健康檢查、靜態與機器用的端點。 */
    private const SKIP_PREFIXES = [
        'admin', 'up', 'ads.txt', 'robots.txt', 'sitemap', 'llms.txt', 'storage', 'build',
    ];

    private const BOT_PATTERN = '/bot|crawler|spider|crawling|slurp|facebookexternalhit|preview|monitor|curl|wget|python-requests|headless/i';

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        try {
            if ($this->shouldRecord($request, $response)) {
                $this->record($request);
            }
        } catch (Throwable) {
            // 統計不是功能。寫不進去就算了,不要影響到使用者。
        }

        return $response;
    }

    private function shouldRecord(Request $request, Response $response): bool
    {
        if (! $request->isMethod('GET') || $request->ajax() || $request->wantsJson()) {
            return false;
        }

        // 只記真的看到內容的那一次。302 到年齡閘或登入頁不是一次瀏覽。
        if ($response->getStatusCode() !== 200) {
            return false;
        }

        if (! str_contains((string) $response->headers->get('Content-Type'), 'text/html')) {
            return false;
        }

        if (preg_match(self::BOT_PATTERN, (string) $request->userAgent())) {
            return false;
        }

        $first = explode('/', trim($this->stripLocale($request->path()), '/'))[0] ?? '';

        return ! in_array($first, self::SKIP_PREFIXES, true);
    }

    private function record(Request $request): void
    {
        PageView::create([
            'path' => '/'.ltrim($this->stripLocale($request->path()), '/'),
            'locale' => app()->getLocale(),
            'user_id' => $request->user()?->id,
            'visitor_hash' => $this->visitorHash($request),
            'referer_host' => $this->refererHost($request),
        ]);
    }

    /** /tw/games → /games。四個語系是同一個頁面,分開算就看不出誰熱門。 */
    private function stripLocale(string $path): string
    {
        $path = trim($path, '/');
        $parts = explode('/', $path);

        if (isset($parts[0]) && LocaleHelper::prefixToLocale($parts[0])) {
            array_shift($parts);
        }

        return implode('/', $parts) ?: '/';
    }

    /**
     * 當天不重複訪客用的雜湊。日期加進來,所以同一個人隔天就是另一個雜湊 ——
     * 算得出「今天有多少人」,但沒辦法拿它把某個人的行為串成長期軌跡。
     * APP_KEY 加進來,是為了讓這份資料就算外流也沒辦法用 IP 表反查。
     */
    private function visitorHash(Request $request): string
    {
        return hash('sha256', implode('|', [
            $request->ip(),
            $request->userAgent(),
            now()->toDateString(),
            config('app.key'),
        ]));
    }

    private function refererHost(Request $request): ?string
    {
        $referer = $request->headers->get('referer');

        if (! $referer) {
            return null;
        }

        $host = parse_url($referer, PHP_URL_HOST);

        // 站內互連不是「來源」,記了只會把真正的外部來源洗掉。
        if (! $host || $host === $request->getHost()) {
            return null;
        }

        return mb_substr($host, 0, 191);
    }
}
