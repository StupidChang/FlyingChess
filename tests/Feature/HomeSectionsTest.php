<?php

namespace Tests\Feature;

use App\Http\Middleware\AgeVerification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 首頁的入口設計。
 *
 * 兩件事互相有關,所以放在同一個檔案:hero 的第二顆按鈕從飛行棋改成測驗,而首頁
 * 不再另開一個測驗區塊 —— 也就是說**那顆按鈕是首頁唯一的測驗入口**(導覽列以外)。
 * 它一旦指錯地方或文案掉了,測驗在首頁就完全沒有出口,而畫面看起來完全正常。
 */
class HomeSectionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(AgeVerification::class);
    }

    public function test_the_hero_second_button_leads_to_the_trait_test(): void
    {
        $html = $this->get('/tw')->assertOk()->getContent();

        /* 範圍只取 hero 的按鈕那一組。從頁首開始切的話會把導覽列包進來 ——
           導覽列本來就連飛行棋,那樣下面那條斷言永遠是紅的。 */
        $from = (int) strpos($html, 'class="hero-btns"');
        $hero = substr($html, $from, (int) strpos($html, '</div>', $from) - $from);

        $this->assertStringContainsString(__('home.hero_cta_test'), $hero, 'hero 的測驗按鈕文案不見了');
        $this->assertStringContainsString(route('trait-test.show'), $hero, 'hero 沒有連到屬性測驗');

        /* 第一顆是「直接開始」:直接開預設棋盤(/play,同機遊玩,不用等人加入),
           第一次來的人按下去就在玩了。不是連到大廳 —— 大廳要先挑棋盤,新手會卡在那一步。 */
        $this->assertStringContainsString('href="'.route('play').'"', $hero, 'hero 的直接開始沒有連到預設棋盤');
        $this->assertStringContainsString(__('home.hero_cta_start'), $hero);
        $this->assertStringNotContainsString(route('games.lobby'), $hero, 'hero 不該連到要先挑棋盤的大廳');
    }

    public function test_the_home_page_has_no_separate_tests_section(): void
    {
        $html = $this->get('/tw')->assertOk()->getContent();

        $this->assertStringNotContainsString('id="tests"', $html);
        $this->assertSame(1, substr_count($html, 'class="game-cards-grid"'), '首頁只該有一個卡片網格');
    }

    public function test_the_game_grid_still_holds_exactly_nine_cards(): void
    {
        /* app.css 的欄數是照 9 張算的(4 欄的時候第 9 張要跨欄置中,3 欄剛好 3/3/3)。
           加減卡片而沒重算那幾條的話,最後一排會孤零零落在左邊 —— 畫面不會壞,
           只會變醜,所以沒有測試就不會有人發現。 */
        $html = $this->get('/tw')->assertOk()->getContent();
        $gridStart = (int) strpos($html, 'class="game-cards-grid"');

        $this->assertSame(
            9,
            substr_count(substr($html, $gridStart), '<article class="game-card'),
            '首頁遊戲網格的卡片數變了 —— app.css 的欄數規則要一起重算'
        );
    }

    public function test_every_locale_has_the_new_button_text(): void
    {
        /* 少一個語系就會在畫面上直接印出「home.hero_cta_test」。fallback 是 zh_TW,
           但 fallback 只在整個 key 缺席時生效,補了反而看不出漏掉哪一個語系。 */
        foreach (array_keys((array) config('app.available_locales')) as $locale) {
            $this->assertNotSame(
                'home.hero_cta_test',
                trans('home.hero_cta_test', [], $locale),
                "{$locale} 少了 hero_cta_test"
            );
        }
    }
}
