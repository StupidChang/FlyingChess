<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Services\GameService;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GameServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_started_game_cannot_be_reset_by_starting_again(): void
    {
        $service = app(GameService::class);
        $created = $service->createGame('Host', 2, 'host-session');
        $game = $created['game'];
        $service->joinGame($game, 'Guest', 'guest-session');

        $this->assertTrue($service->startGame($game->fresh())['success']);

        $game->refresh();
        $state = $game->game_state;
        $state['pieces']['yellow'][0] = 12;
        $game->update(['game_state' => $state]);

        $result = $service->startGame($game->fresh());

        $this->assertFalse($result['success']);
        $this->assertSame(12, $game->fresh()->game_state['pieces']['yellow'][0]);
    }

    public function test_tab_identity_fits_database_column(): void
    {
        $response = $this->withoutMiddleware(ValidateCsrfToken::class)
            ->withHeader('User-Agent', 'Googlebot')
            ->withHeader('X-Tab-Id', str_repeat('x', 500))
            ->post('/tw/games', [
                'player_name' => 'Host',
                'max_players' => 2,
            ]);

        $response->assertRedirect();
        $this->assertLessThanOrEqual(
            64,
            strlen(Game::firstOrFail()->players()->firstOrFail()->session_id),
        );
    }
}
