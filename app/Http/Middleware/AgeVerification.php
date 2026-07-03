<?php

namespace App\Http\Middleware;

use App\Support\LocaleHelper;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
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
        'ads.txt',
        'premium/callback',
        'premium/result',
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

    private const CRAWLER_PATTERNS = [
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

        // Allow crawlers
        $ua = $request->userAgent() ?? '';
        foreach (self::CRAWLER_PATTERNS as $pattern) {
            if (stripos($ua, $pattern) !== false) {
                return $next($request);
            }
        }

        // Check cookie
        if ($request->cookie(self::COOKIE_NAME) === '1') {
            return $next($request);
        }

        // Age gate POST (confirm) — `age-verify` is never locale-prefixed,
        // so $rawPath is the only safe match here.
        if ($request->isMethod('POST') && $rawPath === 'age-verify') {
            $cookie = cookie(self::COOKIE_NAME, '1', self::COOKIE_DAYS * 24 * 60);

            return redirect()->back()->withCookie($cookie);
        }

        // Show age gate (renders for both GET and non-GET; previously non-GET silently bypassed
        // age verification entirely, allowing anyone to POST to /games/{code}/roll etc. without confirming age)
        return response()->view('partials.age-gate-full', [], 200);
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
