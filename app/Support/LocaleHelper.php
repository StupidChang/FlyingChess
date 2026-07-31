<?php

namespace App\Support;

use Illuminate\Support\Facades\Request;

class LocaleHelper
{
    public static function supported(): array
    {
        return config('app.available_locales', []);
    }

    /**
     * Locales whose translations are reviewed and safe to expose in hreflang
     * and sitemaps. Unready locales still work as URL prefixes (handy for QA
     * and progressive rollout) but stay invisible to crawlers.
     */
    public static function readyLocales(): array
    {
        return array_filter(
            self::supported(),
            fn ($meta) => ! empty($meta['ready']),
        );
    }

    public static function isReady(string $locale): bool
    {
        return ! empty(self::supported()[$locale]['ready'] ?? false);
    }

    /**
     * Pick the localized value for a translatable column.
     *
     * Master-first read order (avoids stale reads when admin edits the legacy
     * source column without touching JSON):
     *   1. If app locale == master (zh_TW), return $masterValue directly.
     *   2. If translations[$locale] is non-empty, return it.
     *   3. Fall back to $masterValue.
     */
    public static function pickTranslation($translations, $masterValue, ?string $locale = null)
    {
        $locale = $locale ?? app()->getLocale();
        if ($locale === self::defaultLocale()) {
            return $masterValue;
        }

        if ($translations) {
            $decoded = is_string($translations) ? json_decode($translations, true) : $translations;
            if (is_array($decoded) && ! empty($decoded[$locale])) {
                return $decoded[$locale];
            }
        }

        return $masterValue;
    }

    /**
     * 站台的主語系(不是當前請求的語系 —— 那個要用 app()->getLocale())。
     *
     * 刻意不讀 config('app.locale'):App::setLocale() 會把那個設定值覆寫成當前
     * 語系,所以中介層跑完之後它就不再是「預設」了。詳見 config/app.php 裡
     * 'default_locale' 的說明。
     *
     * 退回 fallback_locale 是為了保險 —— 就算有人把 default_locale 從設定檔裡
     * 拿掉,fallback_locale 同樣不會被 setLocale() 動到,語意也最接近。
     */
    public static function defaultLocale(): string
    {
        return config('app.default_locale')
            ?? config('app.fallback_locale')
            ?? 'zh_TW';
    }

    public static function prefixToLocale(?string $prefix): ?string
    {
        if (! $prefix) {
            return null;
        }
        foreach (self::supported() as $locale => $meta) {
            if (($meta['prefix'] ?? null) === $prefix) {
                return $locale;
            }
        }

        return null;
    }

    public static function localeToPrefix(string $locale): ?string
    {
        return self::supported()[$locale]['prefix'] ?? null;
    }

    public static function hreflang(string $locale): ?string
    {
        return self::supported()[$locale]['hreflang'] ?? null;
    }

    public static function isSupported(string $locale): bool
    {
        return array_key_exists($locale, self::supported());
    }

    /**
     * Detect locale by precedence: URL prefix > cookie > Accept-Language > default.
     * URL prefix detection happens in middleware; this is for fallback chain only.
     */
    public static function detectFromRequest($request): string
    {
        $cookie = $request->cookie('locale');
        if ($cookie && self::isSupported($cookie)) {
            return $cookie;
        }
        $accept = $request->header('Accept-Language', '');
        foreach (explode(',', $accept) as $lang) {
            $lang = trim(explode(';', $lang)[0]);
            $candidates = [
                str_replace('-', '_', $lang),
                strtolower(explode('-', $lang)[0] ?? ''),
            ];
            foreach (self::supported() as $locale => $meta) {
                foreach ($candidates as $c) {
                    if ($c === '') {
                        continue;
                    }
                    if (strcasecmp($locale, $c) === 0
                        || strcasecmp(($meta['hreflang'] ?? ''), $lang) === 0
                        || strcasecmp(strtolower(explode('_', $locale)[0]), $c) === 0) {
                        return $locale;
                    }
                }
            }
        }

        return self::defaultLocale();
    }

    /**
     * Build a URL with the given locale prefix.
     * Strips any existing locale prefix from $path before re-prefixing.
     */
    public static function localizedUrl(string $locale, ?string $path = null): string
    {
        $prefix = self::localeToPrefix($locale);
        if (! $prefix) {
            return url($path ?? '/');
        }
        $path = $path ?? Request::path();
        $path = ltrim($path, '/');
        $path = self::stripLocalePrefix($path);

        return url($path === '' ? "/{$prefix}" : "/{$prefix}/{$path}");
    }

    /**
     * Remove a leading locale prefix (tw/cn/jp/en) from a path.
     */
    public static function stripLocalePrefix(string $path): string
    {
        $path = ltrim($path, '/');
        $prefixes = array_filter(array_column(self::supported(), 'prefix'));
        foreach ($prefixes as $prefix) {
            if ($path === $prefix) {
                return '';
            }
            if (str_starts_with($path, $prefix.'/')) {
                return substr($path, strlen($prefix) + 1);
            }
        }

        return $path;
    }
}
