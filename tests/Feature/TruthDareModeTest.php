<?php

namespace Tests\Feature;

use App\Http\Middleware\AgeVerification;
use App\Models\Game;
use App\Models\TruthDareCard;
use App\Models\User;
use App\Services\TruthDareService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 真心話大冒險的「類型」與「人數」是兩個軸。
 *
 * 這一組測試存在的原因是原本兩個軸被壓成一個 category(truth/dare/couple/party):
 * dare 的 24 張裡有 23 張寫著「另一半」,所以三五好友按下「大冒險」會抽到
 * 「在另一半耳邊吹一口氣」,情侶按「派對」會抽到「對在場的人放電」。
 * 真正要守住的不是「有 mode 這個欄位」,而是**抽不到對不上場合的題目**。
 */
class TruthDareModeTest extends TestCase
{
    use RefreshDatabase;

    private function card(string $category, string $audience, string $content): TruthDareCard
    {
        return TruthDareCard::create([
            'category' => $category,
            'audience' => $audience,
            'content' => $content,
            'level' => 'intense',
        ]);
    }

    private function room(string $mode, bool $escalate = false): Game
    {
        $result = app(TruthDareService::class)
            ->createGame('主揪', 'sess-1', false, null, true, $mode, $escalate);

        return $result['game'];
    }

    public function test_a_couple_room_never_draws_a_group_prompt(): void
    {
        $this->card('dare', 'couple', '在另一半耳邊吹一口氣');
        $this->card('dare', 'party', '對在場你覺得最性感的人放電');

        $game = $this->room('couple');
        $service = app(TruthDareService::class);

        // 抽到牌庫見底,確認一路上都沒有摸到多人場的題目。
        $drawn = 0;
        for ($i = 0; $i < 5; $i++) {
            $result = $service->drawCard($game->fresh(), 'dare', true, true);
            if (! $result['success']) {
                break;
            }
            $drawn++;
            $this->assertStringNotContainsString('在場', $result['card']['content']);
        }
        $this->assertSame(1, $drawn, '情侶場應該剛好抽得到那一張情侶題');
    }

    public function test_a_group_room_never_draws_a_partner_prompt(): void
    {
        $this->card('dare', 'couple', '在另一半耳邊吹一口氣');
        $this->card('dare', 'party', '對在場你覺得最性感的人放電');

        $game = $this->room('party');
        $service = app(TruthDareService::class);

        $drawn = 0;
        for ($i = 0; $i < 5; $i++) {
            $result = $service->drawCard($game->fresh(), 'dare', true, true);
            if (! $result['success']) {
                break;
            }
            $drawn++;
            $this->assertStringNotContainsString('另一半', $result['card']['content']);
        }
        $this->assertSame(1, $drawn, '多人場應該剛好抽得到那一張多人題');
    }

    public function test_neutral_prompts_come_up_in_both_modes(): void
    {
        $this->card('truth', 'both', '你最近一次心動是什麼時候');

        foreach (['couple', 'party'] as $mode) {
            $result = app(TruthDareService::class)
                ->drawCard($this->room($mode), 'truth', true, true);

            $this->assertTrue($result['success'], "{$mode} 抽不到通用題目");
        }
    }

    public function test_the_room_mode_follows_the_number_of_players(): void
    {
        $this->withoutMiddleware(AgeVerification::class);

        // 兩個人 → 情侶場
        $this->post('/tw/truth-dare', ['players' => ['小明', '小美']]);
        $this->assertSame('couple', Game::latest('id')->first()->game_state['mode']);

        // 三個人以上 → 多人場
        $this->post('/tw/truth-dare', ['players' => ['小明', '小美', '阿豪']]);
        $this->assertSame('party', Game::latest('id')->first()->game_state['mode']);
    }

    public function test_the_host_can_override_the_mode(): void
    {
        $this->withoutMiddleware(AgeVerification::class);

        // 兩個朋友(不是情侶)一起玩,要的是多人場的題目。
        $this->post('/tw/truth-dare', ['players' => ['小明', '小美'], 'mode' => 'party']);

        $this->assertSame('party', Game::latest('id')->first()->game_state['mode']);
    }

    public function test_a_paid_card_inside_a_free_level_stays_locked(): void
    {
        /* 收費是每張卡片自己的欄位,不是由尺度推導 —— 中度裡也能有付費題目,
           不用把整級變成付費。這一條就是那個界線的合約。 */
        TruthDareCard::create([
            'category' => 'truth', 'audience' => 'both', 'content' => '中度免費', 'level' => 'medium', 'is_paid' => false,
        ]);
        TruthDareCard::create([
            'category' => 'truth', 'audience' => 'both', 'content' => '中度付費', 'level' => 'medium', 'is_paid' => true,
        ]);

        $game = $this->room('couple');
        $service = app(TruthDareService::class);

        $seen = [];
        for ($i = 0; $i < 4; $i++) {
            $result = $service->drawCard($game->fresh(), 'truth', false, true);
            if (! $result['success']) {
                break;
            }
            $seen[] = $result['card']['content'];
        }

        $this->assertSame(['中度免費'], $seen);
    }

    public function test_paid_access_reaches_the_locked_card_in_that_level(): void
    {
        TruthDareCard::create([
            'category' => 'truth', 'audience' => 'both', 'content' => '中度付費', 'level' => 'medium', 'is_paid' => true,
        ]);

        $result = app(TruthDareService::class)
            ->drawCard($this->room('couple'), 'truth', true, true);

        $this->assertTrue($result['success']);
        $this->assertSame('中度付費', $result['card']['content']);
    }

    public function test_escalation_skips_a_level_the_player_cannot_reach(): void
    {
        TruthDareCard::create([
            'category' => 'truth', 'audience' => 'both', 'content' => '輕度', 'level' => 'mild', 'is_paid' => false,
        ]);
        TruthDareCard::create([
            'category' => 'truth', 'audience' => 'both', 'content' => '中度全付費', 'level' => 'medium', 'is_paid' => true,
        ]);
        TruthDareCard::create([
            'category' => 'truth', 'audience' => 'both', 'content' => '重度免費', 'level' => 'intense', 'is_paid' => false,
        ]);

        /* 中度整級都要付費的話,免費玩家的階梯要直接跳過它 —— 「升」到一個
           抽不到東西的等級,畫面上看到的就是沒有題目了。 */
        $game = $this->room('couple', escalate: true);
        $service = app(TruthDareService::class);

        $seen = [];
        for ($drawn = 0; $drawn < 10; $drawn++) {
            $result = $service->drawCard($game->fresh(), 'truth', false, true);
            if ($result['success']) {
                $seen[$result['card']['content']] = true;
            }
            $state = $game->fresh()->game_state;
            $state['used_card_ids'] = array_merge($state['used_card_ids'] ?? [], [-$drawn]);
            $game->update(['game_state' => $state]);
        }

        $this->assertArrayHasKey('輕度', $seen);
        $this->assertArrayHasKey('重度免費', $seen);
        $this->assertArrayNotHasKey('中度全付費', $seen);
    }

    public function test_a_free_player_only_draws_free_prompts(): void
    {
        TruthDareCard::create(['category' => 'truth', 'audience' => 'both', 'content' => '曖昧級', 'level' => 'mild', 'is_paid' => false]);
        TruthDareCard::create(['category' => 'truth', 'audience' => 'both', 'content' => '露骨級', 'level' => 'intense', 'is_paid' => true]);

        /* 這裡守的是原本壞掉的地方:每一間房都是 is_adult,而 is_adult 的分支只抽
           premium,所以免費玩家照樣拿得到全部付費題目,那些免費題目反而一張都
           抽不到 —— 免費與付費之間根本沒有差別。 */
        $game = $this->room('couple');
        $service = app(TruthDareService::class);

        $drawn = 0;
        for ($i = 0; $i < 3; $i++) {
            $result = $service->drawCard($game->fresh(), 'truth', false, true);
            if (! $result['success']) {
                break;
            }
            $drawn++;
            $this->assertSame('曖昧級', $result['card']['content']);
        }

        $this->assertSame(1, $drawn, '免費玩家應該剛好抽得到那一張免費題目');
    }

    public function test_premium_access_adds_the_paid_prompts(): void
    {
        TruthDareCard::create(['category' => 'truth', 'audience' => 'both', 'content' => '露骨級', 'level' => 'intense', 'is_paid' => true]);

        $result = app(TruthDareService::class)
            ->drawCard($this->room('couple'), 'truth', true, true);

        $this->assertTrue($result['success']);
        $this->assertSame('露骨級', $result['card']['content']);
    }

    public function test_escalation_climbs_one_level_at_a_time(): void
    {
        foreach (['mild', 'medium', 'intense'] as $level) {
            TruthDareCard::create([
                'category' => 'truth', 'audience' => 'both', 'content' => $level, 'level' => $level,
            ]);
        }

        $game = $this->room('couple', escalate: true);
        $service = app(TruthDareService::class);

        /* 開頭只會是最輕的一級,即使有付費權限也一樣 —— 那正是升溫的重點。
           抽掉幾張之後才往上開放,一次一級。 */
        $seen = [];
        for ($drawn = 0; $drawn < 12; $drawn++) {
            $result = $service->drawCard($game->fresh(), 'truth', true, true);
            if ($result['success']) {
                $seen[$result['card']['level']] = $drawn;
            }

            // 湊出「已經玩了幾張」,不用真的把題庫抽光
            $state = $game->fresh()->game_state;
            $state['used_card_ids'] = array_merge($state['used_card_ids'] ?? [], [-$drawn]);
            $game->update(['game_state' => $state]);
        }

        $this->assertSame(0, $seen['mild'] ?? null, '第一張就該是輕度');
        $this->assertGreaterThan(0, $seen['medium'] ?? -1, '中度不該一開始就出現');
        $this->assertGreaterThan($seen['medium'], $seen['intense'] ?? -1, '重度要比中度更晚出現');
    }

    public function test_without_escalation_everything_is_in_play_from_the_start(): void
    {
        TruthDareCard::create(['category' => 'truth', 'audience' => 'both', 'content' => '露骨級', 'level' => 'intense']);

        $result = app(TruthDareService::class)
            ->drawCard($this->room('couple'), 'truth', true, true);

        $this->assertTrue($result['success']);
    }

    public function test_the_lobby_offers_the_ad_unlock_to_a_free_visitor(): void
    {
        $this->withoutMiddleware(AgeVerification::class);

        /* 開局前就要知道免費與付費的差別 —— 玩到一半才被擋下來毀掉的是整場氣氛。
           而且要講清楚露骨到什麼程度,不能只說「更直接」讓人自己猜。 */
        $this->get('/tw/truth-dare')
            ->assertOk()
            ->assertSee(__('games.td_scale_summary'))
            ->assertSee(__('games.td_scale_intense_desc'))
            ->assertSee(__('games.td_scale_paywall'))
            ->assertSee('rewardedUnlockOpen', false);
    }

    public function test_the_lobby_does_not_nag_someone_who_already_has_access(): void
    {
        $this->withoutMiddleware(AgeVerification::class);
        $member = User::factory()->create(['premium_expires_at' => now()->addYear()]);

        $this->actingAs($member)->get('/tw/truth-dare')
            ->assertOk()
            // 尺度說明對誰都要看得到,但已經有權限的人不該再被推銷一次。
            ->assertSee(__('games.td_scale_summary'))
            ->assertDontSee('rewardedUnlockOpen', false)
            ->assertSee(__('games.td_tier_unlocked'));
    }

    public function test_the_old_audience_values_are_no_longer_accepted_as_a_category(): void
    {
        $this->withoutMiddleware(AgeVerification::class);
        $game = $this->room('couple');
        // 這一條驗的是驗證規則,不是房間成員,所以 422 會比 403 先發生。

        /* couple / party 以前是 category 的合法值。留著的話,前端只要有一顆沒清乾淨的
           舊按鈕就會送上來,而它現在永遠抽不到東西 —— 寧可直接擋掉。 */
        $this->postJson("/tw/truth-dare/{$game->code}/draw", ['category' => 'party'])
            ->assertStatus(422);
    }
}
