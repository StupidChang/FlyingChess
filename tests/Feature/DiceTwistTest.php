<?php

namespace Tests\Feature;

use App\Http\Middleware\AgeVerification;
use App\Models\GamePrompt;
use App\Services\DiceGameService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 骰子遊戲的轉折骰(twist.bold 免費、twist.wild 付費)。
 */
class DiceTwistTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(AgeVerification::class);
    }

    private function dice(bool $premium)
    {
        return collect(DiceGameService::getBuiltInDice($premium))->keyBy('id');
    }

    public function test_the_free_twist_die_is_playable_and_the_wild_one_is_locked(): void
    {
        $free = $this->dice(false);

        $this->assertFalse($free['builtin_twist_bold']['locked']);
        $this->assertCount(6, $free['builtin_twist_bold']['faces']);
        $this->assertTrue($free['builtin_twist_wild']['locked']);
        $this->assertSame([], $free['builtin_twist_wild']['faces'], '付費骰面不能送到前端');

        $this->assertFalse($this->dice(true)['builtin_twist_wild']['locked']);
    }

    public function test_every_twist_face_has_a_short_label_for_the_die(): void
    {
        foreach (['twist.bold', 'twist.wild'] as $pool) {
            foreach (DiceGameService::defaultPools()[$pool] as $face) {
                [$short, $long] = explode('|', $face, 2) + [1 => null];
                $this->assertNotNull($long, "{$face} 沒有「短標|說明」");
                // 骰面只有 80px:短標放得下,說明才是整句
                $this->assertLessThanOrEqual(5, mb_strlen($short), "{$short} 放不進骰面");
            }
        }
    }

    public function test_a_site_that_imported_prompts_before_the_twist_die_still_gets_one(): void
    {
        // 模擬「轉折骰出現之前就匯入過題庫」的站:資料表裡有其他池,沒有 twist
        GamePrompt::importDefaults('dice_game');
        GamePrompt::where('game', 'dice_game')->where('pool', 'like', 'twist.%')->delete();

        $free = $this->dice(false);
        $this->assertCount(6, $free['builtin_twist_bold']['faces']);
        // 退回預設的付費池沒有 is_paid 可言,沒權限就鎖住
        $this->assertTrue($free['builtin_twist_wild']['locked']);
    }

    public function test_the_twist_faces_follow_the_page_language(): void
    {
        app()->setLocale('en');
        $faces = $this->dice(false)['builtin_twist_bold']['faces'];

        $this->assertContains('Flip it|Flip it — your partner does this to you instead', $faces);
    }

    public function test_the_page_ships_the_twist_die_and_the_timer_copy(): void
    {
        $this->get('/tw/dice-game')->assertOk()
            ->assertSee('builtin_twist_bold', false)
            ->assertSee(__('minigame.dice_label_twist'))
            ->assertSee('dg-timer', false);
    }
}
