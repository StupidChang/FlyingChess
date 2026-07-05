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
        // Same-device play: all players sit on one device and take turns, so we
        // accept a list of names and create every player under the same session.
        $data = $request->validate([
            'players' => ['required', 'array', 'min:1', 'max:6'],
            'players.*' => ['nullable', 'string', 'max:20', new NoBlockedWords],
        ]);

        $names = array_values(array_filter(
            array_map('trim', $data['players']),
            fn ($n) => $n !== ''
        ));
        if (empty($names)) {
            $names = [__('games.td_player_default')];
        }
        $names = array_slice($names, 0, 6);

        $hostUserId = $request->user()?->id;
        $sessionId = $this->playerSessionId($request);

        // Adults-only site: every room is created in adult mode.
        $result = $this->service->createGame($names[0], $sessionId, false, $hostUserId, true);
        $game = $result['game'];

        // Add the remaining local players. Each needs a DISTINCT session_id
        // (game_players has a UNIQUE(game_id, session_id) constraint), so we
        // suffix the host session. The device holder acts for all of them.
        foreach (array_slice($names, 1) as $i => $name) {
            $game->players()->create([
                'session_id' => $sessionId . '#' . ($i + 1),
                'player_name' => $name,
                'color' => 'none',
                'is_host' => false,
                'user_id' => $hostUserId,
            ]);
        }

        $request->session()->put('player_name', $names[0]);

        // Auto-start for direct play
        $this->service->startGame($game);

        return redirect()->route('truth-dare.show', $game->code);
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
                ->with('error', __('games.err_room_expired'));
        }

        $playerName = $request->session()->get('player_name', __('games.player_fallback'));

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
            $this->playerSessionId($request),
            $request->user()?->id
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
            return response()->json(['success' => false, 'message' => __('games.err_not_in_room')], 403);
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
            return response()->json(['success' => false, 'message' => __('games.err_not_in_room')], 403);
        }

        if (!$game->isPlaying()) {
            return response()->json(['success' => false, 'message' => __('games.err_game_not_started')]);
        }

        // Same-device play: one device controls every player's turn, so we don't
        // gate the draw on "is it your turn" — being in the room is enough.
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

        // Must be in the room (same-device: the device holder advances the turn)
        $sessionId = $this->playerSessionId($request);
        if (!$game->players()->where('session_id', $sessionId)->exists()
            && !$game->players()->where('session_id', $request->session()->getId())->exists()) {
            return response()->json(['success' => false, 'message' => __('games.err_not_in_room')], 403);
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
            return response()->json(['success' => false, 'message' => __('games.err_not_in_room')], 403);
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
            ->with('success', __('games.flash_left_room'));
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
