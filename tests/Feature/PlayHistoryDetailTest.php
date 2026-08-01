<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\GamePlayer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 遊玩紀錄要看得出「什麼時候玩的、玩了多久、跟誰玩」。
 */
class PlayHistoryDetailTest extends TestCase
{
    use RefreshDatabase;

    private function playedGame(User $user, array $others = [], ?int $minutes = null): Game
    {
        $game = Game::create([
            'code' => 'ABC'.rand(100, 999),
            'game_type' => 'flying_chess',
            'status' => $minutes === null ? 'playing' : 'finished',
            'max_players' => 4,
            'finished_at' => $minutes === null ? null : now()->addMinutes($minutes),
        ]);

        GamePlayer::create([
            'game_id' => $game->id, 'user_id' => $user->id,
            'player_name' => '我', 'is_host' => true, 'session_id' => 'a',
        ]);
        foreach ($others as $i => $name) {
            GamePlayer::create([
                'game_id' => $game->id, 'player_name' => $name,
                'is_host' => false, 'session_id' => 'b'.$i,
            ]);
        }

        return $game;
    }

    public function test_the_history_lists_who_else_played(): void
    {
        $user = User::factory()->create();
        $this->playedGame($user, ['小美', '阿豪'], 25);

        $this->actingAs($user)->withUnencryptedCookie('age_verified', '1')
            ->get('/tw/profile')
            ->assertOk()
            ->assertSee('小美')
            ->assertSee('阿豪');
    }

    public function test_a_finished_game_shows_how_long_it_took(): void
    {
        $user = User::factory()->create();
        $this->playedGame($user, ['小美'], 25);

        $this->actingAs($user)->withUnencryptedCookie('age_verified', '1')
            ->get('/tw/profile')
            ->assertSee('25');
    }

    public function test_an_unfinished_game_shows_no_duration(): void
    {
        $user = User::factory()->create();
        $this->playedGame($user, ['小美']);   // 沒有 finished_at

        /* 進行中的房間不能用「現在減開始時間」當時長 —— 一場開著三個月沒關的房
           會顯示成玩了三個月。沒結束就只說進行中。 */
        $this->actingAs($user)->withUnencryptedCookie('age_verified', '1')
            ->get('/tw/profile')
            ->assertSee(__('ui.history_ongoing'));
    }

    public function test_a_solo_game_says_so_instead_of_listing_nobody(): void
    {
        $user = User::factory()->create();
        $this->playedGame($user, [], 10);

        $this->actingAs($user)->withUnencryptedCookie('age_verified', '1')
            ->get('/tw/profile')
            ->assertSee(__('ui.history_alone'));
    }

    public function test_the_profile_links_to_custom_dice_and_wheels(): void
    {
        $user = User::factory()->create();

        // 功能一直都在,但個人頁沒有入口等於沒有。
        $this->actingAs($user)->withUnencryptedCookie('age_verified', '1')
            ->get('/tw/profile')
            ->assertOk()
            ->assertSee(route('dice.index'))
            ->assertSee(route('custom-wheel.index'));
    }
}
