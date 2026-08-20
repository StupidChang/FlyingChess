<?php

use App\Http\Middleware\AgeVerification;
use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\EnsureNotBanned;
use App\Http\Middleware\EnsurePremium;
use App\Http\Middleware\RedirectUnprefixedUrl;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\TrackPageView;
use Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Session\Middleware\AuthenticateSession;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // 網站掛在 Cloudflare 後面時,PHP 看到的來源是 Cloudflare、而且那一段連線
        // 是 HTTP。不宣告可信代理的話 isSecure() 會是 false,canonical / hreflang /
        // sitemap / llms.txt / JSON-LD 的 @id 就全部輸出 http://,而且每個人的 IP
        // 都會變成 Cloudflare 的位址(節流會把所有人算成同一個)。
        // 範圍清單與更新方式見 config/cloudflare.php。
        //
        // 這裡不能用 config():bootstrap/app.php 在設定載入之前就執行了,呼叫
        // config() 會在容器裡解析不到 'config' 而讓整個應用程式起不來。所以直接
        // require 那個檔案 —— 清單仍然是資料而不是程式碼,只是取用方式不同。
        $middleware->trustProxies(
            at: (require __DIR__.'/../config/cloudflare.php')['proxies']
        );

        /*
         * 只信任這幾個 Host。origin(nginx)本身可以被直接連到(preview vhost、
         * 直連 IP),沒有這一層的話,攻擊者送一個任意 Host header 進來,就會被
         * 反射進 canonical / 表單 action /「密碼重設信」的連結 —— 等於一個可以
         * 把重設連結指到釣魚站的漏洞。列白名單之後,Host 對不上的請求直接 400,
         * 連控制器都到不了,信也就發不出去。
         *
         * TrustHosts 在 local / testing 會自動略過(見 shouldSpecifyTrustedHosts),
         * 所以不影響本機開發與測試。
         *
         * ⚠ 這裡的每一筆都是「正規表達式」,不是字串比對(Symfony 會拿去 preg_match)。
         * 所以一定要錨定 ^...$ 並跳脫點號,否則 'pillownight.com' 會變成「點號可代表
         * 任意字元、且未錨定」的鬆散樣式(evil-pillownightXcom.attacker.com 也會過)。
         * 主網域與其子網域(www.)由 subdomains: true 依 app.url 自動加上
         * ^(.+\.)?pillownight\.com$,不必也不該在這裡重複。
         */
        $middleware->trustHosts(
            at: [
                '^flying\.104\.64\.144\.208\.nip\.io$',   // preview vhost
                '^localhost$',
                '^127\.0\.0\.1$',
            ],
            subdomains: true,
        );

        $middleware->alias([
            'age.verify' => AgeVerification::class,
            'premium' => EnsurePremium::class,
            'admin' => EnsureAdmin::class,
            'not.banned' => EnsureNotBanned::class,
            'set.locale' => SetLocale::class,
            'redirect.unprefixed' => RedirectUnprefixedUrl::class,
        ]);

        /*
         * 讓「密碼重設 / 變更」能夠讓該使用者的其他 session 一起失效。
         *
         * AuthenticateSession 會把使用者的密碼 hash 存進 session,每次請求比對;
         * 一旦密碼被改(重設),其他仍持有舊 session cookie 的地方(例如被竊取的
         * session)下一個請求就對不上而被登出。沒有它的話,受害者重設密碼後,
         * 攻擊者手上的舊 session 依然有效 —— 這正是使用者重設密碼想擋掉的事。
         */
        $middleware->web(append: [
            AuthenticateSession::class,
        ]);

        // The locale cookie is a UI preference (not sensitive); skip encryption
        // so RedirectUnprefixedUrl can read it before EncryptCookies runs, and
        // so the front end can read it via document.cookie for the language switcher.
        $middleware->encryptCookies(except: ['locale']);

        // AWS 的 SNS 通知不會帶 CSRF token。這條路由的安全性靠 SNS 簽章驗證,
        // 不是靠 session。
        $middleware->validateCsrfTokens(except: ['ses/feedback']);

        // Order matters: RedirectUnprefixedUrl 301s legacy non-prefixed URLs
        // before AgeVerification renders the age gate, avoiding wasted renders.
        $middleware->prepend(RedirectUnprefixedUrl::class);
        $middleware->append(AgeVerification::class);

        // 流量紀錄排在年齡閘之後:被年齡閘擋下的那一次不是真的看到內容,
        // 記了會讓每個新訪客的第一次都變成兩筆。
        $middleware->append(TrackPageView::class);

        // SetLocale must run before Authenticate / EnsureEmailIsVerified:
        // their guest/unverified redirects call route('login') /
        // route('verification.notice'), which need URL::defaults(['locale'])
        // already pinned or they throw UrlGenerationException (500 instead
        // of a redirect for logged-out visitors on auth-only pages).
        $middleware->prependToPriorityList(
            AuthenticatesRequests::class,
            SetLocale::class,
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
