<?php

namespace App\Http\Controllers;

use App\Models\Board;
use App\Models\Game;
use App\Models\GamePrompt;
use App\Models\PageView;
use App\Models\TruthDareCard;
use App\Models\User;
use App\Models\WheelSegment;
use App\Rules\NoBlockedWords;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    private function perPage(Request $request, $query): int
    {
        $selected = $request->input('per_page', '20');

        if ($selected === 'all') {
            return max(1, (clone $query)->count());
        }

        $perPage = (int) $selected;

        return in_array($perPage, [20, 50, 100, 200], true) ? $perPage : 20;
    }

    /**
     * 後台列表的欄位排序。
     *
     * $allowed 是白名單,鍵是網址上的 sort 值,值有兩種寫法:
     *   'created_at'                          直接照欄位排
     *   ['tier', ['mild','medium','intense']]  照指定順序排
     *
     * 第二種是給分級這類欄位用的 —— 直接照字串排的話「大膽 intense」會排在
     * 「輕鬆 mild」前面,那不是任何人想看到的順序。CASE 的值走 binding,
     * 而且欄位名只從白名單來,網址上的參數不會進到 SQL 裡。
     */
    private function applySort($query, Request $request, array $allowed, string $default, string $defaultDir = 'desc'): void
    {
        $sort = (string) $request->input('sort');
        $dir = $request->input('dir') === 'asc' ? 'asc' : 'desc';

        if (! isset($allowed[$sort])) {
            $sort = $default;
            $dir = $defaultDir;
        }

        $spec = $allowed[$sort];

        if (is_array($spec)) {
            [$column, $order] = $spec;
            $cases = implode(' ', array_map(fn ($i) => 'WHEN ? THEN '.$i, array_keys($order)));
            $query->orderByRaw("CASE {$column} {$cases} ELSE ".count($order)." END {$dir}", $order);

            return;
        }

        $query->orderBy($spec, $dir);
    }

    /**
     * 可複選的篩選。
     *
     * 網址上是 ?level[]=mild&level[]=medium,一個值也吃(舊連結、手打的網址)。
     * 值一律先跟白名單取交集才進 SQL —— 篩選參數是使用者給的。
     * 一個都沒選就是不過濾,不是「什麼都不顯示」。
     *
     * @param  array  $allowed  合法值;關聯陣列則取它的鍵
     */
    private function applyIn($query, Request $request, string $param, array $allowed, ?string $column = null): void
    {
        if (array_is_list($allowed) === false) {
            $allowed = array_keys($allowed);
        }

        $values = array_values(array_intersect(
            array_map('strval', (array) $request->input($param, [])),
            array_map('strval', $allowed)
        ));

        if ($values) {
            $query->whereIn($column ?? $param, $values);
        }
    }

    /**
     * 「免費／付費」這種是非篩選。
     *
     * 兩個都選等於沒篩選,所以只有剛好選一個的時候才過濾 —— 不特別處理的話
     * whereIn(['0','1']) 在 SQLite 與 MySQL 對 boolean 欄位的行為並不一致。
     */
    private function applyBoolIn($query, Request $request, string $param, string $column): void
    {
        $values = array_values(array_intersect(
            array_map('strval', (array) $request->input($param, [])),
            ['0', '1']
        ));

        if (count($values) === 1) {
            $query->where($column, $values[0] === '1');
        }
    }

    /**
     * 每個選項是一組條件而不是同一欄的值(付費會員、管理員、已封鎖…)。
     *
     * 複選時把它們 OR 起來,而且整組包在一個 where 閉包裡 —— 沒有包起來的話
     * orWhere 會跟前面的搜尋條件平輩,變成「符合任一篩選 或 名字含關鍵字」,
     * 搜尋就等於失效了。
     *
     * @param  array<string, callable>  $predicates
     */
    private function applyAnyOf($query, Request $request, string $param, array $predicates): void
    {
        $selected = array_values(array_intersect(
            array_map('strval', (array) $request->input($param, [])),
            array_keys($predicates)
        ));

        if (! $selected) {
            return;
        }

        $query->where(function ($outer) use ($selected, $predicates) {
            foreach ($selected as $key) {
                $outer->orWhere(fn ($q) => $predicates[$key]($q));
            }
        });
    }

    /**
     * 回到列表時要帶回去的篩選、排序與頁碼。
     *
     * 列表頁的網址帶著一堆狀態(第 7 頁、只看重度、依尺度排序…),點進去編輯
     * 再回來如果全部歸零,等於每改一題就要重找一次。所以列表上的每個連結都
     * 帶一份當時的 query string,存檔後照原樣回去。
     *
     * 只收 query string 而不是完整網址,而且解析後交回 route() 重組 ——
     * 直接把使用者給的字串當網址轉址就是一個開放轉址漏洞。
     */
    private function listReturn(Request $request): array
    {
        parse_str((string) $request->input('return'), $params);

        return is_array($params) ? $params : [];
    }

    // ── Traffic ──

    /**
     * 站內流量。回答的是「人進來之後往哪裡去、在哪一步不見了」,
     * 資料來源是自己的 page_views(見 TrackPageView),不是 GA4。
     *
     * 所有查詢都限定在選定天數內並且走 created_at 的索引;這張表會長很快,
     * 沒有時間範圍的 group by 遲早會把這頁拖垮。
     */
    public function traffic(Request $request)
    {
        $days = (int) $request->input('days', 7);
        $days = in_array($days, [1, 7, 30, 90], true) ? $days : 7;
        $since = now()->subDays($days - 1)->startOfDay();

        $base = fn () => PageView::where('created_at', '>=', $since);

        // 每日趨勢。補上沒有任何瀏覽的日子,不然圖表會把空日直接跳過,
        // 看起來像那天流量正常。
        $daily = $base()
            ->selectRaw('date(created_at) as d, count(*) as views, count(distinct visitor_hash) as visitors')
            ->groupBy('d')->orderBy('d')->get()->keyBy('d');

        $trend = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $key = now()->subDays($i)->toDateString();
            $trend[] = [
                'date' => $key,
                'views' => (int) ($daily[$key]->views ?? 0),
                'visitors' => (int) ($daily[$key]->visitors ?? 0),
            ];
        }

        $topPaths = $base()
            ->selectRaw('path, count(*) as views, count(distinct visitor_hash) as visitors')
            ->groupBy('path')->orderByDesc('views')->limit(25)->get();

        $referrers = $base()->whereNotNull('referer_host')
            ->selectRaw('referer_host, count(*) as views')
            ->groupBy('referer_host')->orderByDesc('views')->limit(15)->get();

        $locales = $base()
            ->selectRaw('locale, count(*) as views')
            ->groupBy('locale')->orderByDesc('views')->get();

        // 動線漏斗。每一階算的是「不重複訪客」而不是瀏覽數 —— 同一個人重整
        // 十次不該讓那一階看起來比較好。
        $reach = function (array $paths) use ($since) {
            return PageView::where('created_at', '>=', $since)
                ->where(function ($q) use ($paths) {
                    foreach ($paths as $p) {
                        $q->orWhere('path', 'like', $p);
                    }
                })
                ->distinct()->count('visitor_hash');
        };

        $funnel = [
            ['label' => '首頁', 'value' => $reach(['/'])],
            ['label' => '遊戲大廳', 'value' => $reach(['/games', '/game-hall'])],
            ['label' => '實際開局', 'value' => $reach(['/play%'])],
            ['label' => '付費頁', 'value' => $reach(['/premium'])],
        ];

        return view('admin.traffic', [
            'days' => $days,
            'trend' => $trend,
            'topPaths' => $topPaths,
            'referrers' => $referrers,
            'locales' => $locales,
            'funnel' => $funnel,
            'totalViews' => $base()->count(),
            'totalVisitors' => (clone $base())->distinct()->count('visitor_hash'),
            'loggedInViews' => $base()->whereNotNull('user_id')->count(),
            'oldestRecord' => PageView::min('created_at'),
        ]);
    }

    // ── Dashboard ──

    public function dashboard()
    {
        $weekStart = now()->subDays(6)->startOfDay();

        $stats = [
            'users' => User::count(),
            'premium' => User::whereNotNull('premium_expires_at')
                ->where('premium_expires_at', '>', now())->count(),
            'boards' => Board::count(),
            'templates' => Board::where('is_template', true)->count(),
            'cards' => TruthDareCard::count(),
            'wheel_segments' => WheelSegment::count(),
            'games' => Game::count(),
            'users_7d' => User::where('created_at', '>=', $weekStart)->count(),
            'games_7d' => Game::where('created_at', '>=', $weekStart)->count(),
            'users_today' => User::whereDate('created_at', now()->toDateString())->count(),
            'games_today' => Game::whereDate('created_at', now()->toDateString())->count(),
            'pending_reviews' => Board::where('publish_status', Board::PUBLISH_PENDING)->count(),
            'published_boards' => Board::where('publish_status', Board::PUBLISH_APPROVED)->count(),
        ];

        // 近 7 天每日序列（含 0 的日子），給迷你長條圖用
        $userDaily = User::where('created_at', '>=', $weekStart)
            ->selectRaw('DATE(created_at) as d, COUNT(*) as c')
            ->groupBy('d')->pluck('c', 'd');
        $gameDaily = Game::where('created_at', '>=', $weekStart)
            ->selectRaw('DATE(created_at) as d, COUNT(*) as c')
            ->groupBy('d')->pluck('c', 'd');

        $dailySeries = collect(range(6, 0))->map(function ($i) use ($userDaily, $gameDaily) {
            $date = now()->subDays($i)->toDateString();

            return [
                'date' => $date,
                'label' => now()->subDays($i)->format('m/d'),
                'users' => (int) ($userDaily[$date] ?? 0),
                'games' => (int) ($gameDaily[$date] ?? 0),
            ];
        });

        $recentUsers = User::latest()->take(5)->get();
        $recentGames = Game::withCount('players')->latest()->take(5)->get();

        return view('admin.dashboard', compact('stats', 'recentUsers', 'recentGames', 'dailySeries'));
    }

    // ── Boards ──

    public function boards(Request $request)
    {
        $query = Board::with('user');

        $this->applyAnyOf($query, $request, 'filter', [
            'template' => fn ($q) => $q->where('is_template', true),
            'default' => fn ($q) => $q->where('is_default', true),
            'user' => fn ($q) => $q->where('is_template', false)->whereNotNull('user_id'),
            'pending' => fn ($q) => $q->where('publish_status', Board::PUBLISH_PENDING),
            'published' => fn ($q) => $q->where('publish_status', Board::PUBLISH_APPROVED),
        ]);

        // Search
        if ($search = $request->input('q')) {
            $query->where('name', 'like', "%{$search}%");
        }

        $this->applySort($query, $request, [
            'id' => 'id',
            'name' => 'name',
            'squares' => 'squares_count',
            'created_at' => 'created_at',
        ], 'created_at');

        $boards = $query->paginate($this->perPage($request, $query))->withQueryString();

        return view('admin.boards.index', compact('boards'));
    }

    public function editBoard(Request $request, Board $board)
    {
        return view('admin.boards.edit', [
            'board' => $board,
            'return' => $this->listReturn($request),
        ]);
    }

    public function updateBoard(Request $request, Board $board)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', new NoBlockedWords],
            'description' => ['nullable', 'string', 'max:500', new NoBlockedWords],
            'is_default' => ['boolean'],
            'is_template' => ['boolean'],
            'is_premium_template' => ['boolean'],
        ]);

        // Ensure only one default board
        if (! empty($data['is_default'])) {
            Board::where('id', '!=', $board->id)->where('is_default', true)
                ->update(['is_default' => false]);
        }

        $board->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'is_default' => $data['is_default'] ?? false,
            'is_template' => $data['is_template'] ?? false,
            'is_premium_template' => $data['is_premium_template'] ?? false,
        ]);

        return redirect()->route('admin.boards', $this->listReturn($request))->with('success', '棋盤已更新');
    }

    // ── Community publish review ──

    public function boardReviews(Request $request)
    {
        $query = Board::with('user:id,name,email')
            ->withCount('squares')
            ->where('publish_status', Board::PUBLISH_PENDING)
            ->oldest('updated_at');
        $boards = $query->paginate($this->perPage($request, $query))->withQueryString();

        return view('admin.boards.reviews', compact('boards'));
    }

    public function approveBoard(Board $board)
    {
        if ($board->publish_status !== Board::PUBLISH_PENDING) {
            return back()->with('error', '此棋盤不在待審狀態');
        }

        $board->update([
            'publish_status' => Board::PUBLISH_APPROVED,
            'published_at' => $board->published_at ?? now(),
            'publish_note' => null,
        ]);

        return back()->with('success', "「{$board->name}」已核准上架");
    }

    public function rejectBoard(Request $request, Board $board)
    {
        $data = $request->validate([
            'publish_note' => ['nullable', 'string', 'max:200'],
        ]);

        if ($board->publish_status !== Board::PUBLISH_PENDING) {
            return back()->with('error', '此棋盤不在待審狀態');
        }

        $board->update([
            'publish_status' => Board::PUBLISH_REJECTED,
            'published_at' => null,
            'publish_note' => $data['publish_note'] ?? null,
        ]);

        return back()->with('success', "「{$board->name}」已退回");
    }

    // ── Cards (Truth or Dare) ──

    public function cards(Request $request)
    {
        $query = TruthDareCard::query();

        $this->applyIn($query, $request, 'category', TruthDareCard::CATEGORIES);
        $this->applyIn($query, $request, 'audience', TruthDareCard::AUDIENCES);
        $this->applyIn($query, $request, 'level', TruthDareCard::LEVELS);
        $this->applyBoolIn($query, $request, 'paid', 'is_paid');
        if ($search = $request->input('q')) {
            $query->where('content', 'like', "%{$search}%");
        }

        $this->applySort($query, $request, [
            'id' => 'id',
            'category' => ['category', array_keys(TruthDareCard::CATEGORIES)],
            'audience' => ['audience', array_keys(TruthDareCard::AUDIENCES)],
            'content' => 'content',
            'level' => ['level', TruthDareCard::LEVEL_ORDER],
            'paid' => 'is_paid',
            'created_at' => 'created_at',
        ], 'created_at');

        $cards = $query->paginate($this->perPage($request, $query))->withQueryString();

        return view('admin.cards.index', compact('cards'));
    }

    public function createCard()
    {
        return view('admin.cards.form', ['card' => null]);
    }

    public function storeCard(Request $request)
    {
        $data = $request->validate([
            'category' => ['required', 'in:'.implode(',', array_keys(TruthDareCard::CATEGORIES))],
            'audience' => ['required', 'in:'.implode(',', array_keys(TruthDareCard::AUDIENCES))],
            'level' => ['required', 'in:'.implode(',', array_keys(TruthDareCard::LEVELS))],
            'content' => ['required', 'string', 'max:500', new NoBlockedWords],
        ]);

        // checkbox 沒勾就完全不送,所以要自己補 false,不然改成免費會存不進去。
        $data['is_paid'] = $request->boolean('is_paid');

        TruthDareCard::create($data);

        return redirect()->route('admin.cards')->with('success', '卡片已新增');
    }

    public function editCard(Request $request, TruthDareCard $card)
    {
        return view('admin.cards.form', [
            'card' => $card,
            'return' => $this->listReturn($request),
        ]);
    }

    public function updateCard(Request $request, TruthDareCard $card)
    {
        $data = $request->validate([
            'category' => ['required', 'in:'.implode(',', array_keys(TruthDareCard::CATEGORIES))],
            'audience' => ['required', 'in:'.implode(',', array_keys(TruthDareCard::AUDIENCES))],
            'level' => ['required', 'in:'.implode(',', array_keys(TruthDareCard::LEVELS))],
            'content' => ['required', 'string', 'max:500', new NoBlockedWords],
        ]);

        // checkbox 沒勾就完全不送,所以要自己補 false,不然改成免費會存不進去。
        $data['is_paid'] = $request->boolean('is_paid');

        $card->update($data);

        return redirect()->route('admin.cards', $this->listReturn($request))
            ->with('success', '卡片已更新');
    }

    public function destroyCard(Request $request, TruthDareCard $card)
    {
        $card->delete();

        return redirect()->route('admin.cards', $this->listReturn($request))
            ->with('success', '卡片已刪除');
    }

    // ── Wheel Segments ──

    public function wheelSegments(Request $request)
    {
        $query = WheelSegment::query();

        $this->applyIn($query, $request, 'tier', WheelSegment::TIERS);
        if ($search = $request->input('q')) {
            $query->where('content', 'like', "%{$search}%");
        }

        $this->applyBoolIn($query, $request, 'paid', 'is_paid');

        $this->applySort($query, $request, [
            'id' => 'id',
            'tier' => ['tier', array_keys(WheelSegment::TIERS)],
            'content' => 'content',
            'paid' => 'is_paid',
            'created_at' => 'created_at',
        ], 'created_at');

        $segments = $query->paginate($this->perPage($request, $query))->withQueryString();

        return view('admin.wheel.index', compact('segments'));
    }

    public function createWheelSegment(Request $request)
    {
        return view('admin.wheel.form', [
            'segment' => null,
            'return' => $this->listReturn($request),
        ]);
    }

    public function storeWheelSegment(Request $request)
    {
        $data = $request->validate([
            'content' => ['required', 'string', 'max:200', new NoBlockedWords],
            'tier' => ['required', 'in:'.implode(',', array_keys(WheelSegment::TIERS))],
        ]);

        // checkbox 沒勾就完全不送,要自己補 false。
        $data['is_paid'] = $request->boolean('is_paid');

        WheelSegment::create($data);

        return redirect()->route('admin.wheel-segments')->with('success', '轉盤任務已新增');
    }

    public function editWheelSegment(Request $request, WheelSegment $wheelSegment)
    {
        return view('admin.wheel.form', [
            'segment' => $wheelSegment,
            'return' => $this->listReturn($request),
        ]);
    }

    public function updateWheelSegment(Request $request, WheelSegment $wheelSegment)
    {
        $data = $request->validate([
            'content' => ['required', 'string', 'max:200', new NoBlockedWords],
            'tier' => ['required', 'in:'.implode(',', array_keys(WheelSegment::TIERS))],
        ]);

        // checkbox 沒勾就完全不送,要自己補 false。
        $data['is_paid'] = $request->boolean('is_paid');

        $wheelSegment->update($data);

        return redirect()->route('admin.wheel-segments', $this->listReturn($request))
            ->with('success', '轉盤任務已更新');
    }

    public function destroyWheelSegment(Request $request, WheelSegment $wheelSegment)
    {
        $wheelSegment->delete();

        return redirect()->route('admin.wheel-segments', $this->listReturn($request))
            ->with('success', '轉盤任務已刪除');
    }

    // ── Game prompts(四個小遊戲的題庫)──

    /**
     * 誰最有可能 / 撲克牌 / 國王 / 骰子 的題目。
     *
     * 四個遊戲共用一頁而不是各給一頁:欄位一模一樣(遊戲、分級、內容),
     * 拆開只會複製四份相同的 CRUD,以後改一個欄位要改四個地方。
     */
    public function prompts(Request $request)
    {
        $game = $request->input('game', 'who_most_likely');
        if (! isset(GamePrompt::GAMES[$game])) {
            $game = 'who_most_likely';
        }

        $query = GamePrompt::where('game', $game);

        $this->applyIn($query, $request, 'pool', GamePrompt::POOLS[$game]);
        if ($search = $request->input('q')) {
            $query->where('content', 'like', "%{$search}%");
        }
        $this->applyBoolIn($query, $request, 'paid', 'is_paid');

        $this->applySort($query, $request, [
            'id' => 'id',
            'pool' => ['pool', array_keys(GamePrompt::POOLS[$game])],
            'content' => 'content',
            'paid' => 'is_paid',
            'sort_order' => 'sort_order',
        ], 'pool', 'asc');
        $query->orderBy('sort_order')->orderBy('id');

        $prompts = $query->paginate($this->perPage($request, $query))->withQueryString();

        return view('admin.prompts.index', [
            'prompts' => $prompts,
            'game' => $game,
            /* 「新增題目」要帶一個預設分類過去。篩選現在是複選,只有剛好選一個
               的時候才拿它當預設 —— 選了三個分類還幫他挑一個當預設是猜的。 */
            'pool' => count($selectedPools = (array) $request->input('pool', [])) === 1
                ? reset($selectedPools)
                : null,
            // 這個遊戲還沒匯入過預設題目時,頁面要提示可以一鍵匯入。
            'isEmpty' => ! GamePrompt::where('game', $game)->exists(),
        ]);
    }

    public function createPrompt(Request $request)
    {
        $game = $request->input('game', 'who_most_likely');
        if (! isset(GamePrompt::GAMES[$game])) {
            $game = 'who_most_likely';
        }

        $pool = $request->input('pool');

        return view('admin.prompts.form', [
            'prompt' => null,
            'game' => $game,
            // 從列表帶過來的分類要能當預設值,但只在它屬於這個遊戲的時候。
            'pool' => isset(GamePrompt::POOLS[$game][$pool]) ? $pool : null,
        ]);
    }

    public function editPrompt(Request $request, GamePrompt $prompt)
    {
        return view('admin.prompts.form', [
            'prompt' => $prompt,
            'game' => $prompt->game,
            'pool' => $prompt->pool,
            'return' => $this->listReturn($request),
        ]);
    }

    public function storePrompt(Request $request)
    {
        $data = $this->validatePrompt($request);
        GamePrompt::create($data);

        return redirect()->route('admin.prompts', ['game' => $data['game']])
            ->with('success', '題目已新增');
    }

    public function updatePrompt(Request $request, GamePrompt $prompt)
    {
        $data = $this->validatePrompt($request);
        $prompt->update($data);

        return redirect()->route('admin.prompts', $this->listReturn($request) + ['game' => $data['game']])
            ->with('success', '題目已更新');
    }

    public function destroyPrompt(Request $request, GamePrompt $prompt)
    {
        $game = $prompt->game;
        $prompt->delete();

        return redirect()->route('admin.prompts', $this->listReturn($request) + ['game' => $game])
            ->with('success', '題目已刪除');
    }

    /**
     * 把程式碼裡的預設題庫匯進資料表,好讓管理員有東西可以改。
     *
     * 只在該遊戲一題都沒有時才做 —— 否則重複點就會把題庫灌成兩份。
     */
    public function importPrompts(Request $request)
    {
        $game = $request->input('game');
        abort_unless(isset(GamePrompt::GAMES[$game]), 404);

        if (GamePrompt::where('game', $game)->exists()) {
            return redirect()->route('admin.prompts', ['game' => $game])
                ->with('success', '這個遊戲已經有題目了,沒有重複匯入');
        }

        GamePrompt::importDefaults($game);

        return redirect()->route('admin.prompts', ['game' => $game])
            ->with('success', '預設題目已匯入,現在可以編輯了');
    }

    private function validatePrompt(Request $request): array
    {
        $data = $request->validate([
            'game' => ['required', 'in:'.implode(',', array_keys(GamePrompt::GAMES))],
            'content' => ['required', 'string', 'max:200', new NoBlockedWords],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);

        // pool 的合法值取決於 game,所以不能寫死在上面那組規則裡。
        $data['pool'] = $request->validate([
            'pool' => ['required', 'in:'.implode(',', array_keys(GamePrompt::POOLS[$data['game']]))],
        ])['pool'];

        $data['sort_order'] = $data['sort_order'] ?? 0;
        // checkbox 沒勾就完全不送,要自己補 false,不然改成免費會存不進去。
        $data['is_paid'] = $request->boolean('is_paid');

        return $data;
    }

    // ── Users ──

    public function editUser(Request $request, User $user)
    {
        return view('admin.users.edit', [
            'user' => $user,
            'return' => $this->listReturn($request),
        ]);
    }

    public function updateUser(Request $request, User $user)
    {
        $data = $request->validate([
            'is_admin' => ['boolean'],
            'premium_expires_at' => ['nullable', 'date'],
        ]);

        $wantsAdmin = (bool) ($data['is_admin'] ?? false);

        // Prevent removing the last admin
        if ($user->is_admin && ! $wantsAdmin) {
            $adminCount = User::where('is_admin', true)->count();
            if ($adminCount <= 1) {
                return back()->withErrors(['is_admin' => '無法移除最後一位管理員權限'])->withInput();
            }
        }

        $user->update([
            'is_admin' => $wantsAdmin,
            'premium_expires_at' => $data['premium_expires_at'] ?: null,
        ]);

        return redirect()->route('admin.users', $this->listReturn($request))->with('success', '會員資料已更新');
    }

    public function users(Request $request)
    {
        $query = User::withCount('boards');

        $this->applyAnyOf($query, $request, 'filter', [
            'premium' => fn ($q) => $q->whereNotNull('premium_expires_at')
                ->where('premium_expires_at', '>', now()),
            'admin' => fn ($q) => $q->where('is_admin', true),
            'banned' => fn ($q) => $q->where('is_banned', true),
        ]);

        if ($search = $request->input('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $this->applySort($query, $request, [
            'id' => 'id',
            'name' => 'name',
            'email' => 'email',
            'boards' => 'boards_count',
            'created_at' => 'created_at',
        ], 'created_at');

        $users = $query->paginate($this->perPage($request, $query))->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function banUser(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            return back()->withErrors(['ban' => '不能封鎖自己的帳號']);
        }
        if ($user->isAdmin()) {
            return back()->withErrors(['ban' => '不能封鎖管理員帳號']);
        }

        $user->update([
            'is_banned' => true,
            'banned_at' => now(),
        ]);

        return back()->with('success', "已封鎖會員「{$user->name}」");
    }

    public function unbanUser(Request $request, User $user)
    {
        $user->update([
            'is_banned' => false,
            'banned_at' => null,
        ]);

        return back()->with('success', "已解除封鎖會員「{$user->name}」");
    }

    public function destroyUser(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            return back()->withErrors(['delete' => '不能刪除自己的帳號']);
        }
        if ($user->isAdmin()) {
            return back()->withErrors(['delete' => '不能刪除管理員帳號']);
        }

        // boards.user_id 是 nullOnDelete，要手動連帶刪除（board_squares 會隨棋盤 cascade）。
        // 預設棋盤是 /play 的 fallback，保留不刪，FK 會自動把 user_id 設為 null。
        $user->boards()->where('is_default', false)->delete();
        $user->delete();

        return redirect()->route('admin.users')->with('success', '會員及其棋盤已刪除');
    }

    // ── Games ──

    public function games(Request $request)
    {
        // host.user 一起載進來,不然一頁 100 場就是 200 次查詢。
        $query = Game::withCount('players')->with('host.user');

        $this->applyIn($query, $request, 'status', ['waiting', 'playing', 'finished']);

        if ($search = $request->input('q')) {
            /* 房間代碼、開房者的暱稱、註冊會員的帳號與 email 都能搜 ——
               後台會想從「這個人開了哪些場」這個方向找,不是只有從代碼找。 */
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhereHas('host', function ($h) use ($search) {
                        $h->where('player_name', 'like', "%{$search}%")
                            ->orWhereHas('user', function ($u) use ($search) {
                                $u->where('name', 'like', "%{$search}%")
                                    ->orWhere('email', 'like', "%{$search}%");
                            });
                    });
            });
        }

        $this->applySort($query, $request, [
            'id' => 'id',
            'code' => 'code',
            'game_type' => 'game_type',
            'status' => ['status', ['waiting', 'playing', 'finished']],
            'players' => 'players_count',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
        ], 'created_at');

        $games = $query->paginate($this->perPage($request, $query))->withQueryString();

        return view('admin.games.index', compact('games'));
    }

    public function destroyGame(Game $game)
    {
        $game->delete();

        return back()->with('success', "場次 {$game->code} 已刪除");
    }

    public function cleanupGames()
    {
        // 已結束、或超過 7 天沒有任何更新的場次（waiting/playing 視為廢棄）
        $count = Game::where('updated_at', '<', now()->subDays(7))->delete();

        return redirect()->route('admin.games')->with('success', "已清理 {$count} 筆 7 天前的場次");
    }
}
