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
        $stats = [
            'users'     => User::count(),
            'premium'   => User::whereNotNull('premium_expires_at')
                              ->where('premium_expires_at', '>', now())->count(),
            'boards'    => Board::count(),
            'templates' => Board::where('is_template', true)->count(),
            'cards'     => TruthDareCard::count(),
            'wheel_segments' => WheelSegment::count(),
            'games'     => Game::count(),
        ];

        $recentUsers = User::latest()->take(5)->get();

        return view('admin.dashboard', compact('stats', 'recentUsers'));
    }

    // ── Boards ──

    public function boards(Request $request)
    {
        $query = Board::with('user');

        // Filter
        $filter = $request->input('filter', 'all');
        match ($filter) {
            'template' => $query->where('is_template', true),
            'default'  => $query->where('is_default', true),
            'user'     => $query->where('is_template', false)->whereNotNull('user_id'),
            default    => null,
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
            'name'                => ['required', 'string', 'max:100', new NoBlockedWords],
            'description'         => ['nullable', 'string', 'max:500', new NoBlockedWords],
            'is_default'          => ['boolean'],
            'is_template'         => ['boolean'],
            'is_premium_template' => ['boolean'],
        ]);

        // Ensure only one default board
        if (!empty($data['is_default'])) {
            Board::where('id', '!=', $board->id)->where('is_default', true)
                 ->update(['is_default' => false]);
        }

        $board->update([
            'name'                => $data['name'],
            'description'         => $data['description'] ?? null,
            'is_default'          => $data['is_default'] ?? false,
            'is_template'         => $data['is_template'] ?? false,
            'is_premium_template' => $data['is_premium_template'] ?? false,
        ]);

        return redirect()->route('admin.boards')->with('success', '棋盤已更新');
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
            'content'  => ['required', 'string', 'max:500', new NoBlockedWords],
            'tier'     => ['required', 'in:free,premium'],
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
            'content'  => ['required', 'string', 'max:500', new NoBlockedWords],
            'tier'     => ['required', 'in:free,premium'],
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
            'tier'    => ['required', 'in:mild,medium,intense'],
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
            'tier'    => ['required', 'in:mild,medium,intense'],
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
            'is_admin'           => ['boolean'],
            'premium_expires_at' => ['nullable', 'date'],
        ]);

        $wantsAdmin = (bool) ($data['is_admin'] ?? false);

        // Prevent removing the last admin
        if ($user->is_admin && !$wantsAdmin) {
            $adminCount = User::where('is_admin', true)->count();
            if ($adminCount <= 1) {
                return back()->withErrors(['is_admin' => '無法移除最後一位管理員權限'])->withInput();
            }
        }

        $user->update([
            'is_admin'           => $wantsAdmin,
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
            'admin'   => $query->where('is_admin', true),
            default   => null,
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
}
