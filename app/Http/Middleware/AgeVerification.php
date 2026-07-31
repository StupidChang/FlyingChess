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
        'llms.txt',
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

    /**
     * Bots allowed past the age gate.
     *
     * Matched with a case-insensitive substring test against the UA, so entries
     * must stay specific enough not to swallow each other — 'ClaudeBot' and
     * 'Claude-User' are different products and only one of them is here.
     *
     * The split below is deliberate and is the whole GEO policy for this site:
     *
     *   Retrieval / citation bots fetch a page because a user just asked
     *   something, and the answer links back here. They are the ones worth
     *   letting in — without them the site cannot appear in ChatGPT, Perplexity
     *   or Claude answers at all, because the age gate returns a 200 with no
     *   game content and that is all they would ever see.
     *
     *   Training bots (GPTBot, ClaudeBot, CCBot, Bytespider, meta-externalagent,
     *   Amazonbot …) take the content into a model and send nothing back. For an
     *   adult site that is cost without return, so they stay gated, and
     *   robots.txt disallows them as well.
     *
     * To let the training crawlers in too, move their tokens into this array —
     * and update the matching Disallow block in the robots route.
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

        // Generative-engine retrieval / citation (see note above)
        'OAI-SearchBot',    // OpenAI's search index behind ChatGPT search
        'ChatGPT-User',     // ChatGPT fetching a page for the user's current turn
        'PerplexityBot',    // Perplexity's index
        'Perplexity-User',  // Perplexity fetching on a user's request
        'Claude-User',      // Claude fetching on a user's request
        'Claude-SearchBot', // Anthropic's search index
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
