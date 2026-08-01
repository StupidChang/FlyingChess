<?php

namespace Tests\Feature;

use App\Http\Middleware\AgeVerification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * 五個小遊戲的設定畫面要長一樣:都能選頭像,都有「逐漸升溫」。
 *
 * 這一層擋的是「加了新遊戲卻忘了接上去」與「改版時漏掉其中一頁」——
 * 五份設定畫面的程式各自獨立,很容易只改到其中兩三個。
 */
class MinigameSetupTest extends TestCase
{
    use RefreshDatabase;

    public static function setupPages(): array
    {
        return [
            '真心話大冒險' => ['/tw/truth-dare'],
            '情侶撲克牌' => ['/tw/card-game'],
            '國王遊戲' => ['/tw/king-game'],
            '骰子遊戲' => ['/tw/dice-game'],
            '命運轉盤' => ['/tw/wheel-game'],
            '誰最有可能' => ['/tw/who-most-likely'],
        ];
    }

    #[DataProvider('setupPages')]
    public function test_the_setup_screen_has_avatars_and_the_escalate_toggle(string $url): void
    {
        $this->withoutMiddleware(AgeVerification::class);

        $this->get($url)
            ->assertOk()
            ->assertSee('js/player-avatar.js', false)
            ->assertSee('escalate-toggle', false)
            ->assertSee(__('minigame.escalate_label'));
    }

    public function test_the_avatar_script_ships_the_picker_and_the_name_helper(): void
    {
        $js = file_get_contents(public_path('js/player-avatar.js'));

        /* 頭像是接在名字前面送出去的 —— 各遊戲內部有的把玩家存成字串、有的存成
           物件,統一改資料結構要動每一處顯示。這個約定壞掉的話,畫面上就只剩
           名字沒有頭像,而那不會有任何錯誤訊息。 */
        $this->assertStringContainsString('displayName', $js);
        $this->assertStringContainsString('MutationObserver', $js);
    }
}
