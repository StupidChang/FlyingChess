<?php

namespace Tests\Feature;

use App\Http\Middleware\AgeVerification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 自訂轉盤獨立成一頁。
 *
 * 原本它是掛在命運轉盤頁下半部的一個區塊:要往下捲很久才看得到,而且那一頁的
 * 標題與描述講的是命運轉盤 —— 等於這個工具沒有自己的搜尋落點。
 */
class CustomWheelPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(AgeVerification::class);
    }

    public function test_the_editor_has_its_own_page(): void
    {
        $this->get('/tw/custom-wheel')
            ->assertOk()
            ->assertSee(__('minigame.cw_title'))
            ->assertSee('cw-root', false);
    }

    public function test_it_no_longer_hangs_off_the_wheel_game_page(): void
    {
        $this->get('/tw/wheel-game')
            ->assertOk()
            ->assertDontSee('cw-root', false)
            // 但要連得過去 —— 拆開之後兩邊都走得到才不會有人迷路
            ->assertSee(route('custom-wheel.page'), false);
    }

    public function test_the_page_carries_its_own_seo(): void
    {
        $html = $this->get('/tw/custom-wheel')->assertOk()->getContent();

        $this->assertStringContainsString('<title>'.__('minigame.cw_seo_title'), $html);
        $this->assertStringContainsString(__('minigame.cw_seo_description'), $html);
        $this->assertStringContainsString('rel="canonical" href="'.route('custom-wheel.page').'"', $html);
        $this->assertStringContainsString('"@type":"WebApplication"', $html);
    }

    public function test_anyone_can_use_it_but_saving_needs_an_account(): void
    {
        // 不登入也玩得到(資料先留在瀏覽器),要存進帳號才需要登入
        $this->get('/tw/custom-wheel')->assertOk();
        $this->post('/tw/my-wheels', ['name' => '測試', 'items' => [['t' => 'a', 'p' => 50], ['t' => 'b', 'p' => 50]]])
            ->assertRedirect();

        $this->actingAs(User::factory()->create(['email_verified_at' => now()]))
            ->withCredentials()
            ->postJson('/tw/my-wheels', ['name' => '測試', 'items' => [['t' => 'a', 'p' => 50], ['t' => 'b', 'p' => 50]]])
            ->assertOk();
    }

    public function test_the_page_is_in_the_sitemap(): void
    {
        $this->get('/sitemap-tw.xml')->assertOk()->assertSee('/custom-wheel</loc>', false);
    }

    public function test_the_profile_links_to_the_page_not_the_old_anchor(): void
    {
        $this->actingAs(User::factory()->create())->get('/tw/profile')
            ->assertOk()
            ->assertSee(route('custom-wheel.page'), false)
            ->assertDontSee('#cw-root', false);
    }
}
