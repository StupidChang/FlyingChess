<?php

namespace App\Http\Controllers;

use App\Models\Board;
use App\Support\LocaleHelper;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    /**
     * Sitemap index (referenced from robots.txt). Lists each locale's child sitemap.
     */
    public function index(): Response
    {
        $locales = LocaleHelper::readyLocales();

        return response()
            ->view('sitemap.index', compact('locales'))
            ->header('Content-Type', 'application/xml; charset=utf-8');
    }

    /**
     * Per-locale child sitemap. Each <url> entry includes <xhtml:link rel="alternate">
     * back to the same page in every other supported locale, so the sitemap itself
     * carries hreflang signals (Google's recommended sitemap-level i18n declaration).
     */
    public function locale(string $prefix): Response
    {
        $locale = LocaleHelper::prefixToLocale($prefix);
        abort_if($locale === null || ! LocaleHelper::isReady($locale), 404);

        $boards = Board::whereNotNull('share_code')->get();
        $supported = LocaleHelper::readyLocales();

        return response()
            ->view('sitemap.locale', [
                'currentLocale' => $locale,
                'supported'     => $supported,
                'boards'        => $boards,
            ])
            ->header('Content-Type', 'application/xml; charset=utf-8');
    }
}
