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

    public function test_a_paid_prompt_is_withheld_from_free_players(): void
    {
        GamePrompt::create(['game' => 'card_game', 'pool' => 'mild', 'content' => '溫和']);
        GamePrompt::create(['game' => 'card_game', 'pool' => 'intense', 'content' => '露骨', 'is_paid' => true]);

        // 題庫搬進資料表之後,付費的界線必須跟著搬,不能因為改了來源就漏掉。
        $this->assertArrayNotHasKey('intense', CardGameService::getActivityPools(false));
        $this->assertSame(['露骨'], CardGameService::getActivityPools(true)['intense']);
    }

    public function test_a_free_prompt_inside_the_intense_pool_reaches_everyone(): void
    {
        /* 收費是每一題自己的欄位,不是整級的屬性 —— 重度裡放一題免費的當樣品
           是合理的用法,以前整級砍掉就做不到。 */
        GamePrompt::create(['game' => 'card_game', 'pool' => 'intense', 'content' => '免費樣品', 'is_paid' => false]);
        GamePrompt::create(['game' => 'card_game', 'pool' => 'intense', 'content' => '付費的', 'is_paid' => true]);

        $free = CardGameService::getActivityPools(false);

        $this->assertSame(['免費樣品'], $free['intense']);
    }

    public function test_a_paid_prompt_inside_a_free_pool_is_withheld(): void
    {
        // 反過來也要成立:中度裡也能有付費題目。
        GamePrompt::create(['game' => 'card_game', 'pool' => 'medium', 'content' => '中度免費']);
        GamePrompt::create(['game' => 'card_game', 'pool' => 'medium', 'content' => '中度付費', 'is_paid' => true]);

        $this->assertSame(['中度免費'], CardGameService::getActivityPools(false)['medium']);
        $this->assertCount(2, CardGameService::getActivityPools(true)['medium']);
    }

    public function test_importing_defaults_marks_the_paid_pools(): void
    {
        GamePrompt::importDefaults('card_game');

        // 匯入的初始界線要跟改版前一樣:原本掛著「(付費)」的那幾池預設收費。
        $this->assertGreaterThan(0, GamePrompt::where('pool', 'intense')->count());
        $this->assertSame(0, GamePrompt::where('pool', 'intense')->where('is_paid', false)->count());
        $this->assertSame(0, GamePrompt::where('pool', 'mild')->where('is_paid', true)->count());
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

    public function test_the_list_colour_codes_the_intensity(): void
    {
        GamePrompt::create(['game' => 'who_most_likely', 'pool' => 'intense', 'content' => '重度題']);

        /* 強度靠顏色分辨。轉盤後台原本輸出的是沒有樣式的 .badge-mild,所以這裡
           驗的是有樣式的那組 class,不是「畫面上有出現分類名稱」。 */
        $this->actingAs($this->admin())->withUnencryptedCookie('age_verified', '1')
            ->get('/tw/admin/prompts?game=who_most_likely')
            ->assertOk()
            ->assertSee('badge-tier--intense');
    }

    public function test_dice_pools_map_onto_the_same_three_levels(): void
    {
        // 骰子的池是「類別.強度」,詞彙不同但要落在同一組顏色上。
        GamePrompt::create(['game' => 'dice_game', 'pool' => 'action.wild', 'content' => '狂野']);

        $this->actingAs($this->admin())->withUnencryptedCookie('age_verified', '1')
            ->get('/tw/admin/prompts?game=dice_game')
            ->assertOk()
            ->assertSee('badge-tier--intense');
    }

    public function test_prompts_are_edited_on_their_own_page_like_the_other_admin_sections(): void
    {
        $prompt = GamePrompt::create(['game' => 'king_game', 'pool' => 'mild', 'content' => '原本的題目']);
        $admin = $this->admin();

        $this->actingAs($admin)->withUnencryptedCookie('age_verified', '1')
            ->get('/tw/admin/prompts/create?game=king_game')->assertOk();

        $this->actingAs($admin)->withUnencryptedCookie('age_verified', '1')
            ->get("/tw/admin/prompts/{$prompt->id}/edit")
            ->assertOk()
            ->assertSee('原本的題目');

        $this->actingAs($admin)->withUnencryptedCookie('age_verified', '1')
            ->patch("/tw/admin/prompts/{$prompt->id}", [
                'game' => 'king_game', 'pool' => 'medium', 'content' => '改過的題目', 'sort_order' => 3,
            ])->assertRedirect();

        $this->assertSame('改過的題目', $prompt->fresh()->content);
        $this->assertSame('medium', $prompt->fresh()->pool);
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
