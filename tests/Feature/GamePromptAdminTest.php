<?php

namespace Tests\Feature;

use App\Models\GamePrompt;
use App\Models\User;
use App\Services\CardGameService;
use App\Services\DiceGameService;
use App\Services\KingGameService;
use App\Services\WhoMostLikelyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 四個小遊戲的題庫改由後台管理。
 *
 * 最重要的一條不是「後台改得動」,而是**資料表空的時候遊戲照樣能玩** ——
 * 全新環境、測試資料庫、或有人把某個遊戲的題目刪光,都不該讓遊戲拿到空題庫。
 */
class GamePromptAdminTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    public function test_an_empty_table_falls_back_to_the_code_defaults(): void
    {
        $this->assertSame(0, GamePrompt::count());

        $this->assertNotEmpty(WhoMostLikelyService::getPromptPools()['mild']);
        $this->assertNotEmpty(CardGameService::getActivityPools()['mild']);
        $this->assertNotEmpty(KingGameService::getCommandPools()['mild']);
        $this->assertNotEmpty(DiceGameService::getBuiltInDice()[0]['faces']);
    }

    public function test_rows_in_the_table_replace_the_defaults(): void
    {
        GamePrompt::create(['game' => 'who_most_likely', 'pool' => 'mild', 'content' => '只有這一題']);

        $pools = WhoMostLikelyService::getPromptPools();

        $this->assertSame(['只有這一題'], $pools['mild']);
    }

    public function test_the_intense_pool_still_needs_premium(): void
    {
        GamePrompt::create(['game' => 'card_game', 'pool' => 'mild', 'content' => '溫和']);
        GamePrompt::create(['game' => 'card_game', 'pool' => 'intense', 'content' => '露骨']);

        // 題庫搬進資料表之後,分級的界線必須跟著搬,不能因為改了來源就漏掉。
        $this->assertArrayNotHasKey('intense', CardGameService::getActivityPools(false));
        $this->assertSame(['露骨'], CardGameService::getActivityPools(true)['intense']);
    }

    public function test_dice_faces_come_from_the_table_per_category(): void
    {
        GamePrompt::create(['game' => 'dice_game', 'pool' => 'action.gentle', 'content' => '牽手']);

        $gentle = collect(DiceGameService::getBuiltInDice())
            ->firstWhere('id', 'builtin_action_gentle');

        $this->assertSame(['牽手'], $gentle['faces']);
    }

    public function test_importing_defaults_fills_the_table_once(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->withUnencryptedCookie('age_verified', '1')
            ->post('/tw/admin/prompts/import', ['game' => 'king_game'])
            ->assertRedirect();

        $first = GamePrompt::where('game', 'king_game')->count();
        $this->assertGreaterThan(0, $first);

        // 再按一次不能把題庫灌成兩份。
        $this->actingAs($admin)->withUnencryptedCookie('age_verified', '1')
            ->post('/tw/admin/prompts/import', ['game' => 'king_game']);

        $this->assertSame($first, GamePrompt::where('game', 'king_game')->count());
    }

    public function test_a_pool_from_another_game_is_rejected(): void
    {
        // pool 的合法值取決於 game。骰子的分類不該能存進「誰最有可能」。
        $this->actingAs($this->admin())->withUnencryptedCookie('age_verified', '1')
            ->post('/tw/admin/prompts', [
                'game' => 'who_most_likely',
                'pool' => 'action.gentle',
                'content' => '不該過',
            ])
            ->assertSessionHasErrors('pool');

        $this->assertSame(0, GamePrompt::count());
    }

    public function test_the_page_is_admin_only(): void
    {
        $this->withUnencryptedCookie('age_verified', '1')->get('/tw/admin/prompts')->assertRedirect();

        $this->actingAs(User::factory()->create(['is_admin' => false]))
            ->withUnencryptedCookie('age_verified', '1')
            ->get('/tw/admin/prompts')->assertForbidden();

        $this->actingAs($this->admin())->withUnencryptedCookie('age_verified', '1')
            ->get('/tw/admin/prompts')->assertOk();
    }
}
