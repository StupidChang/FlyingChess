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
        for ($turn = 0; $turn < 20; $turn++) {
            $result = $service->drawCard($game->fresh(), 'truth', false, true);
            if ($result['success']) {
                $seen[$result['card']['content']] = true;
            }
            $service->nextPlayer($game->fresh());
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

    public function test_escalation_climbs_one_level_per_few_rounds(): void
    {
        foreach (['mild', 'medium', 'intense'] as $level) {
            TruthDareCard::create([
                'category' => 'truth', 'audience' => 'both', 'content' => $level,
                'level' => $level, 'is_paid' => false,
            ]);
        }

        $game = $this->room('couple', escalate: true);
        $service = app(TruthDareService::class);

        /* 一回合是「每個人都玩過一輪」,所以進度靠 nextPlayer 繞回第一個人來推,
           不是靠抽掉幾張卡。開頭只會是最輕的一級,即使有付費權限也一樣。 */
        $seen = [];
        for ($turn = 0; $turn < 24; $turn++) {
            $result = $service->drawCard($game->fresh(), 'truth', true, true);
            if ($result['success']) {
                $seen[$result['card']['level']] ??= $game->fresh()->game_state['round'] ?? 1;
            }
            $service->nextPlayer($game->fresh());
        }

        $this->assertSame(1, $seen['mild'] ?? null, '第一回合就該是輕度');
        $this->assertGreaterThan(1, $seen['medium'] ?? -1, '中度不該第一回合就出現');
        $this->assertGreaterThan($seen['medium'], $seen['intense'] ?? -1, '重度要比中度更晚');
    }

    public function test_a_round_is_everyone_taking_one_turn(): void
    {
        $game = $this->room('couple', escalate: true);
        $service = app(TruthDareService::class);

        // 兩位玩家:第二個人玩完才算一回合結束。
        $game->players()->create([
            'session_id' => 'sess-2', 'player_name' => '另一位', 'color' => 'none', 'is_host' => false,
        ]);

        $this->assertSame(1, $game->fresh()->game_state['round']);

        $service->nextPlayer($game->fresh());
        $this->assertSame(1, $game->fresh()->game_state['round'], '才輪到第二個人,還沒滿一回合');

        $service->nextPlayer($game->fresh());
        $this->assertSame(2, $game->fresh()->game_state['round'], '繞回第一個人才算下一回合');
    }

    public function test_without_escalation_everything_is_in_play_from_the_start(): void
    {
        TruthDareCard::create(['category' => 'truth', 'audience' => 'both', 'content' => '露骨級', 'level' => 'intense']);

        $result = app(TruthDareService::class)
            ->drawCard($this->room('couple'), 'truth', true, true);

        $this->assertTrue($result['success']);
    }

    public function test_each_player_can_be_given_a_gender(): void
    {
        $this->withoutMiddleware(AgeVerification::class);

        $this->post('/tw/truth-dare', [
            'players' => ['小明', '小美', '阿豪'],
            'genders' => ['male', 'female', ''],
        ]);

        $players = Game::latest('id')->first()->players()->orderBy('id')->get();

        $this->assertSame('male', $players[0]->gender);
        $this->assertSame('female', $players[1]->gender);
        $this->assertNull($players[2]->gender, '不指定就是不存');
    }

    public function test_an_empty_name_does_not_shift_everyone_elses_gender(): void
    {
        $this->withoutMiddleware(AgeVerification::class);

        /* 名字與性別是兩個平行陣列。中間留白的那一列被濾掉時,性別要跟著同一個
           索引一起濾 —— 只濾其中一邊的話,後面每個人的性別都會錯位一格。 */
        $this->post('/tw/truth-dare', [
            'players' => ['小明', '', '小美'],
            'genders' => ['male', 'female', 'female'],
        ]);

        $players = Game::latest('id')->first()->players()->orderBy('id')->get();

        $this->assertCount(2, $players);
        $this->assertSame('小明', $players[0]->player_name);
        $this->assertSame('male', $players[0]->gender);
        $this->assertSame('小美', $players[1]->player_name);
        $this->assertSame('female', $players[1]->gender);
    }

    public function test_a_made_up_gender_is_rejected(): void
    {
        $this->withoutMiddleware(AgeVerification::class);

        $this->post('/tw/truth-dare', [
            'players' => ['小明', '小美'],
            'genders' => ['male', 'dragon'],
        ])->assertSessionHasErrors('genders.1');
    }

    /** 開一間房並指定每個人的性別,回傳依序建立的玩家。 */
    private function roomWithGenders(array $genders): Game
    {
        $service = app(TruthDareService::class);
        $game = $service->createGame('第一位', 'sess-1', false, null, true, 'party', false, $genders[0])['game'];

        foreach (array_slice($genders, 1) as $i => $gender) {
            $game->players()->create([
                'session_id' => 'sess-'.($i + 2), 'player_name' => '第'.($i + 2).'位',
                'gender' => $gender, 'color' => 'none', 'is_host' => false,
            ]);
        }

        return $game;
    }

    private function genderCard(string $gender, string $content): void
    {
        TruthDareCard::create([
            'category' => 'truth', 'audience' => 'both', 'level' => 'mild',
            'gender' => $gender, 'content' => $content, 'is_paid' => false,
        ]);
    }

    public function test_a_male_player_only_draws_male_and_unrestricted_prompts(): void
    {
        $this->genderCard('any', '不限的');
        $this->genderCard('male', '男生的');
        $this->genderCard('female', '女生的');

        $game = $this->roomWithGenders(['male', 'female']);
        $service = app(TruthDareService::class);

        $seen = [];
        for ($i = 0; $i < 5; $i++) {
            $result = $service->drawCard($game->fresh(), 'truth', true, true);
            if (! $result['success']) {
                break;
            }
            $seen[] = $result['card']['content'];
        }

        sort($seen);
        $this->assertSame(['不限的', '男生的'], $seen);
    }

    public function test_the_filter_follows_whose_turn_it_is(): void
    {
        $this->genderCard('male', '男生的');
        $this->genderCard('female', '女生的');

        $game = $this->roomWithGenders(['male', 'female']);
        $service = app(TruthDareService::class);

        // 第一位是男生
        $this->assertSame('男生的', $service->drawCard($game->fresh(), 'truth', true, true)['card']['content']);

        // 換第二位(女生)之後,抽到的就該換一邊
        $service->nextPlayer($game->fresh());
        $this->assertSame('女生的', $service->drawCard($game->fresh(), 'truth', true, true)['card']['content']);
    }

    public function test_a_player_with_no_gender_sees_everything(): void
    {
        $this->genderCard('any', '不限的');
        $this->genderCard('male', '男生的');
        $this->genderCard('female', '女生的');

        /* 沒填性別的人不該只剩「不限」—— 不指定的意思是不想標,不是要少玩。 */
        $game = $this->roomWithGenders([null, null]);
        $service = app(TruthDareService::class);

        $seen = [];
        for ($i = 0; $i < 5; $i++) {
            $result = $service->drawCard($game->fresh(), 'truth', true, true);
            if (! $result['success']) {
                break;
            }
            $seen[] = $result['card']['content'];
        }

        $this->assertCount(3, $seen);
    }

    public function test_escalation_skips_a_level_that_only_has_the_other_gender(): void
    {
        $this->genderCard('any', '輕度不限');
        TruthDareCard::create([
            'category' => 'truth', 'audience' => 'both', 'level' => 'medium',
            'gender' => 'female', 'content' => '中度只有女生的', 'is_paid' => false,
        ]);
        TruthDareCard::create([
            'category' => 'truth', 'audience' => 'both', 'level' => 'intense',
            'gender' => 'any', 'content' => '重度不限', 'is_paid' => false,
        ]);

        /* 某一級只剩異性的題目時,那一級對這個人來說就是空的,升溫要跳過它 ——
           不然男玩家「升」到中度之後會抽不到任何東西。 */
        $service = app(TruthDareService::class);
        $game = $service->createGame('他', 'sess-1', false, null, true, 'couple', true, 'male')['game'];

        $seen = [];
        for ($turn = 0; $turn < 12; $turn++) {
            $result = $service->drawCard($game->fresh(), 'truth', true, true);
            if ($result['success']) {
                $seen[$result['card']['content']] = true;
            }
            $service->nextPlayer($game->fresh());
        }

        $this->assertArrayHasKey('輕度不限', $seen);
        $this->assertArrayHasKey('重度不限', $seen);
        $this->assertArrayNotHasKey('中度只有女生的', $seen);
    }

    public function test_the_lobby_offers_a_gender_for_each_player(): void
    {
        $this->withoutMiddleware(AgeVerification::class);

        // 可以不填 —— 不是每一桌都想標這個,也不是每個人都想被標。
        $this->get('/tw/truth-dare')
            ->assertOk()
            ->assertSee('name="genders[]"', false)
            ->assertSee(__('minigame.gender_unset'));
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
