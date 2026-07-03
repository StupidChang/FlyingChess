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

        // Only publicly discoverable boards belong in the sitemap. Private user
        // boards are unlisted (share_code URL only) — exposing them here would
        // leak every "private" share link to search engines.
        $boards = Board::whereNotNull('share_code')
            ->where(function ($q) {
                $q->where('is_template', true)
                    ->orWhere('is_default', true)
                    ->orWhere('publish_status', Board::PUBLISH_APPROVED);
            })
            ->get();
        $supported = LocaleHelper::readyLocales();

        return response()
            ->view('sitemap.locale', [
                'currentLocale' => $locale,
                'supported' => $supported,
                'boards' => $boards,
            ])
            ->header('Content-Type', 'application/xml; charset=utf-8');
    }
}
