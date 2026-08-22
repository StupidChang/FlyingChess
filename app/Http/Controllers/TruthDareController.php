<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\GamePlayer;
use App\Models\User;
use App\Rules\NoBlockedWords;
use App\Services\TruthDareService;
use App\Support\PremiumAccess;
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
        $tab = $request->input('tab_id') ?? $request->header('X-Tab-Id', '');

        // tab_id 是使用者可控字串。直接串接寫進 game_players.session_id 的話,
        // SQLite 的 TEXT 欄位不限長度,一次請求就能塞進好幾 MB,乘上每場最多 6
        // 個玩家列 = 免費的磁碟填爆管道。跟 GameController 一樣先 hash 成固定 64
        // 字元再用 —— tab 內容仍能區分不同分頁,但長度被釘死。
        /* 沒帶 tab_id 的請求 —— 建立房間之後的 302 轉址、重新整理、上一頁 ——
           必須算出**同一個**身分,否則會被判成「不是這個房間的人」。
           2026-08-21 用瀏覽器實測到的:大廳的表單會送 tab_id,所以玩家被存成
           hash(session|tab);接著的 GET /truth-dare/{code} 沒有 tab_id,算出來是
           純 session id,對不上 → 直接被踢回大廳。從真人的角度看就是「按開始遊戲
           沒反應」,而測試不會抓到(測試不送 tab_id,兩邊剛好都用純 session id)。

           所以:看到 tab_id 就記在 session 裡,沒帶的請求就用記住的那一個。
           有帶的仍然以帶進來的為準(多分頁時各自的身分不受影響)。 */
        if ($tab !== null && $tab !== '') {
            $request->session()->put('tab_id', $tab);
        } else {
            $tab = (string) $request->session()->get('tab_id', '');
        }

        return $tab !== '' ? hash('sha256', "{$base}|{$tab}") : $base;
    }

    /**
     * 找出這次請求對應的玩家列。
     *
     * 三段對應三種時期寫進 session_id 的格式:
     * - `$sessionId`:現在的寫法 —— hash(session|tab),或沒有 tab 時的純 session id
     * - 純 session id:在完全沒帶 tab_id 的請求裡建的房
     * - `{身分}#N`:同一台裝置的第 2 位之後的玩家(見 create())。第 1 位不帶後綴,
     *   所以只有第 1 位離開房間之後才會走到這一段。
     */
    private function findMyPlayer(Game $game, Request $request): ?GamePlayer
    {
        $sessionId = $this->playerSessionId($request);
        $baseSessionId = $request->session()->getId();

        return $game->players->firstWhere('session_id', $sessionId)
            ?? $game->players->firstWhere('session_id', $baseSessionId)
            ?? $game->players->first(fn ($p) => str_starts_with($p->session_id, $sessionId.'#')
                || str_starts_with($p->session_id, $baseSessionId.'#'));
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
            'mode' => ['nullable', 'in:couple,party'],
            'escalate' => ['nullable', 'boolean'],
            // 性別可以不填,所以是 nullable 而不是 required。
            'genders' => ['nullable', 'array', 'max:6'],
            'genders.*' => ['nullable', 'in:'.implode(',', array_keys(GamePlayer::GENDERS))],
        ]);

        /* 名字與性別是兩個平行陣列。先把空白的名字濾掉,性別要跟著同一個索引
           一起濾 —— 只濾其中一邊的話,第二個人的性別會套到第三個人身上。 */
        $rows = [];
        foreach (array_map('trim', $data['players']) as $i => $name) {
            if ($name !== '') {
                $rows[] = ['name' => $name, 'gender' => $data['genders'][$i] ?? null];
            }
        }

        $names = array_column($rows, 'name');
        if (empty($rows)) {
            $rows = [['name' => __('games.td_player_default'), 'gender' => null]];
        }
        $rows = array_slice($rows, 0, 6);
        $names = array_column($rows, 'name');

        $hostUserId = $request->user()?->id;
        $sessionId = $this->playerSessionId($request);

        /* 情侶場還是多人場。預設看人數 —— 兩個人就是情侶場,三個人以上是多人場 ——
           但表單可以覆寫:兩個朋友(不是情侶)一起玩,要的是多人場的題目。 */
        $mode = $data['mode'] ?? (count($names) >= 3 ? 'party' : 'couple');

        // Adults-only site: every room is created in adult mode.
        $result = $this->service->createGame(
            $names[0], $sessionId, false, $hostUserId, true, $mode,
            (bool) ($data['escalate'] ?? false), $rows[0]['gender']
        );
        $game = $result['game'];

        // Add the remaining local players. Each needs a DISTINCT session_id
        // (game_players has a UNIQUE(game_id, session_id) constraint), so we
        // suffix the host session. The device holder acts for all of them.
        foreach (array_slice($rows, 1) as $i => $row) {
            $game->players()->create([
                'session_id' => $sessionId.'#'.($i + 1),
                'player_name' => $row['name'],
                'gender' => $row['gender'],
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

        $myPlayer = $this->findMyPlayer($game, $request);

        // Non-players cannot view game page — redirect to lobby with message
        if (! $myPlayer) {
            return redirect()->route('truth-dare.lobby')
                ->with('error', __('games.err_room_expired'));
        }

        $playerName = $request->session()->get('player_name', __('games.player_fallback'));

        // Host premium: stored in game_state (session-driver agnostic)
        $hostIsPremium = $this->resolveHostPremium($game);
        $isAdult = (bool) ($game->game_state['is_adult'] ?? false);
        $mode = $game->game_state['mode'] ?? 'couple';

        return view('truth-dare.show', compact(
            'game', 'myPlayer', 'playerName', 'hostIsPremium', 'isAdult', 'mode'
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

        if (! $result['success']) {
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
        if (! $game->players()->where('session_id', $sessionId)->exists()
            && ! $game->players()->where('session_id', $request->session()->getId())->exists()) {
            return response()->json(['success' => false, 'message' => __('games.err_not_in_room')], 403);
        }

        $result = $this->service->startGame($game);

        return response()->json($result);
    }

    public function draw(Request $request, string $code)
    {
        $data = $request->validate([
            // 只剩類型。情侶／多人是開局就決定的場合,不是抽牌時再選的分類。
            'category' => 'required|string|in:truth,dare',
        ]);

        $game = Game::where('code', $code)
            ->where('game_type', 'truth_or_dare')
            ->firstOrFail();

        // Must be in the room to draw
        $sessionId = $this->playerSessionId($request);
        if (! $game->players()->where('session_id', $sessionId)->exists()
            && ! $game->players()->where('session_id', $request->session()->getId())->exists()) {
            return response()->json(['success' => false, 'message' => __('games.err_not_in_room')], 403);
        }

        if (! $game->isPlaying()) {
            return response()->json(['success' => false, 'message' => __('games.err_game_not_started')]);
        }

        // Same-device play: one device controls every player's turn, so we don't
        // gate the draw on "is it your turn" — being in the room is enough.
        /* 房主是付費會員,或這台裝置剛看完廣告 —— 兩者都算抽得到付費題目。
           同機遊玩是這個遊戲的主要玩法(一台裝置輪流傳),所以看廣告解鎖
           在這裡跟其他四個小遊戲一致。 */
        $hasPremiumContent = $this->resolveHostPremium($game)
            || PremiumAccess::content($request->user());
        $isAdult = (bool) ($game->game_state['is_adult'] ?? false);

        $result = $this->service->drawCard($game, $data['category'], $hasPremiumContent, $isAdult);

        return response()->json($result);
    }

    public function nextPlayer(Request $request, string $code)
    {
        $game = Game::where('code', $code)
            ->where('game_type', 'truth_or_dare')
            ->firstOrFail();

        // Must be in the room (same-device: the device holder advances the turn)
        $sessionId = $this->playerSessionId($request);
        if (! $game->players()->where('session_id', $sessionId)->exists()
            && ! $game->players()->where('session_id', $request->session()->getId())->exists()) {
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
        $myPlayer = $this->findMyPlayer($game, $request);

        // Must be in the room to see state
        if (! $myPlayer) {
            return response()->json(['success' => false, 'message' => __('games.err_not_in_room')], 403);
        }

        $players = $game->players()->orderBy('id')->get();

        $currentIndex = $game->game_state['current_player_index'] ?? 0;
        $currentPlayer = $players->values()->get($currentIndex);

        return response()->json([
            'status' => $game->status,
            'game_state' => $game->game_state,
            'players' => $players->map(fn ($p) => [
                'player_name' => $p->player_name,
                // 輪詢會把整塊玩家列重畫,性別沒帶過來的話伺服器渲染的標籤
                // 會在第一次輪詢就被洗掉。
                'gender' => $p->gender,
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
        if (! ($result['success'] ?? true)) {
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
        if (! $hostUserId) {
            return false;
        }

        $hostUser = User::find($hostUserId);

        return $hostUser && $hostUser->isPremium();
    }
}
