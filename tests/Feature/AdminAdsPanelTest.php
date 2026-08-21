<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 後台的廣告面板。
 *
 * 這一塊守的是「不必翻 .env 就知道廣告現在什麼狀態」:用哪一家、後台在哪、
 * 哪幾個版位還沒設。版位沒設不會有任何錯誤 —— 那一塊只是不出現,而不出現的
 * 廣告永遠不會有人回報,所以狀態必須看得到。
 */
class AdminAdsPanelTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true, 'email_verified_at' => now()]);
    }

    public function test_the_dashboard_links_to_the_active_network(): void
    {
        config(['ads.adapter' => 'exoclick']);

        $this->actingAs($this->admin())
            ->get('/tw/admin')
            ->assertOk()
            ->assertSee('ExoClick')
            ->assertSee(config('ads.networks.exoclick.dashboard'), false)
            // 其他家也要連得到,但不能被誤認成正在用的那一家
            ->assertSee(config('ads.networks.trafficjunky.dashboard'), false);
    }

    public function test_it_shows_which_slots_are_still_empty(): void
    {
        config([
            'ads.adapter' => 'exoclick',
            'ads.exoclick' => ['zone_home_banner' => '123456', 'zone_share' => ''],
        ]);

        $html = $this->actingAs($this->admin())->get('/tw/admin')->assertOk()->getContent();

        $this->assertStringContainsString('1 / 2 個版位已設定', $html);
        $this->assertStringContainsString('is-on', $html);
        $this->assertStringContainsString('is-off', $html);
    }

    public function test_the_ads_txt_state_is_visible(): void
    {
        /* ads.txt 沒設定的時候那個網址回 404 —— 那是刻意的,但從後台看不出來的話,
           會以為是壞了。 */
        config(['ads.txt_lines' => '']);
        $this->actingAs($this->admin())->get('/tw/admin')->assertOk()->assertSee('未設定');

        config(['ads.txt_lines' => 'exoclick.com, 1, DIRECT|google.com, 2, DIRECT']);
        $this->actingAs($this->admin())->get('/tw/admin')->assertOk()->assertSee('（2 行）');
    }

    public function test_adsense_is_marked_as_not_allowed_here(): void
    {
        // 成人內容啟用 AdSense 會違反政策並可能導致帳號停用 —— 連結留著,但要看得出來
        $this->actingAs($this->admin())
            ->get('/tw/admin')
            ->assertOk()
            ->assertSee('is-forbidden', false)
            ->assertSee('違反 AdSense 政策');
    }

    public function test_a_normal_user_cannot_see_the_panel(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($user)->get('/tw/admin')->assertForbidden();
    }
}
