<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\GamePlayer;
use App\Models\PaymentOrder;
use App\Models\User;
use App\Support\Pricing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 兩件事的迴歸測試:
 *
 * 1. 幣別與方案從 17 個硬寫的「NT$」抽到 config/premium.php。最容易復發的錯誤是
 *    有人為了方便,又在某個語言檔或 Blade 裡寫回幣別符號 —— 所以下面直接掃檔案。
 *
 * 2. 免費會員只保留最近 N 場遊玩紀錄。這種額度限制寫錯的方向通常是「看起來對」:
 *    列表確實只有 5 筆,但總數算錯,或是付費之後沒有真的解鎖。
 */
class PricingAndHistoryTest extends TestCase
{
    use RefreshDatabase;

    /**
     * AgeVerification 在 web 群組裡,會用「狀態 200 的年齡確認頁」回應沒有通過
     * 年齡閘的請求 —— assertOk() 會過,但 view 根本不是要測的那一頁。餵一個白名單
     * 的爬蟲 UA 是中介層自己認可的放行方式,也讓請求不依賴 cookie。
     * 見 AgeVerification::CRAWLER_PATTERNS 與 PublicRoutesSmokeTest 的同款註解。
     */
    private function visit(string $url)
    {
        return $this->withHeader('User-Agent', 'Googlebot')->get($url);
    }

    private function submit(string $url, array $data = [])
    {
        return $this->withHeader('User-Agent', 'Googlebot')->post($url, $data);
    }

    // ── 幣別與方案 ──────────────────────────────────────────

    public function test_minor_units_survive_the_float_round_trip(): void
    {
        // (int)(7.99 * 100) 在浮點數下是 798。這行就是在釘住那個坑。
        $this->assertSame(799, Pricing::toMinor(7.99, 'USD'));
        $this->assertSame(3499, Pricing::toMinor(34.99, 'USD'));
        // 零小數位的幣別,最小單位等於元。
        $this->assertSame(249, Pricing::toMinor(249, 'TWD'));
        $this->assertSame(7.99, Pricing::fromMinor(799, 'USD'));
        $this->assertSame(249, Pricing::fromMinor(249, 'TWD'));
    }

    public function test_format_carries_the_currency_symbol(): void
    {
        $this->assertSame('US$34.99', Pricing::formatAmount(34.99, 'USD'));
        $this->assertSame('NT$1,090', Pricing::formatAmount(1090, 'TWD'));
        $this->assertSame('¥5,200', Pricing::formatAmount(5200, 'JPY'));
    }

    public function test_unmapped_locales_fall_back_to_the_settlement_currency(): void
    {
        // 顯示的幣別必須是真的收得到的幣別:沒有在 locale_currency 指定的語系
        // 一律用 default_currency,而不是猜一個。
        config()->set('premium.locale_currency', ['zh_TW' => 'TWD']);
        config()->set('premium.default_currency', 'USD');

        $this->assertSame('TWD', Pricing::currency('zh_TW'));
        $this->assertSame('USD', Pricing::currency('ja'));
        $this->assertSame('USD', Pricing::currency('en'));
    }

    public function test_no_currency_symbol_is_hardcoded_in_language_files(): void
    {
        $offenders = [];

        foreach (glob(lang_path('*/*.php')) as $file) {
            $body = file_get_contents($file);
            // 註解不算 —— 有些註解會引用 "NT$249" 當例子說明。
            $body = preg_replace('#//.*$|/\*.*?\*/#ms', '', $body);

            if (preg_match('/(NT\$|US\$|¥)/u', $body)) {
                $offenders[] = str_replace(base_path().'/', '', $file);
            }
        }

        $this->assertSame([], $offenders,
            '語言檔不該寫死幣別符號,價格請用 :price 佔位符並由 Pricing 帶入');
    }

    public function test_checkout_prices_from_config_not_from_the_request(): void
    {
        config()->set('premium.default_currency', 'TWD');
        $user = User::factory()->create();

        // 前端送一個被竄改的金額,以及一個不存在的方案。
        $this->actingAs($user)->submit('/tw/premium/checkout', [
            'plan' => 'yearly',
            'amount' => 1,
            'price' => 1,
            'consent' => '1',
        ])->assertOk();

        $order = PaymentOrder::where('user_id', $user->id)->firstOrFail();

        $this->assertSame('yearly', $order->plan);
        $this->assertSame('TWD', $order->currency);
        $this->assertSame(Pricing::minorAmount('yearly', 'TWD'), $order->amount);
        $this->assertNotSame(1, $order->amount);
    }

    public function test_checkout_is_refused_without_the_cooling_off_consent(): void
    {
        // 排除七日猶豫期的前提是「消費者事先明示同意」——前端的 required
        // 擋不住直接送出的請求,所以伺服器端必須自己拒絕。
        config()->set('premium.default_currency', 'TWD');
        $user = User::factory()->create();

        $this->actingAs($user)
            ->submit('/tw/premium/checkout', ['plan' => 'yearly'])
            ->assertSessionHasErrors('consent');

        $this->assertNull(PaymentOrder::where('user_id', $user->id)->first());
    }

    public function test_unknown_plan_falls_back_to_the_default_plan(): void
    {
        config()->set('premium.default_currency', 'TWD');
        $user = User::factory()->create();

        $this->actingAs($user)->submit('/tw/premium/checkout', ['plan' => 'lifetime-free', 'consent' => '1'])->assertOk();

        $order = PaymentOrder::where('user_id', $user->id)->firstOrFail();
        $this->assertSame(Pricing::defaultPlan(), $order->plan);
    }

    public function test_order_duration_comes_from_the_stored_plan_not_current_config(): void
    {
        $order = new PaymentOrder(['plan' => 'yearly']);
        $this->assertSame(365, $order->durationDays());

        // 日後調整年方案的天數,舊訂單要跟著新設定 —— 但方案身分不能漂移。
        config()->set('premium.plans.yearly.days', 400);
        $this->assertSame(400, $order->durationDays());

        // 方案代號被移除時退回預設方案,而不是丟例外讓 callback 整個掛掉。
        $orphan = new PaymentOrder(['plan' => 'a-plan-we-deleted']);
        $this->assertSame(Pricing::days(Pricing::defaultPlan()), $orphan->durationDays());
    }

    // ── 遊玩紀錄額度 ────────────────────────────────────────

    private function playGames(User $user, int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            $game = Game::create([
                'code' => str_pad((string) $i, 8, 'G', STR_PAD_LEFT),
                'game_type' => 'flying_chess',
                'status' => 'finished',
                'max_players' => 4,
            ]);

            GamePlayer::create([
                'game_id' => $game->id,
                'user_id' => $user->id,
                'session_id' => 'sess-'.$i,
                'player_name' => 'P'.$i,
                'color' => 'red',
                'is_host' => true,
            ]);
        }
    }

    public function test_free_user_sees_only_the_last_five_games(): void
    {
        config()->set('premium.free_history_limit', 5);
        $user = User::factory()->create();
        $this->playGames($user, 8);

        $response = $this->actingAs($user)->visit('/tw/profile')->assertOk();

        $response->assertViewHas('playHistory', fn ($h) => $h->count() === 5);
        $response->assertViewHas('totalPlays', 8);
        $response->assertViewHas('hiddenPlays', 3);
        // 時間軸是付費功能。
        $response->assertViewHas('timeline', null);
        $response->assertSee(__('ui.history_upgrade_cta'), false);
    }

    public function test_the_upsell_stays_hidden_when_nothing_is_actually_locked(): void
    {
        config()->set('premium.free_history_limit', 5);
        $user = User::factory()->create();
        $this->playGames($user, 3);

        $response = $this->actingAs($user)->visit('/tw/profile')->assertOk();

        $response->assertViewHas('hiddenPlays', 0);
        $response->assertDontSee(__('ui.history_upgrade_cta'), false);
    }

    public function test_premium_user_gets_every_game_and_a_timeline(): void
    {
        config()->set('premium.free_history_limit', 5);
        $user = User::factory()->create(['premium_expires_at' => now()->addDays(20)]);
        $this->playGames($user, 8);

        $response = $this->actingAs($user)->visit('/tw/profile')->assertOk();

        $response->assertViewHas('playHistory', fn ($h) => $h->count() === 8);
        $response->assertViewHas('hiddenPlays', 0);
        $response->assertViewHas('timeline', fn ($t) => $t !== null && $t->flatten()->count() === 8);
        $response->assertDontSee(__('ui.history_upgrade_cta'), false);
    }

    public function test_expired_premium_is_treated_as_free(): void
    {
        config()->set('premium.free_history_limit', 5);
        $user = User::factory()->create(['premium_expires_at' => now()->subDay()]);
        $this->playGames($user, 8);

        $this->actingAs($user)->visit('/tw/profile')
            ->assertOk()
            ->assertViewHas('playHistory', fn ($h) => $h->count() === 5)
            ->assertViewHas('timeline', null);
    }

    public function test_total_count_ignores_rooms_that_no_longer_exist(): void
    {
        // 總數若把已刪房間的殘列算進去,免費會員會看到「共 8 場」,
        // 付費之後卻只出現 6 場 —— 等於收了錢還少給東西。
        config()->set('premium.free_history_limit', 5);
        $user = User::factory()->create();
        $this->playGames($user, 8);

        Game::query()->limit(2)->get()->each->delete();

        $this->actingAs($user)->visit('/tw/profile')
            ->assertOk()
            ->assertViewHas('totalPlays', 6)
            ->assertViewHas('hiddenPlays', 1);
    }
}
