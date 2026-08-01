<?php

namespace Tests\Feature;

use App\Http\Middleware\AgeVerification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 後台每一頁都打得開。
 *
 * 後台的版面是共用的(admin._nav、section--sm、admin-table…),所以整理版面
 * 時很容易一次動到好幾頁;沒有這一層,改壞的那一頁要等有人點進去才會發現。
 */
class AdminPagesRenderTest extends TestCase
{
    use RefreshDatabase;

    public static function adminPages(): array
    {
        return [
            '總覽' => ['/tw/admin'],
            '棋盤' => ['/tw/admin/boards'],
            '發佈審核' => ['/tw/admin/board-reviews'],
            '卡片' => ['/tw/admin/cards'],
            '題庫' => ['/tw/admin/prompts'],
            '題庫新增' => ['/tw/admin/prompts/create'],
            '轉盤' => ['/tw/admin/wheel-segments'],
            '轉盤新增' => ['/tw/admin/wheel-segments/create'],
            '會員' => ['/tw/admin/users'],
            '遊戲' => ['/tw/admin/games'],
            '流量' => ['/tw/admin/traffic'],
        ];
    }

    /** @dataProvider adminPages */
    public function test_the_page_renders_for_an_admin(string $url): void
    {
        $this->withoutMiddleware(AgeVerification::class);
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->get($url)
            ->assertOk()
            // 後台導覽列在每一頁,是「有走到共用版面」最便宜的證據。
            ->assertSee('admin-nav');
    }
}
