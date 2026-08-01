<?php

namespace Tests\Feature;

use App\Http\Middleware\AgeVerification;
use App\Models\TruthDareCard;
use App\Models\User;
use App\Models\WheelSegment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * 後台列表可以依欄位排序。
 */
class AdminSortingTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $this->withoutMiddleware(AgeVerification::class);

        return User::factory()->create(['is_admin' => true]);
    }

    /** 列表上出現的順序。 */
    private function order(string $url, string $key): array
    {
        return $this->actingAs($this->admin())->get($url)
            ->assertOk()
            ->viewData($key)
            ->pluck('id')->all();
    }

    public static function everySortKey(): array
    {
        return [
            '卡片・類型' => ['/tw/admin/cards', 'category'],
            '卡片・適用' => ['/tw/admin/cards', 'audience'],
            '卡片・等級' => ['/tw/admin/cards', 'tier'],
            '卡片・內容' => ['/tw/admin/cards', 'content'],
            '題庫・分類' => ['/tw/admin/prompts', 'pool'],
            '題庫・排序' => ['/tw/admin/prompts', 'sort_order'],
            '轉盤・強度' => ['/tw/admin/wheel-segments', 'tier'],
            '場次・狀態' => ['/tw/admin/games', 'status'],
            '場次・玩家數' => ['/tw/admin/games', 'players'],
            '場次・代碼' => ['/tw/admin/games', 'code'],
            '會員・棋盤數' => ['/tw/admin/users', 'boards'],
            '會員・Email' => ['/tw/admin/users', 'email'],
            '棋盤・格子數' => ['/tw/admin/boards', 'squares'],
            '棋盤・名稱' => ['/tw/admin/boards', 'name'],
        ];
    }

    /**
     * 每一個排序鍵都真的查得動。
     *
     * 玩家數與棋盤數排的是 withCount 產生的別名,別名寫錯不會有人發現 ——
     * 直到有人按下那一欄才炸開。
     */
    #[DataProvider('everySortKey')]
    public function test_the_sort_key_runs(string $url, string $key): void
    {
        $admin = $this->admin();

        foreach (['asc', 'desc'] as $dir) {
            $this->actingAs($admin)->get("{$url}?sort={$key}&dir={$dir}")->assertOk();
        }
    }

    public function test_cards_sort_by_audience(): void
    {
        $party = TruthDareCard::create(['category' => 'truth', 'audience' => 'party', 'content' => '多人', 'tier' => 'free']);
        $both = TruthDareCard::create(['category' => 'truth', 'audience' => 'both', 'content' => '通用', 'tier' => 'free']);
        $couple = TruthDareCard::create(['category' => 'truth', 'audience' => 'couple', 'content' => '情侶', 'tier' => 'free']);

        // 通用 → 情侶 → 多人,與下拉選單同一個順序。
        $this->assertSame(
            [$both->id, $couple->id, $party->id],
            $this->order('/tw/admin/cards?sort=audience&dir=asc', 'cards')
        );
    }

    public function test_intensity_sorts_by_how_intense_it_is_not_alphabetically(): void
    {
        $intense = WheelSegment::create(['tier' => 'intense', 'content' => '大膽']);
        $mild = WheelSegment::create(['tier' => 'mild', 'content' => '輕鬆']);
        $medium = WheelSegment::create(['tier' => 'medium', 'content' => '親密']);

        /* 照字串排的話 intense < medium < mild,「大膽」會排在「輕鬆」前面 ——
           那不是任何人按下「強度」時想看到的順序。 */
        $this->assertSame(
            [$mild->id, $medium->id, $intense->id],
            $this->order('/tw/admin/wheel-segments?sort=tier&dir=asc', 'segments')
        );
    }

    public function test_the_direction_flips(): void
    {
        $mild = WheelSegment::create(['tier' => 'mild', 'content' => '輕鬆']);
        $intense = WheelSegment::create(['tier' => 'intense', 'content' => '大膽']);

        $this->assertSame(
            [$intense->id, $mild->id],
            $this->order('/tw/admin/wheel-segments?sort=tier&dir=desc', 'segments')
        );
    }

    public function test_an_unknown_sort_key_falls_back_instead_of_reaching_sql(): void
    {
        WheelSegment::create(['tier' => 'mild', 'content' => '輕鬆']);

        /* sort 是網址參數。白名單以外的值必須直接被丟掉 —— 如果它進得了
           order by,那就是一個 SQL injection。 */
        $this->actingAs($this->admin())
            ->get('/tw/admin/wheel-segments?sort=content)+--&dir=asc')
            ->assertOk();

        $this->actingAs($this->admin())
            ->get('/tw/admin/cards?sort=(select+1)&dir=asc')
            ->assertOk();
    }

    public function test_sorting_keeps_the_current_filter(): void
    {
        WheelSegment::create(['tier' => 'mild', 'content' => '留下的']);
        WheelSegment::create(['tier' => 'intense', 'content' => '濾掉的']);

        // 排序不該把使用者剛設好的篩選洗掉。
        $this->actingAs($this->admin())
            ->get('/tw/admin/wheel-segments?tier=mild&sort=content&dir=asc')
            ->assertOk()
            ->assertSee('留下的')
            ->assertDontSee('濾掉的');
    }

    public function test_the_header_link_carries_the_filter_and_resets_the_page(): void
    {
        foreach (range(1, 3) as $i) {
            WheelSegment::create(['tier' => 'mild', 'content' => "任務 {$i}"]);
        }

        /* 換排序還停在第 5 頁看到的是另一批資料。連結要把 page 清掉,
           但保留 tier 這類篩選。 */
        $html = $this->actingAs($this->admin())
            ->get('/tw/admin/wheel-segments?tier=mild&page=1')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('tier=mild', $html);
        $this->assertStringContainsString('sort=content', $html);
        $this->assertStringNotContainsString('sort=content&amp;dir=asc&amp;page=', $html);
    }
}
