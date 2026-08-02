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

        $this->assertSame(['mild', 'medium', 'intense'], array_keys($pools));
        foreach ($pools as $pool) {
            $this->assertGreaterThanOrEqual(16, count($pool));
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
        $this->assertGreaterThanOrEqual(32, count(WheelGameService::SEGMENTS_INTENSE));
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
        $this->assertContains('插入', $premium['builtin_action_wild']['faces']);
        $this->assertContains('陰道', $premium['builtin_part_wild']['faces']);
        $this->assertContains('後庭塞', $premium['builtin_prop_wild']['faces']);
        $this->assertSame([], $free['builtin_play_wild']['faces']);
        $this->assertContains('後入30下', $premium['builtin_play_wild']['faces']);
    }

    public function test_other_games_reserve_explicit_pools_for_premium(): void
    {
        $this->assertArrayNotHasKey('intense', KingGameService::getCommandPools(false));
        $this->assertArrayNotHasKey('intense', WhoMostLikelyService::getPromptPools(false));
        $this->assertStringContainsString('口交', implode(' ', KingGameService::getCommandPools(true)['intense']));
        $this->assertStringContainsString('肛交', implode(' ', WhoMostLikelyService::getPromptPools(true)['intense']));
    }
}
