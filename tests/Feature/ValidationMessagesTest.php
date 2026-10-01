<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Laravel 12 只內建英文的驗證訊息。其他語系少了 lang/{locale}/validation.php,
 * 沒寫自訂訊息的表單就會把 key 原樣印出來 ——「validation.required」。
 */
class ValidationMessagesTest extends TestCase
{
    private const LOCALES = ['zh_TW', 'zh_CN', 'ja'];

    private function flatten(array $a, string $prefix = ''): array
    {
        $out = [];
        foreach ($a as $k => $v) {
            $key = $prefix === '' ? (string) $k : "{$prefix}.{$k}";
            is_array($v) && $v !== [] ? $out += $this->flatten($v, $key) : $out[$key] = $v;
        }

        return $out;
    }

    public function test_every_locale_covers_every_framework_rule(): void
    {
        $vendor = base_path('vendor/laravel/framework/src/Illuminate/Translation/lang/en');

        foreach (['validation', 'passwords'] as $file) {
            $expected = array_keys($this->flatten(require "{$vendor}/{$file}.php"));
            $expected = array_filter($expected, fn ($k) => ! str_starts_with($k, 'custom.') && $k !== 'attributes');

            foreach (self::LOCALES as $locale) {
                $have = $this->flatten(require lang_path("{$locale}/{$file}.php"));
                $missing = array_diff($expected, array_keys($have));

                $this->assertSame([], array_values($missing), "{$locale}/{$file}.php 少了這些鍵");
            }
        }
    }

    public function test_every_locale_names_the_same_fields(): void
    {
        // fallback_locale 是 zh_TW:哪個語系漏了一個欄位名,就會冒出一個中文欄位名
        $base = array_keys((require lang_path('zh_TW/validation.php'))['attributes']);

        foreach (['en', 'zh_CN', 'ja'] as $locale) {
            $have = array_keys((require lang_path("{$locale}/validation.php"))['attributes']);
            $this->assertSame([], array_values(array_diff($base, $have)), "{$locale} 少了欄位名");
        }
    }

    public function test_no_locale_prints_a_raw_key(): void
    {
        foreach (['en', ...self::LOCALES] as $locale) {
            app()->setLocale($locale);

            $errors = validator(
                ['message' => 'a', 'email' => 'x'],
                ['message' => 'required|min:3', 'email' => 'email', 'name' => 'required'],
            )->errors()->all();

            $this->assertCount(3, $errors);
            foreach ($errors as $error) {
                $this->assertStringNotContainsString('validation.', $error, $locale);
            }
            $this->assertStringNotContainsString('passwords.', __('passwords.token'), $locale);
        }
    }
}
