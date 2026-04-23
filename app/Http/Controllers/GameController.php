<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\GamePlayer;
use App\Rules\NoBlockedWords;
use App\Services\GameService;
use Illuminate\Http\Request;

class GameController extends Controller
{
    public function __construct(private GameService $gameService) {}

    public function lobby()
    {
        return view('games.lobby');
    }

    public function create(Request $request)
    {
        $solo = $request->boolean('solo');

        $data = $request->validate([
            'player_name' => ['required', 'string', 'min:1', 'max:20', new NoBlockedWords],
            'max_players' => $solo ? 'nullable' : 'required|integer|in:2,3,4',
        ]);

        $result = $this->gameService->createGame(
            $data['player_name'],
            (int) ($data['max_players'] ?? 4),
            $request->session()->getId(),
            $solo
        );

        $request->session()->put('player_name', $data['player_name']);

        $msg = $solo ? '單人遊戲已開始！' : '房間已建立！分享房間代碼給朋友吧。';
        return redirect()->route('games.show', $result['game']->code)->with('success', $msg);
    }

    public function show(Request $request, string $code)
    {
        $game = Game::where('code', $code)
            ->where('game_type', 'flying_chess')
            ->withCount('players')
            ->with('players')
            ->firstOrFail();

        $sessionId  = $request->session()->getId();
        $myPlayer   = $game->players->firstWhere('session_id', $sessionId);
        $playerName = $request->session()->get('player_name', '玩家');

        $boardData = [
            'track'        => GameService::BOARD_TRACK,
            'safeLanes'    => GameService::SAFE_LANES,
            'homePos'      => GameService::HOME_POSITIONS,
            'center'       => GameService::CENTER,
            'safeSquares'  => GameService::SAFE_SQUARES,
            'startOffsets' => GameService::START_OFFSETS,
        ];

        return view('games.show', compact('game', 'myPlayer', 'playerName', 'boardData'));
    }

    public function join(Request $request, string $code)
    {
        $data = $request->validate([
            'player_name' => ['required', 'string', 'min:1', 'max:20', new NoBlockedWords],
        ]);

        $game   = Game::where('code', $code)->where('game_type', 'flying_chess')->firstOrFail();
        $result = $this->gameService->joinGame(
            $game,
            $data['player_name'],
            $request->session()->getId()
        );

        if (!$result['success']) {
            return back()->with('error', $result['message']);
        }

        $request->session()->put('player_name', $data['player_name']);

        return redirect()->route('games.show', $code);
    }

    public function start(Request $request, string $code)
    {
        $game      = Game::where('code', $code)->where('game_type', 'flying_chess')->firstOrFail();
        $sessionId = $request->session()->getId();
        $myPlayer  = $game->players()->where('session_id', $sessionId)->first();

        if (!$myPlayer || !$myPlayer->is_host) {
            return response()->json(['success' => false, 'message' => '只有房主可以開始遊戲'], 403);
        }

        $result = $this->gameService->startGame($game);
        return response()->json($result);
    }

    public function roll(Request $request, string $code)
    {
        $game      = Game::where('code', $code)->where('game_type', 'flying_chess')->firstOrFail();
        $sessionId = $request->session()->getId();
        $myPlayer  = $game->players()->where('session_id', $sessionId)->first();

        if (!$myPlayer) {
            return response()->json(['success' => false, 'message' => '你不在此遊戲中'], 403);
        }

        $result = $this->gameService->rollDice($game, $myPlayer->color);

        // If roll resulted in no moves (turn auto-passed), execute any pending bot turns
        $noMoves = empty($result['valid_moves'] ?? []) && !($result['three_sixes'] ?? false);
        if ($result['success'] && $noMoves) {
            $botActions = $this->executePendingBotTurns($game);
            if (!empty($botActions)) {
                $game->refresh();
                $result['state']       = $game->game_state;
                $result['bot_actions'] = $botActions;
                if ($game->isFinished()) {
                    $result['winner'] = $game->game_state['winner'] ?? null;
                }
            }
        }

        return response()->json($result);
    }

    public function move(Request $request, string $code)
    {
        $data = $request->validate(['piece_index' => 'required|integer|between:0,3']);

        $game      = Game::where('code', $code)->where('game_type', 'flying_chess')->firstOrFail();
        $sessionId = $request->session()->getId();
        $myPlayer  = $game->players()->where('session_id', $sessionId)->first();

        if (!$myPlayer) {
            return response()->json(['success' => false, 'message' => '你不在此遊戲中'], 403);
        }

        $result = $this->gameService->movePiece($game, $myPlayer->color, $data['piece_index']);

        if (!$result['success'] || isset($result['winner'])) {
            return response()->json($result);
        }

        // After human moves, let all pending bot turns run
        $botActions = $this->executePendingBotTurns($game);
        if (!empty($botActions)) {
            $game->refresh();
            $result['state']       = $game->game_state;
            $result['bot_actions'] = $botActions;
            if ($game->isFinished()) {
                $result['winner'] = $game->game_state['winner'] ?? null;
            }
        }

        return response()->json($result);
    }

    public function state(Request $request, string $code)
    {
        $game      = Game::where('code', $code)->where('game_type', 'flying_chess')->with('players')->firstOrFail();
        $sessionId = $request->session()->getId();
        $myPlayer  = $game->players->firstWhere('session_id', $sessionId);

        return response()->json([
            'status'        => $game->status,
            'game_state'    => $game->game_state,
            'players'       => $game->players->map(fn($p) => [
                'color'       => $p->color,
                'player_name' => $p->player_name,
                'is_host'     => $p->is_host,
                'is_bot'      => str_starts_with($p->session_id, 'bot_'),
            ]),
            'my_color'      => $myPlayer?->color,
            'players_count' => $game->players->count(),
        ]);
    }

    // -------------------------------------------------------
    // Private
    // -------------------------------------------------------

    /**
     * Run bot turns until it's the human's turn (or game ends).
     * Returns array of bot action summaries.
     */
    private function executePendingBotTurns(Game $game): array
    {
        $actions = [];
        $maxIter = 24; // safety cap (4 bots × 6 consecutive turns max)

        while ($maxIter-- > 0) {
            $game->refresh();
            if (!$game->isPlaying()) break;

            $state = $game->game_state;
            $bots  = $state['bots'] ?? [];
            if (!in_array($state['current_color'], $bots)) break;

            $result = $this->gameService->executeBotTurn($game);

            $actions[] = [
                'color'  => $result['bot_color'] ?? $state['current_color'],
                'dice'   => $result['bot_dice']  ?? null,
                'piece'  => $result['bot_piece'] ?? null,
                'action' => $result['bot_action'] ?? 'move',
            ];

            if (!$result['success'] || isset($result['winner'])) break;
        }

        return $actions;
    }
}
