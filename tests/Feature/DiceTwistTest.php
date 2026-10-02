<?php

namespace Tests\Feature;

use App\Http\Middleware\AgeVerification;
use App\Models\GamePrompt;
use App\Services\DiceGameService;
use App\Support\ContentTranslations;
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
        // 題庫可以比 6 面多:骰子還是 6 面,每一局從題庫裡挑 6 個放上去(dice-game/show 的 buildDice)
        $this->assertCount(count(DiceGameService::defaultPools()['twist.bold']), $free['builtin_twist_bold']['faces']);
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
        $this->assertCount(count(DiceGameService::defaultPools()['twist.bold']), $free['builtin_twist_bold']['faces']);
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

    public function test_each_face_ships_with_its_original_text_for_the_combo_rules(): void
    {
        app()->setLocale('en');
        foreach ($this->dice(true) as $die) {
            $this->assertCount(count($die['faces']), $die['keys'], $die['id']);
            foreach ($die['keys'] as $i => $key) {
                // keys 是繁中原文,faces 是同一面的英文
                $this->assertSame(ContentTranslations::translate($key), $die['faces'][$i]);
            }
        }
    }

    public function test_the_combo_rules_only_name_faces_that_exist(): void
    {
        // 規則表打錯一個字就默默失效(對不到骰面 = 不受限),所以每個名字都要真的是某一面
        $all = collect(DiceGameService::defaultPools())->flatten()->all();
        $rules = DiceGameService::rules();
        $names = array_merge(
            $rules['mouth'],
            $rules['quick'],
            array_keys($rules['part_deny']),
            array_merge(...array_values($rules['part_deny'])),
            array_keys($rules['prop_deny']),
            array_merge(...array_values($rules['prop_deny'])),
            array_keys($rules['twist_needs']),
            $rules['prop_needs_play'],
            array_keys($rules['prop_play_deny']),
            array_merge(...array_values($rules['prop_play_deny'])),
        );
        foreach (array_unique($names) as $name) {
            $this->assertContains($name, $all, "規則裡的「{$name}」不是任何一顆骰子的骰面");
        }
    }

    public function test_the_page_ships_the_rules(): void
    {
        $this->get('/tw/dice-game')->assertOk()
            ->assertSee('part_deny', false)
            ->assertSee('dg-settings', false);
    }

    public function test_the_play_die_goes_solo(): void
    {
        // 玩法骰本身就是一整件事;跟動作、部位、時間一起擲就變成一輪要做三件事
        $this->assertSame(['action', 'part', 'time'], DiceGameService::rules()['play_excludes']);
    }
}
