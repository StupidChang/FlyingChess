<?php

namespace App\Http\Middleware;

use App\Support\LocaleHelper;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class AgeVerification
{
    private const COOKIE_NAME = 'age_verified';

    private const COOKIE_DAYS = 30;

    private const WHITELISTED_PATHS = [
        'privacy',
        'terms',
        'sitemap.xml',
        'robots.txt',
        'llms.txt',
        'ads.txt',
        'premium/callback',
        'premium/result',
        // 機器對機器的端點。AWS 的 SNS 不會帶 cookie,也讀不懂年齡確認頁 ——
        // 沒放行的話 webhook 拿到的是一頁 HTML 加 200,退信通知會全部靜靜掉光。
        'ses/feedback',
        'up',
        // Auth flows — allow access before age-gate so users can manage account
        'login',
        'register',
        'forgot-password',
        'reset-password',
        'logout',
        'email/verify',
        'email/verification-notification',
    ];

    private const WHITELISTED_PATH_PATTERNS = [
        '#^reset-password/.+$#',           // reset-password/{token}
        '#^email/verify/[^/]+/[^/]+$#',    // email/verify/{id}/{hash}
        '#^sitemap-[a-z]{2}\.xml$#',        // /sitemap-tw.xml, /sitemap-en.xml ...
    ];

    private const WHITELISTED_PREFIXES = [
        'build/',
        'css/',
        'js/',
        'images/',
        'fonts/',
        'favicon',
    ];

    /**
     * 整站離線複製工具。這些 UA 的唯一用途就是把整個網站抓下來做成鏡像 —— 不是
     * 搜尋引擎、也不是使用者代開的頁面,放行沒有任何好處。直接回 403。
     *
     * 這擋不住改 UA 的人(公開的 SSR 站本來就擋不了決心複製的人,內容必須可被
     * 檢索才有 SEO),但擋掉了「拿現成工具一鍵整站打包」這個最省事的路徑。真正
     * 強的防線在 Cloudflare(Bot Fight Mode / Scrape Shield / 熱連結保護),
     * 那一層看得到 JA3/行為特徵,不是只看 UA 字串。
     */
    private const BLOCKED_SCRAPER_PATTERNS = [
        // 專門的「整站打包/離線瀏覽」工具,對本站沒有任何正當用途。刻意不放
        // curl / wget / python-requests / Go-http-client 這類「泛用」HTTP 客戶端 ——
        // 它們有正當用途(健康檢查、連結預覽、站長自己的腳本),擋了誤傷大、
        // 而且真要抄的人改個 UA 就繞過,防不到人卻先弄壞自己的工具。
        'HTTrack', 'httrack', 'WebCopier', 'WebZIP', 'WebReaper', 'WebStripper',
        'Teleport', 'TeleportPro', 'Offline Explorer', 'SiteSnagger', 'SiteSucker',
        'WebWhacker', 'Web Downloader', 'Scrapy', 'PHPCrawl', 'libwww-perl',
    ];

    /**
     * 訓練型爬蟲。這些 bot 把內容吃進模型,不送任何流量回來 —— 對一個成人站而言
     * 是純成本,所以不給內容,robots.txt 也一併 Disallow(見 routes/web.php)。
     * 兩邊要一起改,不然會出現「robots 說不准、實際卻給」這種自相矛盾。
     *
     * 這**不是** cloaking。cloaking 講的是「給搜尋引擎的內容和給使用者的不一樣」,
     * 而這些不是搜尋引擎,擋掉它們純粹是 robots 政策。真正會被判 cloaking 的是
     * 「Googlebot 看到完整內容、真人看到閘門頁」——那件事由 age_gate_mode 處理。
     *
     * Google-Extended 與 Applebot-Extended 刻意不列在這裡:它們只是 robots.txt 的
     * token,不是真的 User-Agent,寫在這裡永遠比對不到,只會讓人以為有在擋。
     */
    private const TRAINING_BOT_PATTERNS = [
        'GPTBot', 'ClaudeBot', 'CCBot', 'Bytespider', 'meta-externalagent',
        'Amazonbot', 'anthropic-ai', 'cohere-ai', 'Diffbot', 'Omgilibot', 'ImagesiftBot',
    ];

    /**
     * interstitial 模式下可以略過閘門頁、直接看到內容的 bot。
     *
     * overlay 模式(預設)用不到這份清單 —— 那個模式下所有人本來就拿到同一份 HTML,
     * 不需要、也不應該再依 UA 分岔。這份清單只在切回 interstitial 時才生效,
     * 因為那個模式沒有它的話,本站對搜尋引擎與生成式引擎等於完全不存在:
     * 它們看到的永遠是一頁沒有內容的年齡確認頁。
     *
     * 比對方式是對 UA 做不分大小寫的子字串搜尋,所以每一項都要夠具體、不能互相吞掉 ——
     * 'ClaudeBot' 和 'Claude-User' 是兩個不同的東西,而且只有後者在這裡。
     */
    private const CRAWLER_PATTERNS = [
        // Classic search + social preview
        'Googlebot',
        'Bingbot',
        'Slurp',
        'DuckDuckBot',
        'Baiduspider',
        'YandexBot',
        'facebookexternalhit',
        'Twitterbot',
        'LinkedInBot',
        'Applebot',

        // Generative-engine retrieval / citation:使用者當下問了問題才來抓,
        // 而且答案會連回來。擋掉它們等於放棄在 ChatGPT / Perplexity / Claude
        // 的回答裡出現的機會。
        'OAI-SearchBot',
        'ChatGPT-User',
        'PerplexityBot',
        'Perplexity-User',
        'Claude-User',
        'Claude-SearchBot',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $rawPath = $request->path();

        // Static assets are never locale-prefixed; check raw path.
        foreach (self::WHITELISTED_PREFIXES as $prefix) {
            if (str_starts_with($rawPath, $prefix)) {
                return $next($request);
            }
        }

        // 擋整站複製工具(見 BLOCKED_SCRAPER_PATTERNS)。放在資產白名單之後,
        // 所以 css/js/圖片仍能被正常載入;放在其他所有判斷之前,連年齡閘的 HTML
        // 都不給 —— 這些工具本來就是要抓 HTML 做鏡像。
        $ua = $request->userAgent() ?? '';
        foreach (self::BLOCKED_SCRAPER_PATTERNS as $pattern) {
            if (stripos($ua, $pattern) !== false) {
                return response('Forbidden', 403);
            }
        }

        // SetLocale (route group middleware) hasn't run yet at this point —
        // global middleware fires before route-level. Resolve the URL prefix
        // from the path string directly so the rendered age-gate view picks
        // the right language from app()->getLocale().
        $this->setLocaleFromUrlPrefix($rawPath);

        // For everything else, strip /tw|cn|jp|en/ before whitelist matching
        // so /tw/privacy is treated the same as /privacy.
        $path = LocaleHelper::stripLocalePrefix($rawPath);

        // Allow whitelisted paths
        if (in_array($path, self::WHITELISTED_PATHS)) {
            return $next($request);
        }

        // Allow whitelisted path patterns (regex)
        foreach (self::WHITELISTED_PATH_PATTERNS as $pattern) {
            if (preg_match($pattern, $path)) {
                return $next($request);
            }
        }

        // 訓練型爬蟲:不給內容。兩種模式都一樣,這是 robots 政策不是年齡確認。
        if ($this->matches($ua, self::TRAINING_BOT_PATTERNS)) {
            return $this->interstitial();
        }

        /* 分享卡片(og:image)是機器對機器的端點。各家連結預覽抓取器不帶 cookie、
           也讀不懂年齡確認頁 —— 拿到 HTML 的話預覽就是一片空白,等於白做。
           UA 白名單救不了這件事:LINE、Discord、Telegram、Slack 都不在裡面,
           而在台灣分享大多是走 LINE。

           刻意放在訓練型爬蟲那道**之後** —— 它們仍然什麼都拿不到。這裡放行的
           只有一張圖,卡片上只用 `line`(暗示性的一句話),不是內容頁。 */
        if (preg_match('#^trait-test/[a-z0-9-]+/og\\.png$#', $path)) {
            return $next($request);
        }

        // Check cookie
        if ($request->cookie(self::COOKIE_NAME) === '1') {
            return $next($request);
        }

        // Age gate POST (confirm) — `age-verify` is never locale-prefixed,
        // so $rawPath is the only safe match here.
        if ($request->isMethod('POST') && $rawPath === 'age-verify') {
            $cookie = cookie(self::COOKIE_NAME, '1', self::COOKIE_DAYS * 24 * 60);

            // 同 routes/web.php 的 age-verify:只回同主機的 Referer,擋開放轉址。
            $ref = (string) $request->headers->get('referer', '');
            $back = ($ref !== '' && parse_url($ref, PHP_URL_HOST) === $request->getHost()) ? $ref : url('/');

            return redirect($back)->withCookie($cookie);
        }

        /*
         * overlay 模式(預設):讀取型請求一律放行,由版面蓋上覆蓋層。
         *
         * 這裡**刻意不看 User-Agent** —— 不分岔正是這個模式的全部重點:Googlebot
         * 與沒確認過年齡的真人拿到完全相同的 HTML,所以不構成 cloaking。
         *
         * 只放行安全方法(GET/HEAD)。寫入型請求仍然擋著:覆蓋層是畫面上的東西,
         * 擋不住直接對端點送 POST 的人,那道後備防線要留在伺服器這邊。
         */
        if (self::mode() === 'overlay' && $request->isMethodSafe()) {
            View::share('ageUnverified', true);

            /* 同一件事也掛在 request 上,給後面的中介層用(TrackPageView 要知道
               這一次不算一次瀏覽)。不共用 cookie 判斷是因為順序會騙人:這個
               中介層是全域的,跑在 EncryptCookies 之前,而 TrackPageView 讀的是
               $next() 回來之後的 request —— 那時候 cookie 袋已經被解密流程動過,
               解不開的值會變成 null。attributes 不會被任何人動,才是可靠的訊號。 */
            $request->attributes->set('age_unverified', true);

            return $next($request);
        }

        // interstitial 模式:爬蟲靠 UA 白名單放行(否則本站等於不存在)
        if (self::mode() !== 'overlay' && $this->matches($ua, self::CRAWLER_PATTERNS)) {
            return $next($request);
        }

        // Show age gate (renders for both GET and non-GET; previously non-GET silently bypassed
        // age verification entirely, allowing anyone to POST to /games/{code}/roll etc. without confirming age)
        return $this->interstitial();
    }

    /** 目前的年齡閘形式。見 config/content.php 的說明。 */
    private static function mode(): string
    {
        return config('content.age_gate_mode') === 'interstitial' ? 'interstitial' : 'overlay';
    }

    /** 獨立一頁的年齡確認頁。200 是刻意的 —— 這是一個頁面,不是錯誤。 */
    private function interstitial(): Response
    {
        return response()->view('partials.age-gate-full', [], 200);
    }

    /**
     * UA 是否命中清單。不分大小寫的子字串比對 —— 和各家 bot 自己文件上的建議一致。
     *
     * @param  array<int, string>  $patterns
     */
    private function matches(string $ua, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            if (stripos($ua, $pattern) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Pick app locale from a URL prefix (tw/cn/jp/en) when present, otherwise
     * fall back to cookie / Accept-Language / default. Used at this middleware
     * layer because the route group's `set.locale` doesn't fire until after
     * global middleware (us) has already decided whether to render the age-gate.
     */
    private function setLocaleFromUrlPrefix(string $rawPath): void
    {
        $first = explode('/', $rawPath, 2)[0] ?? '';
        $locale = LocaleHelper::prefixToLocale($first);
        if ($locale === null) {
            $locale = LocaleHelper::detectFromRequest(request());
        }
        App::setLocale($locale);
    }
}
