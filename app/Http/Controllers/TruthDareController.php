<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\User;
use App\Rules\NoBlockedWords;
use App\Services\TruthDareService;
use Illuminate\Http\Request;

class TruthDareController extends Controller
{
    public function __construct(private TruthDareService $service) {}

    /**
     * Build a unique player identity string.
     * Combines the PHP session ID with an optional per-tab token so that
     * two browser tabs sharing the same session can be separate players.
     */
    private function playerSessionId(Request $request): string
    {
        $base = $request->session()->getId();
        $tab  = $request->input('tab_id') ?? $request->header('X-Tab-Id', '');
        return $tab ? "{$base}|{$tab}" : $base;
    }

    public function lobby()
    {
        return view('truth-dare.lobby');
    }

    public function create(Request $request)
    {
        $data = $request->validate([
            'player_name' => ['required', 'string', 'min:1', 'max:20', new NoBlockedWords],
            'is_adult' => 'boolean',
        ]);

        $hostUserId = $request->user()?->id;

        $result = $this->service->createGame(
            $data['player_name'],
            $this->playerSessionId($request),
            false,
            $hostUserId,
            (bool) ($data['is_adult'] ?? false)
        );

        $request->session()->put('player_name', $data['player_name']);

        // Auto-start the game for direct play
        $this->service->startGame($result['game']);

        return redirect()->route('truth-dare.show', $result['game']->code);
    }

    public function show(Request $request, string $code)
    {
        $game = Game::where('code', $code)
            ->where('game_type', 'truth_or_dare')
            ->with('players')
            ->firstOrFail();

        $sessionId = $this->playerSessionId($request);
        $baseSessionId = $request->session()->getId();
        $myPlayer  = $game->players->firstWhere('session_id', $sessionId)
                  ?? $game->players->firstWhere('session_id', $baseSessionId)
                  ?? $game->players->first(fn($p) => str_starts_with($p->session_id, $baseSessionId . '|'));

        // Non-players cannot view game page — redirect to lobby with message
        if (!$myPlayer) {
            return redirect()->route('truth-dare.lobby')
                ->with('error', '無法進入此房間，可能連線已過期，請重新建立遊戲。');
        }

        $playerName = $request->session()->get('player_name', '玩家');

        // Host premium: stored in game_state (session-driver agnostic)
        $hostIsPremium = $this->resolveHostPremium($game);
        $isAdult = (bool) ($game->game_state['is_adult'] ?? false);

        return view('truth-dare.show', compact(
            'game', 'myPlayer', 'playerName', 'hostIsPremium', 'isAdult'
        ));
    }

    public function join(Request $request, string $code)
    {
        $data = $request->validate([
            'player_name' => ['required', 'string', 'min:1', 'max:20', new NoBlockedWords],
        ]);

        $game = Game::where('code', $code)
            ->where('game_type', 'truth_or_dare')
            ->firstOrFail();

        $result = $this->service->joinGame(
            $game,
            $data['player_name'],
            $this->playerSessionId($request)
        );

        if (!$result['success']) {
            return back()->with('error', $result['message']);
        }

        $request->session()->put('player_name', $data['player_name']);

        return redirect()->route('truth-dare.show', $code);
    }

    public function start(Request $request, string $code)
    {
        $game = Game::where('code', $code)
            ->where('game_type', 'truth_or_dare')
            ->firstOrFail();

        // Must be in the room to start
        $sessionId = $this->playerSessionId($request);
        if (!$game->players()->where('session_id', $sessionId)->exists()
            && !$game->players()->where('session_id', $request->session()->getId())->exists()) {
            return response()->json(['success' => false, 'message' => '你不在此房間中。'], 403);
        }

        $result = $this->service->startGame($game);

        return response()->json($result);
    }

    public function draw(Request $request, string $code)
    {
        $data = $request->validate([
            'category' => 'required|string|in:truth,dare,couple,party',
        ]);

        $game = Game::where('code', $code)
            ->where('game_type', 'truth_or_dare')
            ->firstOrFail();

        // Must be in the room to draw
        $sessionId = $this->playerSessionId($request);
        if (!$game->players()->where('session_id', $sessionId)->exists()
            && !$game->players()->where('session_id', $request->session()->getId())->exists()) {
            return response()->json(['success' => false, 'message' => '你不在此房間中。'], 403);
        }

        if (!$game->isPlaying()) {
            return response()->json(['success' => false, 'message' => '遊戲尚未開始。']);
        }

        // Check it's the current player's turn
        $players = $game->players()->orderBy('id')->get();
        $currentIndex = $game->game_state['current_player_index'] ?? 0;
        $currentPlayer = $players->values()->get($currentIndex);

        $baseSessionId = $request->session()->getId();
        if (!$currentPlayer || ($currentPlayer->session_id !== $sessionId && $currentPlayer->session_id !== $baseSessionId)) {
            return response()->json(['success' => false, 'message' => '還沒輪到你。']);
        }

        $hostIsPremium = $this->resolveHostPremium($game);
        $isAdult = (bool) ($game->game_state['is_adult'] ?? false);

        $result = $this->service->drawCard($game, $data['category'], $hostIsPremium, $isAdult);

        return response()->json($result);
    }

    public function nextPlayer(Request $request, string $code)
    {
        $game = Game::where('code', $code)
            ->where('game_type', 'truth_or_dare')
            ->firstOrFail();

        // Must be in the room
        $sessionId = $this->playerSessionId($request);
        if (!$game->players()->where('session_id', $sessionId)->exists()
            && !$game->players()->where('session_id', $request->session()->getId())->exists()) {
            return response()->json(['success' => false, 'message' => '你不在此房間中。'], 403);
        }

        // Only current player can advance
        $players = $game->players()->orderBy('id')->get();
        $currentIndex = $game->game_state['current_player_index'] ?? 0;
        $currentPlayer = $players->values()->get($currentIndex);

        $baseSessionId = $request->session()->getId();
        if (!$currentPlayer || ($currentPlayer->session_id !== $sessionId && $currentPlayer->session_id !== $baseSessionId)) {
            return response()->json(['success' => false, 'message' => '還沒輪到你。']);
        }

        $result = $this->service->nextPlayer($game);

        return response()->json($result);
    }

    public function state(Request $request, string $code)
    {
        $game = Game::where('code', $code)
            ->where('game_type', 'truth_or_dare')
            ->with('players')
            ->firstOrFail();

        $sessionId = $this->playerSessionId($request);
        $baseSessionId = $request->session()->getId();
        $myPlayer  = $game->players->firstWhere('session_id', $sessionId)
                  ?? $game->players->firstWhere('session_id', $baseSessionId)
                  ?? $game->players->first(fn($p) => str_starts_with($p->session_id, $baseSessionId . '|'));

        // Must be in the room to see state
        if (!$myPlayer) {
            return response()->json(['success' => false, 'message' => '你不在此房間中。'], 403);
        }

        $players = $game->players()->orderBy('id')->get();

        $currentIndex = $game->game_state['current_player_index'] ?? 0;
        $currentPlayer = $players->values()->get($currentIndex);

        return response()->json([
            'status' => $game->status,
            'game_state' => $game->game_state,
            'players' => $players->map(fn($p) => [
                'player_name' => $p->player_name,
                'is_host' => $p->is_host,
                'session_id' => $p->session_id,
            ]),
            'my_session_id' => $sessionId,
            'is_my_player' => true,
            'current_player' => $currentPlayer ? [
                'player_name' => $currentPlayer->player_name,
                'session_id' => $currentPlayer->session_id,
            ] : null,
            'players_count' => $players->count(),
        ]);
    }

    public function leave(Request $request, string $code)
    {
        $game = Game::where('code', $code)
            ->where('game_type', 'truth_or_dare')
            ->firstOrFail();

        $sessionId = $this->playerSessionId($request);
        $result = $this->service->leaveGame($game, $sessionId);

        // Fallback: try plain session ID if composite didn't match
        if (!($result['success'] ?? true)) {
            $result = $this->service->leaveGame($game, $request->session()->getId());
        }

        if ($request->expectsJson()) {
            return response()->json($result);
        }

        return redirect()->route('truth-dare.lobby')
            ->with('success', '你已離開房間。');
    }

    /**
     * Resolve host premium status from game_state.host_user_id (session-driver agnostic).
     */
    private function resolveHostPremium(Game $game): bool
    {
        $hostUserId = $game->game_state['host_user_id'] ?? null;
        if (!$hostUserId) {
            return false;
        }

        $hostUser = User::find($hostUserId);
        return $hostUser && $hostUser->isPremium();
    }
}
