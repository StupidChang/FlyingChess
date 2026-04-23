<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
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
        'premium/callback',
        'premium/result',
        'up',
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
        // Allow static assets
        $path = $request->path();
        foreach (self::WHITELISTED_PREFIXES as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return $next($request);
            }
        }

        // Allow whitelisted paths
        if (in_array($path, self::WHITELISTED_PATHS)) {
            return $next($request);
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

        // Age gate POST (confirm)
        if ($request->isMethod('POST') && $path === 'age-verify') {
            $cookie = cookie(self::COOKIE_NAME, '1', self::COOKIE_DAYS * 24 * 60);
            return redirect()->back()->withCookie($cookie);
        }

        // Show age gate
        return response()->view('partials.age-gate-full', [], 200);
    }
}
