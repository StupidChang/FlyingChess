<?php

namespace Tests\Unit;

use App\Support\LocaleHelper;
use Tests\TestCase;

/**
 * LocaleHelper::defaultLocale() 必須是「站台的主語系」,不能跟著當前請求跑。
 *
 * 這個測試存在的原因是一個實際發生過、而且完全不會報錯的 bug:defaultLocale()
 * 原本讀 config('app.locale'),但 Laravel 的 App::setLocale() 會覆寫那個設定值,
 * 所以 SetLocale 中介層一跑,「預設語系」就變成了「當前語系」。後果是
 * Organization 的 @id 在四個語系各產生一個、sitemap 的 x-default 指錯、
 * 以及 pickTranslation() 永遠回傳主值而不去看翻譯。
 */
class DefaultLocaleTest extends TestCase
{
    public function test_default_locale_does_not_follow_the_current_request_locale(): void
    {
        $default = LocaleHelper::defaultLocale();

        foreach (['ja', 'en', 'zh_CN'] as $locale) {
            app()->setLocale($locale);

            $this->assertSame(
                $default,
                LocaleHelper::defaultLocale(),
                "切換到 {$locale} 之後,defaultLocale() 不該跟著變"
            );
        }
    }

    public function test_setting_the_app_locale_still_changes_the_current_locale(): void
    {
        // 反面確認:上面那條不是靠「setLocale 根本沒作用」而通過的。
        app()->setLocale('ja');
        $this->assertSame('ja', app()->getLocale());
    }

    public function test_the_organization_id_is_the_same_entity_in_every_locale(): void
    {
        $ids = [];

        foreach (['zh_TW', 'en', 'zh_CN', 'ja'] as $locale) {
            app()->setLocale($locale);
            $ids[] = LocaleHelper::localizedUrl(LocaleHelper::defaultLocale(), '').'#organization';
        }

        $this->assertCount(1, array_unique($ids),
            '四個語系必須指向同一個 Organization,否則搜尋與 AI 引擎會當成四個不同的組織');
    }

    public function test_translations_are_read_instead_of_always_falling_back_to_the_master(): void
    {
        // pickTranslation 的「當前語系就是主語系嗎」若永遠成立,翻譯欄位就永遠讀不到。
        app()->setLocale('ja');

        $this->assertSame(
            'ボード',
            LocaleHelper::pickTranslation(['ja' => 'ボード'], '棋盤'),
        );
    }
}
