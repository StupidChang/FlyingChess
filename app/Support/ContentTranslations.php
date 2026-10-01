<?php

namespace App\Support;

/**
 * 站上內建內容(範本棋盤、真心話大冒險題卡、轉盤、小遊戲題庫)的翻譯字典。
 *
 * 為什麼是「以繁中原文當 key 的字典檔」,而不是寫進各表的 *_translations 欄位:
 *
 * 1. **內容的事實來源在 seeder 與 Service 常數**,不在資料庫。寫進 DB 的翻譯會被
 *    下一次 db:seed / 後台「匯入預設」蓋掉或漏掉,字典檔跟著 git 走,不會。
 * 2. **原文改了,翻譯自動失效**,而不是默默顯示一句跟原文對不上的舊翻譯 ——
 *    key 對不到就退回繁中,`php artisan content:translation-coverage` 會把它列出來。
 * 3. **使用者從範本複製出來的棋盤也吃得到**:沒改過的格子文字跟範本一模一樣,
 *    自然對得上;改過的格子是使用者自己的內容,本來就不該被翻。
 *
 * 讀取順序由 LocaleHelper::pickTranslation() 決定:欄位裡的翻譯(後台手動填的)
 * 優先,其次這份字典,最後才是繁中原文。
 *
 * 字典檔:resources/content-translations/{locale}.php,回傳 [原文 => 譯文]。
 */
class ContentTranslations
{
    /** @var array<string, array<string, string>> */
    private static array $loaded = [];

    public static function path(string $locale): string
    {
        return resource_path("content-translations/{$locale}.php");
    }

    /**
     * 字典的 key 一律用正規化過的原文:後台貼上的內容常帶 \r\n,
     * 前後空白也不該讓一句翻譯對不上。
     */
    public static function normalize(string $text): string
    {
        return trim(str_replace("\r\n", "\n", $text));
    }

    /** @return array<string, string> */
    public static function dictionary(string $locale): array
    {
        if (! array_key_exists($locale, self::$loaded)) {
            $file = self::path($locale);
            self::$loaded[$locale] = is_file($file) ? (array) require $file : [];
        }

        return self::$loaded[$locale];
    }

    /** 測試改了字典檔之後要重讀 */
    public static function flush(): void
    {
        self::$loaded = [];
    }

    /**
     * 找得到翻譯就回傳譯文,找不到回 null(呼叫端自己決定要退回什麼)。
     * 主語系永遠回 null —— 原文就是答案,不需要查。
     */
    public static function lookup(?string $text, ?string $locale = null): ?string
    {
        $locale = $locale ?? app()->getLocale();

        if ($text === null || $text === '' || $locale === LocaleHelper::defaultLocale()) {
            return null;
        }

        $hit = self::dictionary($locale)[self::normalize($text)] ?? null;

        return ($hit === null || $hit === '') ? null : $hit;
    }

    /** 有翻譯給翻譯,沒有就原文 */
    public static function translate(?string $text, ?string $locale = null): ?string
    {
        return self::lookup($text, $locale) ?? $text;
    }

    /**
     * 小遊戲題庫的形狀:[pool => [題目, ...]]。
     *
     * @param  array<string, array<int, string>>  $pools
     * @return array<string, array<int, string>>
     */
    public static function pools(array $pools, ?string $locale = null): array
    {
        return array_map(
            fn ($items) => array_map(fn ($t) => is_string($t) ? self::translate($t, $locale) : $t, $items),
            $pools,
        );
    }

    /**
     * 這一批文字在這個語系是不是全部有翻譯。空字串不算缺。
     * 用來決定一張棋盤的頁面在這個語系能不能被收錄。
     *
     * @param  iterable<string|null>  $texts
     */
    public static function covers(iterable $texts, ?string $locale = null): bool
    {
        $locale = $locale ?? app()->getLocale();

        if ($locale === LocaleHelper::defaultLocale()) {
            return true;
        }

        foreach ($texts as $text) {
            // 沒有中文字的(純數字、emoji、「P1」)不需要翻,也不該讓整張棋盤被當成沒翻完
            if ($text === null || ! preg_match('/\p{Han}/u', $text)) {
                continue;
            }
            if (self::lookup($text, $locale) === null) {
                return false;
            }
        }

        return true;
    }
}
