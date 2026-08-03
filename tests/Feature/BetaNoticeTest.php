<?php

namespace Tests\Feature;

use App\Http\Middleware\AgeVerification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 測試版公告。
 *
 * 站台還在測試階段,右下角(手機是底部細條)要有一個可關閉的提示,說明題目與功能
 * 還會變、資料可能重置。重點在「上線時關得掉」與「年齡確認那一頁不要跟著出現」。
 */
class BetaNoticeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(AgeVerification::class);
    }

    public function test_it_shows_on_a_normal_page(): void
    {
        config(['beta.notice' => true]);

        $this->get('/tw')
            ->assertOk()
            ->assertSee('beta-note', false)
            ->assertSee(__('ui.beta_badge'))
            ->assertSee(__('ui.beta_title'))
            ->assertSee(__('ui.beta_desc'))
            ->assertSee(__('ui.beta_close'));
    }

    public function test_it_rides_the_layout_so_every_page_has_it(): void
    {
        config(['beta.notice' => true]);

        foreach (['/tw/game-hall', '/tw/wheel-game', '/tw/custom-wheel'] as $path) {
            $this->get($path)->assertOk()->assertSee('beta-note', false);
        }
    }

    public function test_going_live_only_takes_one_env_flag(): void
    {
        config(['beta.notice' => false]);

        $this->get('/tw')
            ->assertOk()
            ->assertDontSee('beta-note', false)
            ->assertDontSee(__('ui.beta_title'));
    }

    public function test_the_dismissal_is_remembered_per_version(): void
    {
        config(['beta.notice' => true, 'beta.notice_version' => '7']);

        // 版號進到 localStorage 的 key 裡 —— 版號一動,關過的人會重新看到一次
        $this->get('/tw')->assertOk()->assertSee('data-key="beta_notice_v7"', false);
    }

    public function test_the_report_link_appears_only_with_a_support_address(): void
    {
        config(['beta.notice' => true, 'support.email' => 'help@example.com']);
        $this->get('/tw')->assertOk()->assertSee('mailto:help@example.com', false);

        // 沒有信箱就不要留一個死路連結
        config(['support.email' => '']);
        $this->get('/tw')
            ->assertOk()
            ->assertDontSee(__('ui.beta_report'))
            ->assertDontSee('mailto:', false);
    }

    public function test_the_age_gate_stays_clean(): void
    {
        config(['beta.notice' => true]);

        // 這一頁是獨立版型,而且是使用者看到的第一個畫面 —— 不要在上面再疊一張卡
        $this->withMiddleware(AgeVerification::class)
            ->get('/tw')
            ->assertOk()
            ->assertSee(__('legal.age_gate_title'))
            ->assertDontSee('beta-note', false);
    }

    public function test_it_is_hidden_until_the_script_decides(): void
    {
        config(['beta.notice' => true]);

        // 帶 hidden 出場:已經關掉的人不會看到任何一格閃現
        $this->get('/tw')->assertOk()->assertSee('data-key="beta_notice_v1" hidden', false);
    }
}
