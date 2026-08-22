<?php

namespace Tests\Feature;

use App\Http\Middleware\AgeVerification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 建立房間之後,**不帶 tab_id 的後續請求要認得同一個人**。
 *
 * 2026-08-21 用瀏覽器實測抓到的 blocker:大廳的表單會送 tab_id,玩家因此被存成
 * hash(session|tab);緊接著的轉址 GET /truth-dare/{code} 沒有 tab_id,算出來是純
 * session id,對不上 → 直接被踢回大廳。真人看到的就是「按了開始遊戲沒反應」。
 *
 * 之前的測試抓不到,是因為它們**不送 tab_id** —— 建立與讀取兩邊剛好都用純
 * session id,所以永遠一致。這一組刻意照瀏覽器的行為送 tab_id。
 */
class RoomIdentityAcrossRedirectTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(AgeVerification::class);

        /* getJson() 預設**不帶任何 cookie**(prepareCookiesForJsonRequest() 在沒有
           withCredentials() 時回傳空陣列),所以輪詢會落在一個全新的 session 上,
           跟真實的 fetch() 不一樣 —— 同源的 fetch 預設就是 same-origin,會帶 cookie。 */
        $this->withCredentials();
    }

    /**
     * 讓後續的請求沿用同一個 session,跟瀏覽器一樣。
     *
     * Laravel 的測試 client **沒有 cookie jar**:每個請求只帶 defaultCookies,
     * StartSession 拿不到 session cookie 就 setId(null) → 每個請求都是新的 session
     * id。這正是原本的測試抓不到這個 bug 的原因:兩邊的身分都在變,卻剛好都算成
     * 純 session id 而一致。
     *
     * 要帶的是**明文** session id ——「把回應的 Set-Cookie 原封不動接回去」行不通,
     * 因為 prepareCookiesForRequest() 會再加密一次,EncryptCookies 解一層之後拿到的
     * 還是密文,結果又是一個新 session。
     */
    private function keepSession(): void
    {
        $this->withCookie(config('session.cookie'), session()->getId());
    }

    public function test_starting_a_truth_dare_game_lands_in_the_room(): void
    {
        $create = $this->post('/tw/truth-dare', [
            'tab_id' => 'tab-abc123',          // 瀏覽器的表單真的會送這個
            'players' => ['小美', '小明'],
            'genders' => ['female', 'male'],
        ]);

        $create->assertRedirect();
        $this->keepSession();
        $roomUrl = $create->headers->get('Location');
        $this->assertMatchesRegularExpression('#/tw/truth-dare/[A-Z0-9]+$#', $roomUrl);

        // 轉址過去的這一次**沒有** tab_id,就跟瀏覽器一樣
        $this->get($roomUrl)
            ->assertOk()
            ->assertDontSee(__('games.err_room_expired'));
    }

    public function test_refreshing_the_room_still_works(): void
    {
        $create = $this->post('/tw/truth-dare', [
            'tab_id' => 'tab-xyz789',
            'players' => ['A', 'B'],
        ]);
        $this->keepSession();
        $roomUrl = $create->headers->get('Location');

        // 重新整理三次:每一次都不帶 tab_id
        foreach (range(1, 3) as $i) {
            $this->get($roomUrl)->assertOk();
        }
    }

    public function test_the_state_endpoint_accepts_the_remembered_tab(): void
    {
        /* 房間頁的輪詢會帶 tab_id,但「進房那一次」不會 —— 兩邊算出來的身分
           必須是同一個,否則畫面開得起來卻每兩秒被 403 打回。 */
        $create = $this->post('/tw/truth-dare', [
            'tab_id' => 'tab-poll',
            'players' => ['A', 'B'],
        ]);
        $this->keepSession();
        $code = basename($create->headers->get('Location'));

        $this->get("/tw/truth-dare/{$code}")->assertOk();

        $this->getJson("/tw/truth-dare/{$code}/state?tab_id=tab-poll")
            ->assertOk()
            ->assertJsonPath('is_my_player', true);

        // 不帶 tab_id 的輪詢也要通(session 裡記著)
        $this->getJson("/tw/truth-dare/{$code}/state")->assertOk();
    }

    public function test_a_request_that_does_carry_a_tab_id_still_wins(): void
    {
        /* session 裡記住的 tab 只是後備。帶了 tab_id 的請求要以帶進來的為準,
           否則同一台裝置開兩個分頁會互相蓋掉身分。 */
        $create = $this->post('/tw/truth-dare', ['tab_id' => 'tab-one', 'players' => ['A', 'B']]);
        $this->keepSession();
        $roomUrl = $create->headers->get('Location');

        // 另一個分頁的 tab_id 不是這個房間的成員 → 應該被擋在外面
        $this->get($roomUrl.'?tab_id=tab-two')
            ->assertRedirect(route('truth-dare.lobby', ['locale' => 'tw']));

        // 原本那個分頁仍然進得去
        $this->get($roomUrl.'?tab_id=tab-one')->assertOk();
    }

    public function test_the_flying_chess_room_lets_the_creator_play(): void
    {
        /* 飛行棋是同一支 playerSessionId 的邏輯。它的 show 不會把人踢回大廳,
           只是拿不到 my_color,所以症狀比較隱晦:進得去但不能擲骰。要驗的是
           state API 認不認人,不是頁面渲不渲得出來。 */
        $create = $this->post('/tw/games', [
            'tab_id' => 'tab-fc',
            'player_name' => '小美',
            'max_players' => 2,
        ]);
        $create->assertRedirect();
        $this->keepSession();
        $code = basename($create->headers->get('Location'));

        $this->get("/tw/games/{$code}")->assertOk()->assertSee('小美');

        // 不帶 tab_id 的輪詢也要認得建房的人,否則骰子是灰的
        $this->getJson("/tw/games/{$code}/state")
            ->assertOk()
            ->assertJsonPath('my_color', 'yellow');
    }
}
