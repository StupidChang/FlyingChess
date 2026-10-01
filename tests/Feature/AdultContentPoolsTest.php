<?php

namespace Tests\Feature;

use App\Services\CardGameService;
use App\Services\DiceGameService;
use App\Services\KingGameService;
use App\Services\WheelGameService;
use App\Services\WhoMostLikelyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdultContentPoolsTest extends TestCase
{
    // WheelGameService reads wheel_segments first and only falls back to its
    // hardcoded pools when the table is empty. The migrated-but-empty database
    // this trait provides is what exercises that fallback path.
    use RefreshDatabase;

    public function test_card_game_has_expanded_unique_pools(): void
    {
        $pools = CardGameService::getActivityPools(true);

        // 2026-09-27 起五級:微撩、挑逗補在原本三級之間的斷層
        $this->assertSame(['mild', 'mild_plus', 'medium', 'medium_plus', 'intense'], array_keys($pools));
        foreach ($pools as $pool) {
            $this->assertGreaterThanOrEqual(12, count($pool));
            $this->assertCount(count($pool), array_unique($pool));
        }
    }

    public function test_non_premium_wheel_does_not_expose_intense_pool(): void
    {
        $free = WheelGameService::getSegmentPools(false);
        $premium = WheelGameService::getSegmentPools(true);

        $this->assertArrayHasKey('mild', $free);
        $this->assertArrayHasKey('medium', $free);
        $this->assertArrayNotHasKey('intense', $free);
        /* 題庫本身的數量看原始常數,不看送出去的那一份 —— 送出去的會被
           ContentExposure 裁成隨機子集,拿它當數量指標會誤判成題目變少了。 */
        $this->assertGreaterThanOrEqual(32, count(WheelGameService::defaultPools()['intense']));
        $this->assertNotEmpty($premium['intense']);
    }

    public function test_non_premium_card_game_does_not_receive_intense_pool(): void
    {
        $free = CardGameService::getActivityPools(false);
        $premium = CardGameService::getActivityPools(true);

        $this->assertGreaterThanOrEqual(40, count(CardGameService::defaultPools()['intense']));
        $this->assertNotEmpty($premium['intense']);
        $this->assertArrayNotHasKey('intense', $free);
    }

    public function test_explicit_dice_faces_only_ship_with_premium_access(): void
    {
        $free = collect(DiceGameService::getBuiltInDice(false))->keyBy('id');
        $premium = collect(DiceGameService::getBuiltInDice(true))->keyBy('id');

        $this->assertTrue($free['builtin_action_wild']['locked']);
        $this->assertSame([], $free['builtin_action_wild']['faces']);
        $this->assertContains('陰蒂或龜頭', $premium['builtin_part_wild']['faces']);
        $this->assertContains('跳蛋', $premium['builtin_prop_wild']['faces']);
        $this->assertSame([], $free['builtin_play_wild']['faces']);
        $this->assertContains('後入抽插30下', $premium['builtin_play_wild']['faces']);
        // 免費的大膽骰也骰得到私處,只是隔著內褲
        $this->assertContains('隔著內褲的私處', $free['builtin_part_bold']['faces']);
    }

    public function test_other_games_reserve_explicit_pools_for_premium(): void
    {
        $this->assertArrayNotHasKey('intense', KingGameService::getCommandPools(false));
        $this->assertArrayNotHasKey('intense', WhoMostLikelyService::getPromptPools(false));
        $this->assertStringContainsString('口交', implode(' ', KingGameService::getCommandPools(true)['intense']));
        $this->assertStringContainsString('肛交', implode(' ', WhoMostLikelyService::getPromptPools(true)['intense']));
    }

    public function test_free_tiers_also_carry_some_sex_but_less_than_premium(): void
    {
        /* 2026-09-27 使用者要求:免費也要有性交的內容,只是比付費少。免費的上限
           在「挑逗」(medium_plus)那一級:以私處撫摸為主,另外有幾題口交與插入。
           最高一級(付費)每一題都要是實際的身體行為。 */
        $sex = '/口交|69|插|進去|騎上|後入|傳教士|體位|手指伸/u';
        // 最高一級的「性行為」還包括玩具、舔私處、做到高潮
        $act = '/口交|69|插|進去|騎上|後入|傳教士|體位|手指伸|跳蛋|按摩棒|震動|舔|私處|高潮|射|臉上|做愛|坐蓮|從後面|坐上去|壓上去|慢慢做|自慰|手指|含|騎在|在上面/u';

        foreach ([
            'wheel' => WheelGameService::defaultPools(),
            'card' => CardGameService::defaultPools(),
            'king' => KingGameService::defaultPools(),
        ] as $game => $pools) {
            $free = array_merge($pools['mild'], $pools['mild_plus'], $pools['medium'], $pools['medium_plus']);
            $freeSex = count(preg_grep($sex, $free));
            $paidSex = count(preg_grep($sex, $pools['intense']));

            $this->assertGreaterThanOrEqual(3, $freeSex, "{$game}:免費等級要有性交內容");
            $this->assertSame([], preg_grep($sex, array_merge($pools['mild'], $pools['mild_plus'])), "{$game}:前兩級還不該出現");
            $this->assertGreaterThan($freeSex, $paidSex, "{$game}:付費的份量要比免費多");
            $this->assertGreaterThanOrEqual(count($pools['intense']) * .95, count(preg_grep($act, $pools['intense'])), "{$game}:最高一級幾乎每題都要是性行為");
        }
    }
}
