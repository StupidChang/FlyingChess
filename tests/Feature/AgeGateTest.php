<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 年齡閘的兩種形式。見 config/content.php 的 age_gate_mode。
 *
 * 這裡真正要守的是 test_googlebot_and_an_unverified_human_get_the_same_page:
 * 舊做法是依 User-Agent 分岔 —— Googlebot 拿到完整內容、真人拿到一頁閘門 ——
 * 那正是 Google 對 cloaking 的定義。改成覆蓋層就是為了消掉那個分岔,
 * 而「分岔有沒有偷偷長回來」不是看程式碼看得出來的,要靠這條測試。
 */
class AgeGateTest extends TestCase
{
    use RefreshDatabase;

    private const HUMAN = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/120 Safari/537.36';

    /** 遊戲大廳自己的 H1。閘門頁上不會有這一句,所以它是「有沒有拿到真內容」的判準。 */
    private function realContent(): string
    {
        return __('seo.lobby_title');
    }

    private function visit(string $ua, bool $verified = false)
    {
        $test = $this->withHeader('User-Agent', $ua);

        if ($verified) {
            $test = $test->withUnencryptedCookie('age_verified', '1');
        }

        return $test->get('/tw/game-hall');
    }

    public function test_an_unverified_visitor_gets_the_real_page_with_the_overlay_on_top(): void
    {
        $response = $this->visit(self::HUMAN);

        $response->assertOk()
            ->assertSee($this->realContent(), false)   // 內容真的在 HTML 裡
            ->assertSee('class="age-overlay"', false)  // 但上面蓋著覆蓋層
            ->assertSee('age-locked', false);          // 而且捲動被鎖住
    }

    public function test_a_verified_visitor_sees_no_overlay(): void
    {
        $this->visit(self::HUMAN, verified: true)
            ->assertOk()
            ->assertSee($this->realContent(), false)
            ->assertDontSee('age-overlay', false)
            ->assertDontSee('age-locked', false);
    }

    public function test_googlebot_and_an_unverified_human_get_the_same_page(): void
    {
        /* 這是整個改動的重點。兩邊都必須拿到「完整內容 + 覆蓋層」——
           只要哪一天有人為了某個理由再加一條 UA 判斷,這裡就會紅。 */
        $bot = $this->visit('Googlebot/2.1 (+http://www.google.com/bot.html)');
        $human = $this->visit(self::HUMAN);

        foreach ([$bot, $human] as $response) {
            $response->assertOk()
                ->assertSee($this->realContent(), false)
                ->assertSee('class="age-overlay"', false)
                ->assertDontSee('class="age-gate"', false);   // 獨立閘門頁的標記
        }
    }

    public function test_training_crawlers_still_get_nothing(): void
    {
        /* 訓練型爬蟲的政策沒有變:不給內容。這不是 cloaking —— 它們不是搜尋引擎,
           擋掉它們純粹是 robots 政策,robots.txt 也一併 Disallow。 */
        $this->visit('Mozilla/5.0 (compatible; GPTBot/1.2; +https://openai.com/gptbot)')
            ->assertOk()
            ->assertSee('class="age-gate"', false)
            ->assertDontSee($this->realContent(), false);
    }

    public function test_site_ripping_tools_are_still_refused(): void
    {
        $this->visit('Mozilla/4.0 (compatible; HTTrack 3.0)')->assertForbidden();
    }

    public function test_an_unverified_visitor_cannot_post(): void
    {
        /* 覆蓋層是畫面上的東西,擋不住直接對端點送 POST 的人 ——
           所以伺服器這邊的後備防線要留著,不能因為改成覆蓋層就一起拿掉。 */
        $this->withHeader('User-Agent', self::HUMAN)
            ->post('/tw/dual-control', ['a' => array_fill(0, 64, 2)])
            ->assertOk()                                 // 閘門頁是頁面不是錯誤
            ->assertSee('class="age-gate"', false);
    }

    public function test_confirming_age_sets_the_cookie_and_returns_to_the_page(): void
    {
        $this->withHeader('User-Agent', self::HUMAN)
            ->post('/age-verify')
            ->assertRedirect()
            // 這個 cookie 不走 Laravel 的 cookie 加密(中介層是直接讀原始值比對),
            // 所以斷言時要關掉解密,否則 assertCookie 會先嘗試解密而炸掉。
            ->assertCookie('age_verified', '1', false);
    }

    public function test_interstitial_mode_restores_the_old_behaviour(): void
    {
        /* 廣告聯播網若要求「確認年齡前不得看到任何內容」,設定切回去就好。
           切回去之後爬蟲必須仍然看得到內容,不然本站對搜尋引擎等於不存在。 */
        config(['content.age_gate_mode' => 'interstitial']);

        $this->visit(self::HUMAN)
            ->assertOk()
            ->assertSee('class="age-gate"', false)
            ->assertDontSee($this->realContent(), false);

        $this->visit('Googlebot/2.1')
            ->assertOk()
            ->assertSee($this->realContent(), false)
            ->assertDontSee('class="age-gate"', false);
    }
}
