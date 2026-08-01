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

    public function test_truth_dare_merges_the_avatar_into_the_posted_name(): void
    {
        $this->withoutMiddleware(AgeVerification::class);

        /* 其他四個遊戲是前端自己收名字,只有這一頁是真的表單 POST,所以要多一段
           送出前把頭像併進 input 的程式。那一段被順手刪掉的話,畫面上一切正常,
           只有存進資料庫的名字沒有頭像 —— 沒有任何錯誤訊息。 */
        $this->get('/tw/truth-dare')
            ->assertOk()
            ->assertSee("addEventListener('submit'", false)
            ->assertSee('PlayerAvatar.displayName(row)', false);
    }

    public function test_the_avatar_picker_is_actually_hidden_when_closed(): void
    {
        /* .pa-grid 有 display:grid,而 hidden 屬性的 UA 樣式優先權比 class 低 ——
           少了這條覆蓋,挑選面板會一直攤在畫面上而且怎麼點都收不起來。
           JS 那邊完全正常,所以只有這條 CSS 擋得住。 */
        $css = file_get_contents(public_path('css/minigames.css'));

        $this->assertStringContainsString('.pa-grid[hidden]{display:none}', $css);
    }

    public function test_the_stylesheets_have_balanced_braces(): void
    {
        /* 用腳本大量改 CSS 的時候很容易多留一個 }。瀏覽器會忽略它繼續往下讀,
           所以畫面不會整個壞掉,只會有某一段規則莫名其妙不生效 —— 那種問題
           用肉眼看幾乎找不到。 */
        foreach (['css/app.css', 'css/minigames.css', 'css/board.css'] as $file) {
            $depth = 0;
            $wentNegative = false;

            foreach (str_split(file_get_contents(public_path($file))) as $ch) {
                if ($ch === '{') {
                    $depth++;
                } elseif ($ch === '}') {
                    $depth--;
                }
                if ($depth < 0) {
                    $wentNegative = true;
                    break;
                }
            }

            $this->assertFalse($wentNegative, "{$file} 有多出來的 }");
            $this->assertSame(0, $depth, "{$file} 有沒關起來的 {");
        }
    }
}
