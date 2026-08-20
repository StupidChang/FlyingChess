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

        /* Only publicly discoverable boards belong in the sitemap. Private user
           boards are unlisted (share_code URL only) — exposing them here would
           leak every "private" share link to search engines.

           條件走 Board::scopePubliclyIndexable(),和 play 頁面的 robots meta 同一份
           規則 —— 這裡曾經自己寫過一份,結果 sitemap 收的和頁面宣告的不一樣:
           付費範本回 302、範本頁自己標 noindex,兩種都被列進來。見那個 scope 的說明。 */
        $boards = Board::publiclyIndexable()
            ->whereNotNull('share_code')
            // 預設棋盤在上面已經以靜態路徑 play 收錄過了。它如果也有 share_code,
            // 這裡不排除就會讓同一張棋盤出現兩個 <loc>,而且其中一個不是它的
            // canonical(Board::canonicalPlayUrl() 對預設棋盤回的是 /play)——
            // sitemap 只該列 canonical 網址。
            ->where('is_default', false)
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
