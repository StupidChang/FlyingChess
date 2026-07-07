<?php

namespace App\Http\Middleware;

use App\Support\LocaleHelper;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Read the URL prefix (tw/cn/jp/en) injected by the locale route group,
     * map it to the matching app locale (zh_TW/zh_CN/ja/en), set Carbon/Lang
     * locale, and refresh the `locale` cookie so future visits remember it.
     *
     * When attached to a non-prefixed route (e.g. /premium/result, which must
     * keep a stable URL for the payment gateway but still renders layouts.app
     * — and that layout calls route('home') etc. that require {locale}), the
     * route parameter is absent. In that case we detect the locale from
     * cookie / Accept-Language / default and still set URL::defaults so the
     * shared layout doesn't crash with UrlGenerationException.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $prefix = $request->route('locale');

        if ($prefix) {
            $locale = LocaleHelper::prefixToLocale($prefix) ?? LocaleHelper::defaultLocale();
        } else {
            $locale = LocaleHelper::detectFromRequest($request);
            $prefix = LocaleHelper::localeToPrefix($locale);
        }

        App::setLocale($locale);

        if ($prefix) {
            URL::defaults(['locale' => $prefix]);
        }

        // Drop the {locale} parameter so it is not injected into controller
        // method signatures. Without this, routes like /tw/play/{board} would
        // pass 'tw' as the first argument (e.g. $board), causing 404/500.
        if ($request->route() && $request->route()->hasParameter('locale')) {
            $request->route()->forgetParameter('locale');
        }

        $response = $next($request);

        if ($request->cookie('locale') !== $locale) {
            $cookie = Cookie::create(
                name: 'locale',
                value: $locale,
                expire: time() + 60 * 60 * 24 * 365,
                path: '/',
                secure: $request->isSecure(),
                httpOnly: false,
                sameSite: Cookie::SAMESITE_LAX,
            );
            $response->headers->setCookie($cookie);
        }

        return $response;
    }
}
