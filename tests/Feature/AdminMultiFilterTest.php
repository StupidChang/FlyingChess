<?php

namespace Tests\Feature;

use App\Http\Middleware\AgeVerification;
use App\Models\Board;
use App\Models\TruthDareCard;
use App\Models\User;
use App\Models\WheelSegment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 後台的篩選籤可以複選。
 */
class AdminMultiFilterTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $this->withoutMiddleware(AgeVerification::class);

        return User::factory()->create(['is_admin' => true]);
    }

    private function card(string $level, string $content, bool $paid = false): TruthDareCard
    {
        return TruthDareCard::create([
            'category' => 'truth', 'audience' => 'both',
            'level' => $level, 'content' => $content, 'is_paid' => $paid,
        ]);
    }

    public function test_two_levels_can_be_selected_at_once(): void
    {
        $this->card('mild', '輕度的');
        $this->card('medium', '中度的');
        $this->card('intense', '重度的');

        $this->actingAs($this->admin())
            ->get('/tw/admin/cards?level[]=mild&level[]=intense')
            ->assertOk()
            ->assertSee('輕度的')
            ->assertSee('重度的')
            ->assertDontSee('中度的');
    }

    public function test_a_single_value_still_works(): void
    {
        $this->card('mild', '輕度的');
        $this->card('intense', '重度的');

        // 舊連結、書籤、手打的網址都還是 ?level=mild 這種寫法。
        $this->actingAs($this->admin())
            ->get('/tw/admin/cards?level=mild')
            ->assertOk()
            ->assertSee('輕度的')
            ->assertDontSee('重度的');
    }

    public function test_different_filters_narrow_each_other(): void
    {
        $this->card('mild', '輕度免費', false);
        $this->card('mild', '輕度付費', true);
        $this->card('intense', '重度付費', true);

        /* 同一個篩選裡是「或」,不同篩選之間是「且」——
           選了輕度+重度、又選付費,要的是這兩級裡的付費題目。 */
        $this->actingAs($this->admin())
            ->get('/tw/admin/cards?level[]=mild&level[]=intense&paid[]=1')
            ->assertOk()
            ->assertSee('輕度付費')
            ->assertSee('重度付費')
            ->assertDontSee('輕度免費');
    }

    public function test_selecting_both_sides_of_a_yes_no_filter_shows_everything(): void
    {
        $this->card('mild', '免費的', false);
        $this->card('mild', '付費的', true);

        // 免費+付費 = 全部,不該變成什麼都不顯示。
        $this->actingAs($this->admin())
            ->get('/tw/admin/cards?paid[]=0&paid[]=1')
            ->assertOk()
            ->assertSee('免費的')
            ->assertSee('付費的');
    }

    public function test_predicate_filters_are_ored_together(): void
    {
        $admin = User::factory()->create(['name' => '管理員甲', 'is_admin' => true]);
        $banned = User::factory()->create(['name' => '被封鎖的', 'is_banned' => true]);
        $plain = User::factory()->create(['name' => '普通會員']);

        /* 會員的篩選不是同一欄的值,是各自不同的條件,複選時要 OR 起來。 */
        $this->actingAs($this->admin())
            ->get('/tw/admin/users?filter[]=admin&filter[]=banned')
            ->assertOk()
            ->assertSee($admin->name)
            ->assertSee($banned->name)
            ->assertDontSee($plain->name);
    }

    public function test_a_search_still_narrows_the_ored_filters(): void
    {
        User::factory()->create(['name' => '管理員甲', 'is_admin' => true]);
        User::factory()->create(['name' => '管理員乙', 'is_admin' => true]);

        /* OR 起來的那組條件必須包在一個 where 閉包裡,不然 orWhere 會跟搜尋
           平輩,變成「是管理員 或 名字含關鍵字」—— 搜尋等於失效。 */
        $this->actingAs($this->admin())
            ->get('/tw/admin/users?filter[]=admin&filter[]=banned&q=管理員甲')
            ->assertOk()
            ->assertSee('管理員甲')
            ->assertDontSee('管理員乙');
    }

    public function test_board_filters_can_combine(): void
    {
        $template = Board::create(['name' => '範本棋盤', 'is_template' => true]);
        $default = Board::create(['name' => '預設棋盤', 'is_default' => true]);
        $other = Board::create(['name' => '別的棋盤']);

        $this->actingAs($this->admin())
            ->get('/tw/admin/boards?filter[]=template&filter[]=default')
            ->assertOk()
            ->assertSee($template->name)
            ->assertSee($default->name)
            ->assertDontSee($other->name);
    }

    public function test_the_new_middle_levels_are_selectable(): void
    {
        $this->card('mild_plus', '輕中的');
        $this->card('medium_plus', '中重的');
        $this->card('medium', '中度的');

        // 五級之後,新加的兩級也要能單獨篩、也要能跟別級一起篩。
        $this->actingAs($this->admin())
            ->get('/tw/admin/cards?level[]=mild_plus&level[]=medium_plus')
            ->assertOk()
            ->assertSee('輕中的')
            ->assertSee('中重的')
            ->assertDontSee('中度的');
    }

    public function test_an_unknown_filter_value_is_ignored(): void
    {
        WheelSegment::create(['tier' => 'mild', 'content' => '任務']);

        // 篩選是網址參數,白名單以外的值不能進 SQL,也不該讓頁面壞掉。
        $this->actingAs($this->admin())
            ->get('/tw/admin/wheel-segments?tier[]=mild&tier[]='.urlencode("') or 1=1 --"))
            ->assertOk()
            ->assertSee('任務');
    }

    public function test_the_tab_link_toggles_the_value_off_again(): void
    {
        $this->card('mild', '輕度的');

        /* 已經選起來的籤,連結要指向「拿掉它」而不是「再選一次」——
           不然選了就取消不掉,只能手動改網址。 */
        $html = $this->actingAs($this->admin())
            ->get('/tw/admin/cards?level[]=mild')
            ->assertOk()
            ->getContent();

        // 目前選著 mild,那一籤的連結不該再帶 level
        $this->assertMatchesRegularExpression(
            '/href="[^"]*admin\/cards\?(?![^"]*level)[^"]*"[^>]*class="admin-filter-tab active"/',
            $html
        );
    }
}
