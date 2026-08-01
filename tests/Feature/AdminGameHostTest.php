<?php

namespace Tests\Feature;

use App\Http\Middleware\AgeVerification;
use App\Models\Game;
use App\Models\GamePlayer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 後台的場次列表要看得出「誰開的」。
 */
class AdminGameHostTest extends TestCase
{
    use RefreshDatabase;

    private function game(array $host): Game
    {
        $game = Game::create([
            'code' => 'ABC'.rand(100, 999),
            'game_type' => 'flying_chess',
            'status' => 'playing',
            'max_players' => 4,
        ]);

        GamePlayer::create(array_merge([
            'game_id' => $game->id,
            'is_host' => true,
            'session_id' => 'sess-'.rand(1000, 9999),
            'player_name' => '房主',
        ], $host));

        return $game;
    }

    private function admin(): User
    {
        $this->withoutMiddleware(AgeVerification::class);

        return User::factory()->create(['is_admin' => true]);
    }

    public function test_a_registered_host_shows_the_account(): void
    {
        $member = User::factory()->create(['name' => '王小明']);
        $this->game(['user_id' => $member->id, 'player_name' => '小明']);

        $this->actingAs($this->admin())->get('/tw/admin/games')
            ->assertOk()
            ->assertSee('王小明')
            // 遊戲裡用的暱稱跟帳號不同時兩個都要看得到,不然對不起來。
            ->assertSee('小明')
            ->assertSee(route('admin.users.edit', $member), false);
    }

    public function test_a_guest_host_gets_a_stable_code(): void
    {
        $this->game(['user_id' => null, 'session_id' => 'guest-session', 'player_name' => '路人甲']);
        $expected = GamePlayer::first()->guestCode();

        $this->actingAs($this->admin())->get('/tw/admin/games')
            ->assertOk()
            ->assertSee('路人甲')
            ->assertSee('訪客')
            ->assertSee($expected);
    }

    public function test_the_raw_session_id_never_reaches_the_page(): void
    {
        $this->game(['user_id' => null, 'session_id' => 'super-secret-session-id']);

        /* session_id 是還在生效的識別碼 —— 印在後台等於任何看得到畫面的人
           都能冒用那個 session。代號必須是雜湊過的。 */
        $this->actingAs($this->admin())->get('/tw/admin/games')
            ->assertOk()
            ->assertDontSee('super-secret-session-id');
    }

    public function test_the_same_visitor_keeps_the_same_code_across_games(): void
    {
        // 代號要能回答「這幾場是不是同一個人開的」,所以必須穩定。
        $this->game(['user_id' => null, 'session_id' => 'same-visitor']);
        $this->game(['user_id' => null, 'session_id' => 'same-visitor']);
        $this->game(['user_id' => null, 'session_id' => 'other-visitor']);

        $codes = GamePlayer::all()->map(fn ($p) => $p->guestCode());

        $this->assertSame($codes[0], $codes[1]);
        $this->assertNotSame($codes[0], $codes[2]);
    }

    public function test_games_can_be_searched_by_who_opened_them(): void
    {
        $member = User::factory()->create(['name' => '王小明']);
        $mine = $this->game(['user_id' => $member->id, 'player_name' => '小明']);
        $other = $this->game(['user_id' => null, 'player_name' => '別人']);

        $this->actingAs($this->admin())->get('/tw/admin/games?q=王小明')
            ->assertOk()
            ->assertSee($mine->code)
            ->assertDontSee($other->code);
    }

    public function test_the_origin_is_recorded_when_a_game_is_opened(): void
    {
        $this->withoutMiddleware(AgeVerification::class);

        $this->withHeader('referer', 'https://www.google.com/search?q=%E6%83%85%E4%BE%B6%E9%81%8A%E6%88%B2')
            ->post('/tw/truth-dare', ['players' => ['小明', '小美']]);

        $game = Game::latest('id')->first();

        // 只留主機名 —— 完整網址會把使用者搜了什麼一起存下來。
        $this->assertSame('www.google.com', $game->origin_referer);
        $this->assertSame('zh_TW', $game->origin_locale);
    }

    public function test_internal_links_are_not_treated_as_a_source(): void
    {
        $this->withoutMiddleware(AgeVerification::class);

        // 站內互連不是來源,記了只會把真正的外部來源洗掉。
        $this->withHeader('referer', config('app.url').'/tw/truth-dare')
            ->post('/tw/truth-dare', ['players' => ['小明', '小美']]);

        $this->assertNull(Game::latest('id')->first()->origin_referer);
    }

    public function test_a_game_with_no_host_row_still_renders(): void
    {
        // 中途離開會刪掉玩家列,舊資料有可能整場都沒有 is_host。
        Game::create([
            'code' => 'NOHOST',
            'game_type' => 'flying_chess',
            'status' => 'finished',
            'max_players' => 4,
        ]);

        $this->actingAs($this->admin())->get('/tw/admin/games')
            ->assertOk()
            ->assertSee('NOHOST');
    }
}
