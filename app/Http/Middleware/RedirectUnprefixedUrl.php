<?php

namespace App\Http\Middleware;

use App\Support\LocaleHelper;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectUnprefixedUrl
{
    /**
     * Paths that must NEVER be locale-prefixed. These are infrastructural endpoints
     * (cookie writes, webhooks, sitemaps, health checks) where adding a /tw or /en
     * segment would break the contract with browsers, payment providers, or crawlers.
     */
    private const NEVER_PREFIX = [
        'age-verify',
        'sitemap.xml',
        'robots.txt',
        'up',
        'premium/callback',
        'premium/result',
    ];

    private const NEVER_PREFIX_PATTERNS = [
        '#^sitemap-[a-z]{2}\.xml$#',
        '#^_ignition(/|$)#',
        '#^livewire(/|$)#',
        '#^build/#',
        '#^css/#',
        '#^js/#',
        '#^images/#',
        '#^fonts/#',
        '#^favicon#',
        '#^storage/#',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethod('GET') && ! $request->isMethod('HEAD')) {
            return $next($request);
        }

        $path = ltrim($request->path(), '/');

        if ($this->isExempt($path) || $this->hasLocalePrefix($path)) {
            return $next($request);
        }

        $locale = LocaleHelper::detectFromRequest($request);
        $prefix = LocaleHelper::localeToPrefix($locale);
        if (! $prefix) {
            return $next($request);
        }

        $query = $request->getQueryString();
        $target = '/'.$prefix.($path === '' ? '' : '/'.$path).($query ? '?'.$query : '');

        return redirect($target, 301);
    }

    private function hasLocalePrefix(string $path): bool
    {
        foreach (LocaleHelper::supported() as $meta) {
            $prefix = $meta['prefix'] ?? null;
            if (! $prefix) {
                continue;
            }
            if ($path === $prefix || str_starts_with($path, $prefix.'/')) {
                return true;
            }
        }

        return false;
    }

    private function isExempt(string $path): bool
    {
        if (in_array($path, self::NEVER_PREFIX, true)) {
            return true;
        }
        foreach (self::NEVER_PREFIX_PATTERNS as $pattern) {
            if (preg_match($pattern, $path)) {
                return true;
            }
        }

        return false;
    }
}
