<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * 四個語系的語系檔結構要一模一樣:同樣的 key、每個陣列同樣的長度。
 *
 * 測驗題目、量表選項、遊戲詞庫都放在語系檔裡,而計分是照**位置**對到 config
 * 的 —— 少一題、多一個選項,後面每一題就全部錯位,畫面不會壞,只是外語使用者的
 * 結果全部算錯。2026-10-02 全面比對時,日文的隨機暱稱詞庫就少了兩個詞。
 *
 * 只檢查結構,不檢查語意:翻譯有沒有把題目意思翻反,要人工(或逐題對照)確認,
 * 見 memory 的 quiz-question-polarity。
 */
class LangParityTest extends TestCase
{
    /** 英文直接用 Laravel 內建的這幾份,專案裡不放 */
    private const FRAMEWORK_DEFAULTS = ['en' => ['validation.php', 'passwords.php']];

    public function test_every_locale_has_the_same_keys_and_list_lengths_as_zh_tw(): void
    {
        $problems = [];

        foreach (glob(lang_path('zh_TW/*.php')) as $path) {
            $file = basename($path);
            $master = $this->shape(require $path);

            foreach (['zh_CN', 'ja', 'en'] as $locale) {
                if (in_array($file, self::FRAMEWORK_DEFAULTS[$locale] ?? [], true)) {
                    continue;
                }
                $other = lang_path("{$locale}/{$file}");
                if (! is_file($other)) {
                    $problems[] = "{$locale}/{$file} 不存在";

                    continue;
                }
                $shape = $this->shape(require $other);

                foreach (array_diff_key($master, $shape) as $key => $_) {
                    $problems[] = "{$locale}/{$file} 少了 {$key}";
                }
                foreach (array_diff_key($shape, $master) as $key => $_) {
                    $problems[] = "{$locale}/{$file} 多了 {$key}";
                }
                foreach (array_intersect_key($master, $shape) as $key => $len) {
                    if ($len !== null && $shape[$key] !== $len) {
                        $problems[] = "{$locale}/{$file} 的 {$key} 有 {$shape[$key]} 項,繁中是 {$len} 項";
                    }
                }
            }
        }

        $this->assertSame([], $problems, implode("\n", $problems));
    }

    /** key => 陣列長度(字串值記 null),巢狀攤平成 a.b.c */
    private function shape(array $a, string $prefix = ''): array
    {
        $out = [];
        foreach ($a as $k => $v) {
            $key = $prefix === '' ? (string) $k : "{$prefix}.{$k}";
            $out[$key] = is_array($v) ? count($v) : null;
            if (is_array($v)) {
                $out += $this->shape($v, $key);
            }
        }

        return $out;
    }
}
