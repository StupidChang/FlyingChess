<?php

namespace Tests\Feature;

use App\Http\Middleware\AgeVerification;
use App\Models\Game;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 直接關掉分頁的場次不會自己結束 —— 真心話大冒險要所有人按離開、飛行棋要走到終點。
 * 沒有 games:close-idle 的話,後台的場次會永遠停在「進行中」。
 */
class CloseIdleGamesTest extends TestCase
{
    use RefreshDatabase;

    private function game(string $code, string $status, int $idleHours, string $type = 'truth_or_dare'): Game
    {
        $game = Game::create([
            'code' => $code,
            'game_type' => $type,
            'status' => $status,
            'max_players' => 4,
            'game_state' => ['current_player_index' => 0],
        ]);
        $game->timestamps = false;
        $game->forceFill(['updated_at' => now()->subHours($idleHours)])->save();

        return $game;
    }

    public function test_idle_games_are_closed_and_active_or_finished_ones_are_left_alone(): void
    {
        $idle = $this->game('IDLE01', 'playing', Game::IDLE_CLOSE_HOURS + 1);
        $idleWaiting = $this->game('IDLE02', 'waiting', Game::IDLE_CLOSE_HOURS + 5, 'flying_chess');
        $active = $this->game('LIVE01', 'playing', 1);
        $finished = $this->game('DONE01', 'finished', 100);

        $this->artisan('games:close-idle')->assertSuccessful();

        $this->assertTrue($idle->fresh()->isAbandoned());
        $this->assertNotNull($idle->fresh()->finished_at);
        $this->assertTrue($idleWaiting->fresh()->isAbandoned());
        $this->assertTrue($active->fresh()->isPlaying());
        $this->assertTrue($finished->fresh()->isFinished());
    }

    public function test_the_job_is_scheduled(): void
    {
        $this->artisan('schedule:list')->expectsOutputToContain('games:close-idle');
    }

    public function test_a_closed_room_tells_players_to_start_a_new_one(): void
    {
        $this->withoutMiddleware(AgeVerification::class);
        // 真心話大冒險的房間只有房內玩家打得開(非玩家會被轉回大廳),測試 client 帶不出
        // 同一個玩家身分,所以這裡只驗飛行棋;兩頁用的是同一組 closed_* 文案。
        $this->game('IDLE04', Game::STATUS_ABANDONED, 20, 'flying_chess');

        $this->get('/tw/games/IDLE04')->assertOk()
            ->assertSee(__('games.closed_title'))
            ->assertSee(route('games.lobby'));
    }
}
