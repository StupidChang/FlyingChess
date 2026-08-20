<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\GamePlayer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * 看廣告換一段時間的付費內容。
 *
 * 這裡守兩件事:
 *
 * 1. 兌換不能被隨手繞過。最短觀看秒數由伺服器判斷、憑證只能用一次。
 *    (真正的防作弊要靠聯播網的 S2S callback,見 PremiumAccess::issueAdToken)
 *
 * 2. 換到的是**內容**,不是會員。看完廣告仍然要看得到廣告、遊玩紀錄仍然只有
 *    免費額度 —— 這條界線一旦被寫壞,等於把付費會員的價值送掉。
 */
class RewardedUnlockTest extends TestCase
{
    use RefreshDatabase;

    /** AgeVerification 會擋掉沒確認過年齡的寫入請求 —— 見 TestCase::asAgeVerified。 */
    private function asVisitor()
    {
        return $this->asAgeVerified();
    }

    private function startAd(): string
    {
        $response = $this->asVisitor()->postJson('/tw/ad-unlock/start')->assertOk();

        return $response->json('token');
    }

    public function test_the_hint_is_shown_to_visitors_who_have_not_paid(): void
    {
        $this->asVisitor()->get('/tw/who-most-likely')
            ->assertOk()
            ->assertSee(__('minigame.rewarded_hint', ['minutes' => 30]), false);
    }

    public function test_the_hint_is_hidden_from_paying_members(): void
    {
        $user = User::factory()->create(['premium_expires_at' => now()->addDays(10)]);

        $this->actingAs($user)->asVisitor()->get('/tw/who-most-likely')
            ->assertOk()
            ->assertDontSee('rw-bar', false);
    }

    public function test_claiming_before_the_ad_finished_is_rejected(): void
    {
        $token = $this->startAd();

        // 立刻兌換 —— 廣告根本還沒播完。
        $this->asVisitor()->postJson('/tw/ad-unlock/claim', ['token' => $token])
            ->assertStatus(422)
            ->assertJson(['ok' => false]);
    }

    public function test_a_forged_token_is_rejected(): void
    {
        $this->startAd();
        $this->travel(30)->seconds();

        $this->asVisitor()->postJson('/tw/ad-unlock/claim', ['token' => 'not-the-real-token'])
            ->assertStatus(422);
    }

    public function test_watching_the_ad_unlocks_the_premium_content(): void
    {
        // 解鎖前:免費版的題庫。
        $this->asVisitor()->get('/tw/who-most-likely')
            ->assertOk()
            ->assertViewHas('isPremium', false);

        $token = $this->startAd();
        $this->travel(20)->seconds();

        $this->asVisitor()->postJson('/tw/ad-unlock/claim', ['token' => $token])
            ->assertOk()
            ->assertJson(['ok' => true, 'minutes' => 30]);

        // 解鎖後:同一個訪客(沒有帳號)拿得到付費內容。
        $this->asVisitor()->get('/tw/who-most-likely')
            ->assertOk()
            ->assertViewHas('isPremium', true);
    }

    public function test_the_unlock_expires(): void
    {
        $token = $this->startAd();
        $this->travel(20)->seconds();
        $this->asVisitor()->postJson('/tw/ad-unlock/claim', ['token' => $token])->assertOk();

        $this->travel(31)->minutes();

        $this->asVisitor()->get('/tw/dice-game')
            ->assertOk()
            ->assertViewHas('isPremium', false);
    }

    public function test_a_token_cannot_be_redeemed_twice(): void
    {
        $token = $this->startAd();
        $this->travel(20)->seconds();

        $this->asVisitor()->postJson('/tw/ad-unlock/claim', ['token' => $token])->assertOk();
        // 同一張憑證再兌換一次,不該再換到 30 分鐘。
        $this->asVisitor()->postJson('/tw/ad-unlock/claim', ['token' => $token])->assertStatus(422);
    }

    public function test_watching_a_second_ad_extends_rather_than_resets(): void
    {
        $first = $this->startAd();
        $this->travel(20)->seconds();
        $this->asVisitor()->postJson('/tw/ad-unlock/claim', ['token' => $first])->assertOk();

        // 還剩約 29 分鐘時再看一支,應該累加而不是把剩下的洗掉。
        $this->travel(1)->minutes();
        $second = $this->startAd();
        $this->travel(20)->seconds();

        $left = $this->asVisitor()->postJson('/tw/ad-unlock/claim', ['token' => $second])
            ->assertOk()->json('secondsLeft');

        $this->assertGreaterThan(30 * 60, $left, '第二支廣告應該疊加在剩餘時間上');
    }

    public function test_the_unlock_grants_content_but_not_membership(): void
    {
        // 這是整個機制最容易寫壞的地方:看廣告換到的是內容,不是會員。
        config()->set('premium.free_history_limit', 5);
        $user = User::factory()->create();

        for ($i = 0; $i < 8; $i++) {
            $game = Game::create([
                'code' => str_pad((string) $i, 8, 'G', STR_PAD_LEFT),
                'game_type' => 'flying_chess', 'status' => 'finished', 'max_players' => 4,
            ]);
            GamePlayer::create([
                'game_id' => $game->id, 'user_id' => $user->id,
                'session_id' => 'sess-'.$i, 'player_name' => 'P'.$i,
                'color' => 'red', 'is_host' => true,
            ]);
        }

        $token = $this->actingAs($user)->startAd();
        $this->travel(20)->seconds();
        $this->actingAs($user)->asVisitor()
            ->postJson('/tw/ad-unlock/claim', ['token' => $token])->assertOk();

        // 內容解鎖了……
        $this->actingAs($user)->asVisitor()->get('/tw/dice-game')
            ->assertOk()->assertViewHas('isPremium', true);

        // ……但個人頁的遊玩紀錄仍然只有免費額度,時間軸也還是鎖著。
        $this->actingAs($user)->asVisitor()->get('/tw/profile')
            ->assertOk()
            ->assertViewHas('isPremium', false)
            ->assertViewHas('timeline', null)
            ->assertViewHas('hiddenPlays', 3);
    }

    // ── 每日上限 ─────────────────────────────────────────

    /**
     * 換一次:發憑證 → 等過最短觀看秒數 → 兌換。
     *
     * 刻意不用 startAd():額度用完之後 start 不會發憑證,那個 helper 會在
     * 「回傳 string」的型別上先炸掉,測不到我們想測的 422。
     */
    private function redeemOnce(): TestResponse
    {
        $token = $this->asVisitor()->postJson('/tw/ad-unlock/start')->json('token');
        $this->travel(20)->seconds();

        return $this->asVisitor()->postJson('/tw/ad-unlock/claim', ['token' => $token]);
    }

    public function test_the_daily_limit_stops_the_endless_start_wait_claim_loop(): void
    {
        /* 這是這個功能真正的漏洞:最短觀看秒數只證明「有等 15 秒」,不證明
           「有看廣告」。少了上限,15 秒換 30 分鐘可以無限重複。 */
        config(['premium.rewarded.max_per_day' => 3]);

        for ($i = 0; $i < 3; $i++) {
            $this->redeemOnce()->assertOk();
        }

        $this->redeemOnce()
            ->assertStatus(422)
            ->assertJson(['ok' => false, 'limitReached' => true, 'dailyLimit' => 3]);
    }

    public function test_the_ad_is_not_even_served_once_the_limit_is_reached(): void
    {
        /* 額度用完之後 start 就不發憑證了。先播再拒絕是最糟的順序:使用者看完
           15 秒才被告知換不到,而那次曝光也拿不回來。 */
        config(['premium.rewarded.max_per_day' => 1]);

        $this->redeemOnce()->assertOk();

        $this->asVisitor()->postJson('/tw/ad-unlock/start')
            ->assertOk()
            ->assertJson(['limitReached' => true, 'usedToday' => 1, 'dailyLimit' => 1])
            ->assertJsonMissing(['token' => true]);
    }

    public function test_a_token_taken_before_the_limit_cannot_be_cashed_in_after_it(): void
    {
        /* 憑證發完會留在 session 裡,所以「先拿一張、把額度用完、再回來領」
           這條路要在兌換的那一刻堵住,不能只擋在 start()。 */
        config(['premium.rewarded.max_per_day' => 1]);

        $early = $this->startAd();

        $this->redeemOnce()->assertOk();   // 額度用掉(這一次會換掉 session 裡的憑證)

        $this->asVisitor()->postJson('/tw/ad-unlock/claim', ['token' => $early])
            ->assertStatus(422);
    }

    public function test_the_count_resets_the_next_day(): void
    {
        config(['premium.rewarded.max_per_day' => 1]);

        $this->redeemOnce()->assertOk();
        $this->redeemOnce()->assertStatus(422);

        // 跨日自動歸零,不需要排程去清
        $this->travel(1)->day();

        $this->redeemOnce()->assertOk();
    }

    public function test_the_limit_can_be_switched_off(): void
    {
        config(['premium.rewarded.max_per_day' => 0]);

        for ($i = 0; $i < 4; $i++) {
            $this->redeemOnce()->assertOk();
        }
    }
}
