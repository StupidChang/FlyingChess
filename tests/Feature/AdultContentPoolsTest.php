<?php

namespace Tests\Feature;

use App\Services\CardGameService;
use App\Services\WheelGameService;
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
        $this->assertArrayHasKey('intense', $free);
        $this->assertSame(intdiv(count($premium['intense']), 2), count($free['intense']));
        $this->assertSame(
            $free['intense'],
            array_slice($premium['intense'], 0, count($free['intense'])),
        );
    }

    public function test_non_premium_card_game_receives_half_of_intense_pool(): void
    {
        $free = CardGameService::getActivityPools(false);
        $premium = CardGameService::getActivityPools(true);

        $this->assertGreaterThanOrEqual(32, count($premium['intense']));
        $this->assertSame(intdiv(count($premium['intense']), 2), count($free['intense']));
    }
}
