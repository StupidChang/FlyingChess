<?php

namespace App\Http\Controllers;

use App\Models\Board;
use App\Models\Game;
use App\Rules\NoBlockedWords;
use App\Services\GameService;
use App\Support\PremiumAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GameController extends Controller
{
    public function __construct(private GameService $gameService) {}

    /**
     * Build a unique player identity string.
     * Combines the PHP session ID with an optional per-tab token so that
     * two browser tabs sharing the same session can be separate players.
     */
    private function playerSessionId(Request $request): string
    {
        $base = $request->session()->getId();
        $tab = $request->input('tab_id') ?? $request->header('X-Tab-Id', '');

        // game_players.session_id is limited to 64 characters. Browser tab IDs
        // are commonly UUIDs, so concatenating them to Laravel's session ID can
        // exceed the column and turn a normal join into a database error.
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

    public function lobby(Request $request)
    {
        // 上方的切換:網站棋盤(範本+預設)或社群棋盤(會員發佈、審核通過的)
        $tab = $request->query('tab') === 'community' ? 'community' : 'site';

        if ($tab === 'community') {
            // 跟 BoardController::community 同一個條件:已審核、至少兩格才玩得起來
            $boards = Board::published()
                ->has('squares', '>=', 2)
                ->with(['squares', 'user:id,name'])
                ->withCount('squares')
                ->orderByDesc('published_at')
                ->paginate(12)
                ->withQueryString();
        } else {
            $boards = Board::where(function ($q) {
                $q->where('is_template', true)
                    ->orWhere('is_default', true);
            })
                ->with('squares')
                ->withCount('squares')
                // 免費的全部排在前面,付費的放後面;同一段裡預設棋盤最先、再來是新的
                ->orderBy('is_premium_template')
                ->orderByDesc('is_default')
                ->orderByDesc('created_at')
                ->paginate(12);
        }

        return view('games.lobby', compact('boards', 'tab'));
    }

    /**
     * 大廳卡片的快速預覽:照路線順序列出每一格的內容。
     *
     * 只開放大廳本來就列得出來的棋盤(範本、預設、社群已審核),私人棋盤一律 404 ——
     * 不然換個數字就能讀到別人的私人棋盤(/play/{board} 同樣的防列舉規則)。
     * 付費範本沒有權限時只給 Board::previewOpenPositions() 那 8 格,其餘格子**不送文字**,
     * 跟範本預覽頁同一份規則,兩邊合起來也不會多看到。
     */
    public function boardPreview(Request $request, Board $board)
    {
        abort_unless($board->is_template || $board->is_default || $board->isPublished(), 404);

        $board->load('squares');
        $canSeeAll = ! $board->is_premium_template || PremiumAccess::content($request->user());
        $open = $canSeeAll ? null : array_flip($board->previewOpenPositions());
        $byPos = $board->squares->keyBy('position');

        $squares = collect($board->resolvedPath())
            ->filter(fn ($pos) => $byPos->has($pos))
            ->values()
            ->map(function ($pos, $i) use ($byPos, $open) {
                $sq = $byPos[$pos];
                $locked = $open !== null && ! isset($open[$pos]);

                return [
                    'step' => $i,
                    'color' => $sq->color,
                    'text' => $locked ? null : $sq->text,
                    'locked' => $locked,
                ];
            });

        return response()->json([
            'name' => $board->name,
            'description' => $board->description,
            'audience' => $board->isGroupPlay() ? __('play.audience_group') : __('play.audience_couple'),
            'locked' => ! $canSeeAll,
            'squares' => $squares,
            'play_url' => $canSeeAll ? $board->canonicalPlayUrl() : null,
            'preview_url' => $board->is_template ? route('boards.template.preview', $board) : null,
        ])->header('X-Robots-Tag', 'noindex');
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
            $this->playerSessionId($request),
            $solo,
            $request->user()?->id
        );

        $request->session()->put('player_name', $data['player_name']);

        $msg = $solo ? __('games.flash_solo_started') : __('games.flash_room_created');

        return redirect()->route('games.show', $result['game']->code)->with('success', $msg);
    }

    public function show(Request $request, string $code)
    {
        $game = Game::where('code', $code)
            ->where('game_type', 'flying_chess')
            ->withCount('players')
            ->with('players')
            ->firstOrFail();

        $sessionId = $this->playerSessionId($request);
        $myPlayer = $game->players->firstWhere('session_id', $sessionId)
                   ?? $game->players->firstWhere('session_id', $request->session()->getId());
        $playerName = $request->session()->get('player_name', __('games.player_fallback'));

        $boardData = [
            'track' => GameService::BOARD_TRACK,
            'safeLanes' => GameService::SAFE_LANES,
            'homePos' => GameService::HOME_POSITIONS,
            'center' => GameService::CENTER,
            'safeSquares' => GameService::SAFE_SQUARES,
            'startOffsets' => GameService::START_OFFSETS,
        ];

        return view('games.show', compact('game', 'myPlayer', 'playerName', 'boardData'));
    }

    public function join(Request $request, string $code)
    {
        $data = $request->validate([
            'player_name' => ['required', 'string', 'min:1', 'max:20', new NoBlockedWords],
        ]);

        $game = Game::where('code', $code)->where('game_type', 'flying_chess')->firstOrFail();
        $result = $this->gameService->joinGame(
            $game,
            $data['player_name'],
            $this->playerSessionId($request),
            $request->user()?->id
        );

        if (! $result['success']) {
            return back()->with('error', $result['message']);
        }

        $request->session()->put('player_name', $data['player_name']);

        return redirect()->route('games.show', $code);
    }

    public function start(Request $request, string $code)
    {
        return DB::transaction(function () use ($request, $code) {
            $game = Game::where('code', $code)
                ->where('game_type', 'flying_chess')
                ->lockForUpdate()
                ->firstOrFail();
            $sessionId = $this->playerSessionId($request);
            $myPlayer = $game->players()->where('session_id', $sessionId)->first()
                ?? $game->players()->where('session_id', $request->session()->getId())->first();

            if (! $myPlayer || ! $myPlayer->is_host) {
                return response()->json(['success' => false, 'message' => __('games.err_host_only_start')], 403);
            }

            return response()->json($this->gameService->startGame($game));
        }, 3);
    }

    public function roll(Request $request, string $code)
    {
        return DB::transaction(function () use ($request, $code) {
            $game = Game::where('code', $code)->where('game_type', 'flying_chess')
                ->lockForUpdate()->firstOrFail();
            $sessionId = $this->playerSessionId($request);
            $myPlayer = $game->players()->where('session_id', $sessionId)->first()
                ?? $game->players()->where('session_id', $request->session()->getId())->first();

            if (! $myPlayer) {
                return response()->json(['success' => false, 'message' => __('games.err_not_in_game')], 403);
            }

            $result = $this->gameService->rollDice($game, $myPlayer->color);

            // If roll resulted in no moves (turn auto-passed), execute any pending bot turns
            $noMoves = empty($result['valid_moves'] ?? []) && ! ($result['three_sixes'] ?? false);
            if ($result['success'] && $noMoves) {
                $botActions = $this->executePendingBotTurns($game);
                if (! empty($botActions)) {
                    $game->refresh();
                    $result['state'] = $game->game_state;
                    $result['bot_actions'] = $botActions;
                    if ($game->isFinished()) {
                        $result['winner'] = $game->game_state['winner'] ?? null;
                    }
                }
            }

            return response()->json($result);
        }, 3);
    }

    public function move(Request $request, string $code)
    {
        $data = $request->validate(['piece_index' => 'required|integer|between:0,3']);

        return DB::transaction(function () use ($request, $code, $data) {
            $game = Game::where('code', $code)->where('game_type', 'flying_chess')
                ->lockForUpdate()->firstOrFail();
            $sessionId = $this->playerSessionId($request);
            $myPlayer = $game->players()->where('session_id', $sessionId)->first()
                      ?? $game->players()->where('session_id', $request->session()->getId())->first();

            if (! $myPlayer) {
                return response()->json(['success' => false, 'message' => __('games.err_not_in_game')], 403);
            }

            $result = $this->gameService->movePiece($game, $myPlayer->color, $data['piece_index']);

            if (! $result['success'] || isset($result['winner'])) {
                return response()->json($result);
            }

            // After human moves, let all pending bot turns run
            $botActions = $this->executePendingBotTurns($game);
            if (! empty($botActions)) {
                $game->refresh();
                $result['state'] = $game->game_state;
                $result['bot_actions'] = $botActions;
                if ($game->isFinished()) {
                    $result['winner'] = $game->game_state['winner'] ?? null;
                }
            }

            return response()->json($result);
        }, 3);
    }

    public function state(Request $request, string $code)
    {
        $game = Game::where('code', $code)->where('game_type', 'flying_chess')->with('players')->firstOrFail();
        $sessionId = $this->playerSessionId($request);
        $myPlayer = $game->players->firstWhere('session_id', $sessionId)
                  ?? $game->players->firstWhere('session_id', $request->session()->getId());

        return response()->json([
            'status' => $game->status,
            'game_state' => $game->game_state,
            'players' => $game->players->map(fn ($p) => [
                'color' => $p->color,
                'player_name' => $p->player_name,
                'is_host' => $p->is_host,
                'is_bot' => str_starts_with($p->session_id, 'bot_'),
            ]),
            'my_color' => $myPlayer?->color,
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
            if (! $game->isPlaying()) {
                break;
            }

            $state = $game->game_state;
            $bots = $state['bots'] ?? [];
            if (! in_array($state['current_color'], $bots)) {
                break;
            }

            $result = $this->gameService->executeBotTurn($game);

            $actions[] = [
                'color' => $result['bot_color'] ?? $state['current_color'],
                'dice' => $result['bot_dice'] ?? null,
                'piece' => $result['bot_piece'] ?? null,
                'action' => $result['bot_action'] ?? 'move',
            ];

            if (! $result['success'] || isset($result['winner'])) {
                break;
            }
        }

        return $actions;
    }
}
