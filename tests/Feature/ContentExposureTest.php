<?php

namespace Tests\Feature;

use App\Models\GamePrompt;
use App\Models\WheelSegment;
use App\Services\CardGameService;
use App\Services\WheelGameService;
use App\Support\ContentExposure;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 一次頁面載入最多讓瀏覽器拿到多少題目。
 *
 * 四個小遊戲把整份題庫 @json 進頁面再由前端抽,所以「檢視原始碼」一次就能全部
 * 帶走。付費題目本來就不會送給沒權限的人,但看一支廣告解鎖三十分鐘之後,整份
 * 付費題庫就進了瀏覽器 —— 一次解鎖換整份題庫。這一層把它切成隨機子集。
 */
class ContentExposureTest extends TestCase
{
    use RefreshDatabase;

    private function prompts(int $n, string $pool = 'mild'): void
    {
        foreach (range(1, $n) as $i) {
            GamePrompt::create(['game' => 'card_game', 'pool' => $pool, 'content' => "題目 {$i}"]);
        }
    }

    public function test_a_big_pool_is_cut_down_before_it_reaches_the_browser(): void
    {
        config(['content.client_pool_cap' => 10]);
        $this->prompts(60);

        $this->assertCount(10, CardGameService::getActivityPools(true)['mild']);
    }

    public function test_a_small_pool_is_left_alone(): void
    {
        config(['content.client_pool_cap' => 10]);
        $this->prompts(4);

        // 題目本來就不多的時候不該再砍,不然玩兩輪就重複了
        $this->assertCount(4, CardGameService::getActivityPools(true)['mild']);
    }

    public function test_two_page_loads_do_not_return_the_same_slice(): void
    {
        config(['content.client_pool_cap' => 10]);
        $this->prompts(80);

        /* 每次重抽是重點。固定切前 N 筆的話,等於永遠只保護後面那些,
           前 N 筆照樣被完整帶走。 */
        $a = CardGameService::getActivityPools(true)['mild'];
        $b = CardGameService::getActivityPools(true)['mild'];

        $this->assertNotSame($a, $b);
    }

    public function test_the_cap_can_be_switched_off(): void
    {
        config(['content.client_pool_cap' => 0]);
        $this->prompts(60);

        $this->assertCount(60, CardGameService::getActivityPools(true)['mild']);
    }

    public function test_the_wheel_is_capped_too(): void
    {
        config(['content.client_pool_cap' => 5]);
        foreach (range(1, 40) as $i) {
            WheelSegment::create(['tier' => 'mild', 'content' => "任務 {$i}"]);
        }

        $this->assertCount(5, WheelGameService::getSegmentPools(true)['mild']);
    }

    public function test_the_sample_never_invents_or_duplicates_items(): void
    {
        config(['content.client_pool_cap' => 6]);
        $source = array_map(fn ($i) => "第 {$i} 題", range(1, 30));

        $out = ContentExposure::sample($source);

        $this->assertCount(6, $out);
        $this->assertSame($out, array_unique($out), '同一題不該出現兩次');
        $this->assertEmpty(array_diff($out, $source), '不該冒出原本沒有的題目');
    }

    public function test_paid_content_still_never_reaches_a_free_player(): void
    {
        config(['content.client_pool_cap' => 50]);
        GamePrompt::create(['game' => 'card_game', 'pool' => 'mild', 'content' => '免費的']);
        GamePrompt::create(['game' => 'card_game', 'pool' => 'intense', 'content' => '付費的', 'is_paid' => true]);

        // 上限是第二道防線,不是第一道 —— 付費的界線本身不能因此鬆掉
        $free = CardGameService::getActivityPools(false);

        $this->assertSame(['免費的'], $free['mild']);
        $this->assertArrayNotHasKey('intense', $free);
    }
}
