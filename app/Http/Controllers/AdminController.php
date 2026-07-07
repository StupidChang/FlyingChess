<?php

namespace App\Http\Controllers;

use App\Models\Board;
use App\Models\Game;
use App\Models\TruthDareCard;
use App\Models\User;
use App\Models\WheelSegment;
use App\Rules\NoBlockedWords;
use Illuminate\Http\Request;

class AdminController extends Controller
{
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

        // Filter
        $filter = $request->input('filter', 'all');
        match ($filter) {
            'template' => $query->where('is_template', true),
            'default' => $query->where('is_default', true),
            'user' => $query->where('is_template', false)->whereNotNull('user_id'),
            'pending' => $query->where('publish_status', Board::PUBLISH_PENDING),
            'published' => $query->where('publish_status', Board::PUBLISH_APPROVED),
            default => null,
        };

        // Search
        if ($search = $request->input('q')) {
            $query->where('name', 'like', "%{$search}%");
        }

        $boards = $query->latest()->paginate(20)->withQueryString();

        return view('admin.boards.index', compact('boards', 'filter'));
    }

    public function editBoard(Board $board)
    {
        return view('admin.boards.edit', compact('board'));
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

        return redirect()->route('admin.boards')->with('success', '棋盤已更新');
    }

    // ── Community publish review ──

    public function boardReviews()
    {
        $boards = Board::with('user:id,name,email')
            ->withCount('squares')
            ->where('publish_status', Board::PUBLISH_PENDING)
            ->oldest('updated_at')
            ->paginate(20);

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

        if ($category = $request->input('category')) {
            $query->where('category', $category);
        }
        if ($tier = $request->input('tier')) {
            $query->where('tier', $tier);
        }
        if ($search = $request->input('q')) {
            $query->where('content', 'like', "%{$search}%");
        }

        $cards = $query->latest()->paginate(20)->withQueryString();

        return view('admin.cards.index', compact('cards'));
    }

    public function createCard()
    {
        return view('admin.cards.form', ['card' => null]);
    }

    public function storeCard(Request $request)
    {
        $data = $request->validate([
            'category' => ['required', 'in:truth,dare,couple,party'],
            'content' => ['required', 'string', 'max:500', new NoBlockedWords],
            'tier' => ['required', 'in:free,premium'],
        ]);

        TruthDareCard::create($data);

        return redirect()->route('admin.cards')->with('success', '卡片已新增');
    }

    public function editCard(TruthDareCard $card)
    {
        return view('admin.cards.form', compact('card'));
    }

    public function updateCard(Request $request, TruthDareCard $card)
    {
        $data = $request->validate([
            'category' => ['required', 'in:truth,dare,couple,party'],
            'content' => ['required', 'string', 'max:500', new NoBlockedWords],
            'tier' => ['required', 'in:free,premium'],
        ]);

        $card->update($data);

        return redirect()->route('admin.cards')->with('success', '卡片已更新');
    }

    public function destroyCard(TruthDareCard $card)
    {
        $card->delete();

        return redirect()->route('admin.cards')->with('success', '卡片已刪除');
    }

    // ── Wheel Segments ──

    public function wheelSegments(Request $request)
    {
        $query = WheelSegment::query();

        if ($tier = $request->input('tier')) {
            $query->where('tier', $tier);
        }
        if ($search = $request->input('q')) {
            $query->where('content', 'like', "%{$search}%");
        }

        $segments = $query->latest()->paginate(20)->withQueryString();

        return view('admin.wheel.index', compact('segments'));
    }

    public function createWheelSegment()
    {
        return view('admin.wheel.form', ['segment' => null]);
    }

    public function storeWheelSegment(Request $request)
    {
        $data = $request->validate([
            'content' => ['required', 'string', 'max:200', new NoBlockedWords],
            'tier' => ['required', 'in:mild,medium,intense'],
        ]);

        WheelSegment::create($data);

        return redirect()->route('admin.wheel-segments')->with('success', '轉盤任務已新增');
    }

    public function editWheelSegment(WheelSegment $wheelSegment)
    {
        return view('admin.wheel.form', ['segment' => $wheelSegment]);
    }

    public function updateWheelSegment(Request $request, WheelSegment $wheelSegment)
    {
        $data = $request->validate([
            'content' => ['required', 'string', 'max:200', new NoBlockedWords],
            'tier' => ['required', 'in:mild,medium,intense'],
        ]);

        $wheelSegment->update($data);

        return redirect()->route('admin.wheel-segments')->with('success', '轉盤任務已更新');
    }

    public function destroyWheelSegment(WheelSegment $wheelSegment)
    {
        $wheelSegment->delete();

        return redirect()->route('admin.wheel-segments')->with('success', '轉盤任務已刪除');
    }

    // ── Users ──

    public function editUser(User $user)
    {
        return view('admin.users.edit', compact('user'));
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

        return redirect()->route('admin.users')->with('success', '會員資料已更新');
    }

    public function users(Request $request)
    {
        $query = User::withCount('boards');

        $filter = $request->input('filter', 'all');
        match ($filter) {
            'premium' => $query->whereNotNull('premium_expires_at')
                ->where('premium_expires_at', '>', now()),
            'admin' => $query->where('is_admin', true),
            'banned' => $query->where('is_banned', true),
            default => null,
        };

        if ($search = $request->input('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->latest()->paginate(20)->withQueryString();

        return view('admin.users.index', compact('users', 'filter'));
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
        $query = Game::withCount('players');

        $status = $request->input('status', 'all');
        if (in_array($status, ['waiting', 'playing', 'finished'], true)) {
            $query->where('status', $status);
        }

        if ($search = $request->input('q')) {
            $query->where('code', 'like', "%{$search}%");
        }

        $games = $query->latest()->paginate(20)->withQueryString();

        return view('admin.games.index', compact('games', 'status'));
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
