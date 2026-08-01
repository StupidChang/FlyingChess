<?php

namespace Tests\Feature;

use App\Models\PageView;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 自己記的流量統計。會壞的地方不是「有沒有寫進去」,而是寫進了不該寫的東西 ——
 * 爬蟲、後台自己、被年齡閘擋下的那一次,任何一個漏掉都會讓數字變成噪音。
 */
class TrafficTrackingTest extends TestCase
{
    use RefreshDatabase;

    /** 通過年齡閘的一般訪客。年齡閘會用 200 回應年齡確認頁,那一頁不該被計入。 */
    private function visit(string $url)
    {
        return $this->withUnencryptedCookie('age_verified', '1')
            ->withHeader('User-Agent', 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X)')
            ->get($url);
    }

    public function test_a_normal_page_view_is_recorded_without_the_locale_prefix(): void
    {
        $this->visit('/tw/games')->assertOk();

        $view = PageView::firstOrFail();

        // /tw/games 與 /jp/games 是同一個頁面。分開存的話熱門頁排行會被語系拆散。
        $this->assertSame('/games', $view->path);
        $this->assertSame('zh_TW', $view->locale);
        $this->assertNull($view->user_id);
        $this->assertSame(64, strlen($view->visitor_hash));
    }

    public function test_the_same_page_in_another_locale_shares_one_path(): void
    {
        $this->visit('/tw/games');
        $this->visit('/jp/games');

        $this->assertSame(2, PageView::where('path', '/games')->count());
        $this->assertSame(2, PageView::distinct()->count('locale'));
    }

    public function test_the_age_gate_is_not_counted_as_a_view(): void
    {
        // 沒有 cookie 的訪客會拿到狀態 200 的年齡確認頁 —— 內容不是他要去的
        // 那一頁,計進去會讓每個新訪客的第一次都多算一筆。
        $this->withHeader('User-Agent', 'Mozilla/5.0 (iPhone)')->get('/tw/games')->assertOk();

        $this->assertSame(0, PageView::count());
    }

    public function test_crawlers_are_not_counted(): void
    {
        $this->withUnencryptedCookie('age_verified', '1')
            ->withHeader('User-Agent', 'Mozilla/5.0 (compatible; Googlebot/2.1)')
            ->get('/tw/games');

        $this->assertSame(0, PageView::count());
    }

    public function test_admin_pages_are_not_counted(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->withUnencryptedCookie('age_verified', '1')
            ->withHeader('User-Agent', 'Mozilla/5.0 (Macintosh)')
            ->get('/tw/admin/traffic')->assertOk();

        // 後台自己不計:每看一次流量頁就替它加一筆,數字會愈看愈大。
        $this->assertSame(0, PageView::count());
    }

    public function test_a_logged_in_visit_carries_the_user_id(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->withUnencryptedCookie('age_verified', '1')
            ->withHeader('User-Agent', 'Mozilla/5.0 (Macintosh)')
            ->get('/tw/games');

        $this->assertSame($user->id, PageView::firstOrFail()->user_id);
    }

    public function test_the_traffic_page_is_admin_only(): void
    {
        $this->visit('/tw/admin/traffic')->assertRedirect();

        $plain = User::factory()->create(['is_admin' => false]);
        $this->actingAs($plain)->withUnencryptedCookie('age_verified', '1')
            ->get('/tw/admin/traffic')->assertForbidden();
    }

    public function test_the_traffic_page_only_counts_the_selected_window(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        PageView::create(['path' => '/games', 'locale' => 'zh_TW', 'visitor_hash' => str_repeat('a', 64)]);
        PageView::create(['path' => '/old', 'locale' => 'zh_TW', 'visitor_hash' => str_repeat('b', 64)])
            ->forceFill(['created_at' => now()->subDays(40)])->save();

        $this->actingAs($admin)->withUnencryptedCookie('age_verified', '1')
            ->get('/tw/admin/traffic?days=7')
            ->assertOk()
            ->assertSee('/games')
            ->assertDontSee('/old');
    }
}
