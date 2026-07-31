<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * Premium 的定價查詢與格式化。
 *
 * 所有價格顯示都要走這裡,不要在 Blade 或語言檔裡寫幣別符號 —— 那正是改成多幣別
 * 之前的狀態:「NT$」硬寫在 17 個地方,而語言檔只吃到一個沒有單位的數字。
 *
 * 金額一律以「最小單位」在資料庫與金流之間傳遞(USD 用分、TWD/JPY 用元),因為
 * 浮點數在轉帳金額上是不能用的:7.99 在 IEEE 754 裡不是精確值,累加或比對都可能
 * 差一分錢。config 裡寫成 7.99 只是為了人看得懂,進出系統前後都轉成整數 799。
 */
class Pricing
{
    /**
     * 這個語系該顯示哪種幣別。
     *
     * 沒有在 locale_currency 裡指定的語系一律回 default_currency —— 顯示的幣別
     * 必須是真的能扣款的幣別,寧可全站顯示美元,也不要顯示 NT$ 卻扣美元。
     */
    public static function currency(?string $locale = null): string
    {
        $locale ??= app()->getLocale();
        $mapped = config('premium.locale_currency.'.$locale);

        return self::isKnownCurrency($mapped) ? $mapped : self::defaultCurrency();
    }

    public static function defaultCurrency(): string
    {
        $code = config('premium.default_currency', 'USD');

        // 設錯幣別會讓整站的價格顯示變成空白,寧可在這裡就炸開。
        if (! self::isKnownCurrency($code)) {
            throw new InvalidArgumentException("premium.default_currency 指向未定義的幣別:{$code}");
        }

        return $code;
    }

    /** @return array{symbol: string, decimals: int} */
    public static function currencyMeta(?string $code = null): array
    {
        $code ??= self::currency();

        return config('premium.currencies.'.$code);
    }

    /** @return list<string> 依 config 的順序 */
    public static function planKeys(): array
    {
        return array_keys(config('premium.plans', []));
    }

    /** @return array{days: int, amounts: array<string, float|int>} */
    public static function plan(string $key): array
    {
        $plan = config('premium.plans.'.$key);

        if (! is_array($plan)) {
            throw new InvalidArgumentException("未定義的方案:{$key}");
        }

        return $plan;
    }

    public static function exists(string $key): bool
    {
        return is_array(config('premium.plans.'.$key));
    }

    public static function defaultPlan(): string
    {
        $key = config('premium.default_plan');

        return self::exists($key) ? $key : (self::planKeys()[0] ?? 'monthly');
    }

    public static function entryPlan(): string
    {
        $key = config('premium.entry_plan');

        return self::exists($key) ? $key : self::defaultPlan();
    }

    public static function days(string $planKey): int
    {
        return (int) self::plan($planKey)['days'];
    }

    /**
     * 方案在某幣別的標價(人看的單位,例如 7.99)。
     *
     * 某個方案漏標某幣別時退回 default_currency 的標價,而不是回 0 —— 顯示成免費
     * 是比顯示錯幣別更糟的失敗。
     */
    public static function amount(string $planKey, ?string $currency = null): float|int
    {
        $currency ??= self::currency();
        $amounts = self::plan($planKey)['amounts'] ?? [];

        return $amounts[$currency] ?? $amounts[self::defaultCurrency()] ?? 0;
    }

    /**
     * 方案的最小單位金額(USD 7.99 → 799)。資料庫與金流用這個。
     */
    public static function minorAmount(string $planKey, ?string $currency = null): int
    {
        $currency ??= self::currency();

        return self::toMinor(self::amount($planKey, $currency), $currency);
    }

    public static function toMinor(float|int $amount, string $currency): int
    {
        $decimals = self::currencyMeta($currency)['decimals'] ?? 2;

        // round 一定要在轉整數之前:(int)(7.99 * 100) 在浮點數下會得到 798。
        return (int) round($amount * (10 ** $decimals));
    }

    public static function fromMinor(int $minor, string $currency): float|int
    {
        $decimals = self::currencyMeta($currency)['decimals'] ?? 2;

        return $decimals === 0 ? $minor : $minor / (10 ** $decimals);
    }

    /** 格式化成可直接輸出的字串,例如 "US$34.99"、"NT$1,090"。 */
    public static function format(string $planKey, ?string $currency = null): string
    {
        $currency ??= self::currency();

        return self::formatAmount(self::amount($planKey, $currency), $currency);
    }

    public static function formatAmount(float|int $amount, ?string $currency = null): string
    {
        $currency ??= self::currency();
        $meta = self::currencyMeta($currency);

        return $meta['symbol'].number_format((float) $amount, $meta['decimals']);
    }

    public static function formatMinor(int $minor, string $currency): string
    {
        return self::formatAmount(self::fromMinor($minor, $currency), $currency);
    }

    /** 文案裡的「起價」,給 :price 佔位符用。 */
    public static function entryPrice(?string $currency = null): string
    {
        return self::format(self::entryPlan(), $currency);
    }

    /**
     * 相對於 entry_plan,這個方案每天便宜幾 %。年繳頁面用來標「省 63%」。
     * 沒有省到(或就是 entry_plan 本身)時回 0,呼叫端只要判斷 > 0。
     */
    public static function savingPercent(string $planKey, ?string $currency = null): int
    {
        $entry = self::entryPlan();
        if ($planKey === $entry) {
            return 0;
        }

        $entryPerDay = self::amount($entry, $currency) / max(1, self::days($entry));
        $planPerDay = self::amount($planKey, $currency) / max(1, self::days($planKey));

        if ($entryPerDay <= 0) {
            return 0;
        }

        return max(0, (int) round((1 - $planPerDay / $entryPerDay) * 100));
    }

    private static function isKnownCurrency(mixed $code): bool
    {
        return is_string($code) && is_array(config('premium.currencies.'.$code));
    }
}
