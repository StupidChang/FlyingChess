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

    public function lobby()
    {
        $games = Game::where('game_type', 'truth_or_dare')
            ->where('status', 'waiting')
            ->where('is_private', false)
            ->withCount('players')
            ->orderByDesc('created_at')
            ->paginate(12);

        return view('truth-dare.lobby', compact('games'));
    }

    public function create(Request $request)
    {
        $data = $request->validate([
            'player_name' => ['required', 'string', 'min:1', 'max:20', new NoBlockedWords],
            'is_private' => 'boolean',
        ]);

        $isPrivate = $data['is_private'] ?? false;

        // Private rooms require premium
        if ($isPrivate) {
            $user = $request->user();
            if (!$user || !$user->isPremium()) {
                return back()->with('error', '建立私人房間需要付費會員，請先升級。');
            }
        }

        // Store host user_id for premium card pool detection (driver-agnostic)
        $hostUserId = $request->user()?->id;

        $result = $this->service->createGame(
            $data['player_name'],
            $request->session()->getId(),
            $isPrivate,
            $hostUserId
        );

        $request->session()->put('player_name', $data['player_name']);

        return redirect()->route('truth-dare.show', $result['game']->code)
            ->with('success', '房間已建立！');
    }

    public function show(Request $request, string $code)
    {
        $game = Game::where('code', $code)
            ->where('game_type', 'truth_or_dare')
            ->with('players')
            ->firstOrFail();

        $sessionId = $request->session()->getId();
        $myPlayer = $game->players->firstWhere('session_id', $sessionId);
        $playerName = $request->session()->get('player_name', '玩家');

        // Host premium: stored in game_state (session-driver agnostic)
        $hostIsPremium = $this->resolveHostPremium($game);

        return view('truth-dare.show', compact(
            'game', 'myPlayer', 'playerName', 'hostIsPremium'
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
            $request->session()->getId()
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
        $sessionId = $request->session()->getId();
        if (!$game->players()->where('session_id', $sessionId)->exists()) {
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
        $sessionId = $request->session()->getId();
        if (!$game->players()->where('session_id', $sessionId)->exists()) {
            return response()->json(['success' => false, 'message' => '你不在此房間中。'], 403);
        }

        if (!$game->isPlaying()) {
            return response()->json(['success' => false, 'message' => '遊戲尚未開始。']);
        }

        // Check it's the current player's turn
        $players = $game->players()->orderBy('id')->get();
        $currentIndex = $game->game_state['current_player_index'] ?? 0;
        $currentPlayer = $players->values()->get($currentIndex);

        if (!$currentPlayer || $currentPlayer->session_id !== $sessionId) {
            return response()->json(['success' => false, 'message' => '還沒輪到你。']);
        }

        $hostIsPremium = $this->resolveHostPremium($game);

        $result = $this->service->drawCard($game, $data['category'], $hostIsPremium);

        return response()->json($result);
    }

    public function nextPlayer(Request $request, string $code)
    {
        $game = Game::where('code', $code)
            ->where('game_type', 'truth_or_dare')
            ->firstOrFail();

        // Must be in the room
        $sessionId = $request->session()->getId();
        if (!$game->players()->where('session_id', $sessionId)->exists()) {
            return response()->json(['success' => false, 'message' => '你不在此房間中。'], 403);
        }

        // Only current player can advance
        $players = $game->players()->orderBy('id')->get();
        $currentIndex = $game->game_state['current_player_index'] ?? 0;
        $currentPlayer = $players->values()->get($currentIndex);

        if (!$currentPlayer || $currentPlayer->session_id !== $sessionId) {
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

        $sessionId = $request->session()->getId();
        $myPlayer = $game->players->firstWhere('session_id', $sessionId);

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

        $result = $this->service->leaveGame($game, $request->session()->getId());

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
