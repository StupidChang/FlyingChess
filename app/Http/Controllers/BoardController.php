<?php

namespace App\Http\Controllers;

use App\Models\Board;
use App\Models\BoardSquare;
use App\Rules\NoBlockedWords;
use App\Support\BoardShapes;
use App\Support\PremiumAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class BoardController extends Controller
{
    private function checkOwnership(Board $board): void
    {
        $user = Auth::user();

        if (! $user || ($board->user_id !== $user->id && ! $user->isAdmin())) {
            abort(403, __('play.err_board_forbidden'));
        }
    }

    /**
     * 已上架(送審中/已核准)的棋盤,只要改到「玩起來會看到的內容」就退回 pending
     * 重審。少了這個,可以先送一個溫和版本過審、核准後再改成任意內容,而棋盤仍掛在
     * /community 對所有訪客放送 —— 等於繞過人工審核。以前只有 update()/updateSquare()
     * 有這個檢查,其餘七個 mutator(rules/canvas/path/bulk/store/destroy/preset)沒有,
     * 那正是破口。集中成一個 helper,新增 mutator 時照呼叫即可。
     */
    private function requeueIfPublished(Board $board): void
    {
        if ($board->isPublished()) {
            $board->update(['publish_status' => Board::PUBLISH_PENDING]);
        }
    }

    public function index()
    {
        $boards = Board::withCount('squares')
            ->where('user_id', Auth::id())
            ->latest()
            ->get();

        return view('boards.index', compact('boards'));
    }

    public function create()
    {
        return view('boards.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', new NoBlockedWords],
            'description' => ['nullable', 'string', 'max:500', new NoBlockedWords],
        ]);

        $board = Board::create(array_merge($data, [
            'canvas_rows' => 11,
            'canvas_cols' => 13,
            'user_id' => Auth::id(),
        ]));

        // Clone default squares
        $default = Board::where('is_default', true)->first();
        if ($default) {
            foreach ($default->squares as $sq) {
                BoardSquare::create([
                    'board_id' => $board->id,
                    'position' => $sq->position,
                    'text' => $sq->text,
                    'color' => $sq->color,
                    'fly_to' => $sq->fly_to,
                    'grid_row' => $sq->grid_row,
                    'grid_col' => $sq->grid_col,
                ]);
            }
            $board->update([
                'path_data' => $default->path_data,
                'start_wheel' => $default->start_wheel,
                'capture_enabled' => $default->capture_enabled,
            ]);
        } else {
            // Blank cross-shape (40 squares)
            $crossMap = [
                0 => [1, 6], 1 => [1, 7], 2 => [2, 7], 3 => [3, 7], 4 => [4, 7],
                5 => [5, 8], 6 => [5, 9], 7 => [5, 10], 8 => [5, 11], 9 => [5, 12], 10 => [5, 13],
                11 => [6, 13], 12 => [7, 13], 13 => [7, 12], 14 => [7, 11], 15 => [7, 10],
                16 => [7, 9], 17 => [7, 8], 18 => [8, 7], 19 => [9, 7], 20 => [10, 7], 21 => [11, 7],
                22 => [11, 6], 23 => [11, 5], 24 => [10, 5], 25 => [9, 5], 26 => [8, 5],
                27 => [7, 4], 28 => [7, 3], 29 => [7, 2], 30 => [7, 1], 31 => [6, 1], 32 => [5, 1],
                33 => [5, 2], 34 => [5, 3], 35 => [5, 4], 36 => [4, 5], 37 => [3, 5], 38 => [2, 5], 39 => [1, 5],
            ];
            foreach ($crossMap as $i => [$row, $col]) {
                BoardSquare::create([
                    'board_id' => $board->id,
                    'position' => $i,
                    'text' => '',
                    'color' => $i === 0 ? 'start' : ($i === 22 ? 'end' : 'normal'),
                    'grid_row' => $row,
                    'grid_col' => $col,
                ]);
            }
            $board->update(['path_data' => ['all' => range(0, 22), 'male' => null, 'female' => null]]);
        }

        return redirect()->route('boards.edit', $board)->with('success', __('play.flash_board_created'));
    }

    public function edit(Board $board)
    {
        $this->checkOwnership($board);
        $board->load('squares');
        $squares = $board->squaresArray();
        $pathData = $board->path_data ?? ['all' => null, 'male' => null, 'female' => null];

        return view('boards.edit', compact('board', 'squares', 'pathData'));
    }

    public function update(Request $request, Board $board)
    {
        $this->checkOwnership($board);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', new NoBlockedWords],
            'description' => ['nullable', 'string', 'max:500', new NoBlockedWords],
            'recommended_players' => ['sometimes', 'integer', 'in:2,4'],
        ]);

        // Content changed after approval → back to review queue
        if ($board->isPublished()) {
            $data['publish_status'] = Board::PUBLISH_PENDING;
        }

        $board->update($data);

        return response()->json(['success' => true]);
    }

    public function destroy(Board $board)
    {
        $this->checkOwnership($board);
        if ($board->is_default) {
            return back()->with('error', __('play.err_default_board_delete'));
        }
        $board->delete();

        return redirect()->route('boards.index')->with('success', __('play.flash_board_deleted'));
    }

    /* ── Individual square content update (text/color/fly_to) ── */
    public function updateSquare(Request $request, Board $board, int $position)
    {
        $this->checkOwnership($board);

        $validator = Validator::make($request->all(), [
            'text' => ['nullable', 'string', 'max:200', new NoBlockedWords],
            'color' => 'required|string|in:action,drink,dare,truth,strip,move,normal,start,end,male,female,p1,p2,p3,p4',
            'fly_to' => 'nullable|integer|min:0|max:999',
            // Signed: + forward, - backward. Replaces parsing 前進N格 out of the text.
            'move_steps' => 'nullable|integer|min:-20|max:20',
            'skip_turn' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            // Remap error keys: text → squares.{position}.text
            $errors = [];
            foreach ($validator->errors()->toArray() as $field => $messages) {
                $errors["squares.{$position}.{$field}"] = $messages;
            }

            return response()->json(['errors' => $errors], 422);
        }

        $data = $validator->validated();
        // Absent checkbox means "off", not "leave as-is".
        $data['skip_turn'] = (bool) ($data['skip_turn'] ?? false);

        BoardSquare::updateOrCreate(
            ['board_id' => $board->id, 'position' => $position],
            $data
        );

        // Square content changed after approval → back to review queue
        if ($board->isPublished()) {
            $board->update(['publish_status' => Board::PUBLISH_PENDING]);
        }

        return response()->json(['success' => true]);
    }

    /* ── Per-board rules: entry wheel + eviction toggle ── */
    public function updateRules(Request $request, Board $board)
    {
        $this->checkOwnership($board);

        $data = $request->validate([
            'capture_enabled' => 'required|boolean',
            'wheel_enabled' => 'required|boolean',
            'segments' => 'required_if:wheel_enabled,true|array|size:6',
            'segments.*.text' => ['nullable', 'string', 'max:60', new NoBlockedWords],
            'segments.*.enter' => 'required|boolean',
            'segments.*.reroll' => 'required|boolean',
        ]);

        // A wheel nobody can leave would soft-lock the game on the first turn.
        if ($data['wheel_enabled'] && ! collect($data['segments'])->contains(fn ($s) => $s['enter'])) {
            return response()->json([
                'errors' => ['segments' => [__('play.err_wheel_needs_enter')]],
            ], 422);
        }

        $board->update([
            'capture_enabled' => $data['capture_enabled'],
            'start_wheel' => $data['wheel_enabled']
                ? ['enabled' => true, 'segments' => $data['segments']]
                : null,
        ]);

        $this->requeueIfPublished($board);

        return response()->json(['success' => true]);
    }

    /* ── Canvas: update canvas size ── */
    public function updateCanvas(Request $request, Board $board)
    {
        $this->checkOwnership($board);
        $data = $request->validate([
            'canvas_rows' => 'required|integer|min:3|max:30',
            'canvas_cols' => 'required|integer|min:3|max:30',
        ]);
        $board->update($data);

        $this->requeueIfPublished($board);

        return response()->json(['success' => true]);
    }

    /* ── Path: save path_data ── */
    public function updatePath(Request $request, Board $board)
    {
        $this->checkOwnership($board);
        $data = $request->validate([
            'all' => 'required|array|min:2',
            'all.*' => 'integer|min:0',
            'male' => 'nullable|array',
            'male.*' => 'integer|min:0',
            'female' => 'nullable|array',
            'female.*' => 'integer|min:0',
        ]);

        // Verify positions belong to this board
        $validPositions = $board->squares()->pluck('position')->toArray();
        foreach ($data['all'] as $pos) {
            if (! in_array($pos, $validPositions)) {
                return response()->json(['success' => false, 'message' => __('play.err_position_missing', ['pos' => $pos])], 422);
            }
        }

        $board->update(['path_data' => [
            'all' => $data['all'],
            'male' => empty($data['male']) ? null : $data['male'],
            'female' => empty($data['female']) ? null : $data['female'],
        ]]);

        $this->requeueIfPublished($board);

        return response()->json(['success' => true]);
    }

    /* ── Layout: bulk update grid positions ── */
    public function bulkUpdateSquares(Request $request, Board $board)
    {
        $this->checkOwnership($board);
        $data = $request->validate([
            'squares' => 'required|array',
            'squares.*.position' => 'required|integer|min:0',
            'squares.*.grid_row' => 'required|integer|min:1|max:30',
            'squares.*.grid_col' => 'required|integer|min:1|max:30',
        ]);

        /* 兩格落在同一個座標的話,編輯器就再也選不到被蓋住的那一格
           (buildLayoutBoard 一個座標只畫一格),那一格既刪不掉也搬不動 ——
           只能進資料庫救。畫布外也一樣:超出 canvas_rows/cols 的格子根本不會被
           畫出來。所以先算出套用後的完整盤面,有撞格就整批退回,不做一半。 */
        $moves = collect($data['squares'])->keyBy('position');

        foreach ($moves as $move) {
            if ($move['grid_row'] > $board->canvas_rows || $move['grid_col'] > $board->canvas_cols) {
                return response()->json([
                    'success' => false,
                    'message' => __('play.err_cell_outside_canvas'),
                ], 422);
            }
        }

        $cells = $board->squares()->get(['position', 'grid_row', 'grid_col'])
            ->map(function ($sq) use ($moves) {
                $move = $moves->get($sq->position);

                return $move
                    ? $move['grid_row'].','.$move['grid_col']
                    : $sq->grid_row.','.$sq->grid_col;
            });

        if ($cells->count() !== $cells->unique()->count()) {
            return response()->json([
                'success' => false,
                'message' => __('play.err_cell_occupied'),
            ], 422);
        }

        /* 交換兩格是兩次 update。中間失敗的話兩格會停在同一個座標,也就是上面那個
           救不回來的狀態 —— 所以要嘛兩次都成,要嘛都不動。 */
        DB::transaction(function () use ($data, $board) {
            foreach ($data['squares'] as $sq) {
                BoardSquare::where('board_id', $board->id)
                    ->where('position', $sq['position'])
                    ->update(['grid_row' => $sq['grid_row'], 'grid_col' => $sq['grid_col']]);
            }
        });

        $this->requeueIfPublished($board);

        return response()->json(['success' => true]);
    }

    /* ── Layout: create a new square at a grid position ── */
    public function storeSquare(Request $request, Board $board)
    {
        $this->checkOwnership($board);
        $data = $request->validate([
            'grid_row' => 'required|integer|min:1|max:30',
            'grid_col' => 'required|integer|min:1|max:30',
        ]);

        // Check cell not already occupied
        if ($board->squares()->where('grid_row', $data['grid_row'])->where('grid_col', $data['grid_col'])->exists()) {
            return response()->json(['success' => false, 'message' => __('play.err_cell_occupied')], 422);
        }

        $nextPos = ($board->squares()->max('position') ?? -1) + 1;

        $sq = BoardSquare::create([
            'board_id' => $board->id,
            'position' => $nextPos,
            'text' => '',
            'color' => 'normal',
            'grid_row' => $data['grid_row'],
            'grid_col' => $data['grid_col'],
        ]);

        $this->requeueIfPublished($board);

        return response()->json(['success' => true, 'position' => $nextPos, 'square' => [
            'text' => '',
            'color' => 'normal',
            'fly_to' => null,
            'grid_row' => $sq->grid_row,
            'grid_col' => $sq->grid_col,
        ]]);
    }

    /* ── Layout: delete a square ── */
    public function destroySquare(Board $board, int $position)
    {
        $this->checkOwnership($board);
        $sq = BoardSquare::where('board_id', $board->id)->where('position', $position)->first();
        if (! $sq) {
            return response()->json(['success' => false, 'message' => __('play.err_square_missing')], 404);
        }
        $sq->delete();

        // Remove from path_data if present
        $pd = $board->path_data ?? [];
        foreach (['all', 'male', 'female'] as $group) {
            if (! empty($pd[$group])) {
                $pd[$group] = array_values(array_filter($pd[$group], fn ($p) => $p !== $position));
                if (empty($pd[$group])) {
                    $pd[$group] = null;
                }
            }
        }
        $board->update(['path_data' => $pd]);

        $this->requeueIfPublished($board);

        return response()->json(['success' => true]);
    }

    /* ── Apply preset (clears all squares, creates preset layout) ── */
    public function applyPreset(Request $request, Board $board)
    {
        $this->checkOwnership($board);
        $data = $request->validate([
            'preset' => ['required', 'string', Rule::in(BoardShapes::KEYS)],
        ]);

        $board->squares()->delete();

        /* 版型的幾何在 App\Support\BoardShapes ——「哪些格子、走哪個順序」跟
           「誰能改這張棋盤」是兩件事,混在同一個方法裡的話,加一個形狀就得動
           controller。那邊也寫著兩條不能違反的規則(只能正交相鄰、不能重複)。 */
        $shape = BoardShapes::make($data['preset']);

        foreach ($shape['cells'] as $pos => $cell) {
            BoardSquare::create([
                'board_id' => $board->id,
                'position' => $pos,
                'text' => $cell['text'],
                'color' => $cell['color'],
                'grid_row' => $cell['row'],
                'grid_col' => $cell['col'],
            ]);
        }

        /* 路徑由版型自己決定。多數版型就是全部格子,但十字鷹架的路徑只走前 23 格
           (第 22 格就是終點,後面是裝飾用的另外兩條臂)—— 見 BoardShapes::make()。 */
        $board->update([
            'canvas_rows' => $shape['rows'],
            'canvas_cols' => $shape['cols'],
            'path_data' => ['all' => $shape['path'], 'male' => null, 'female' => null],
        ]);

        $this->requeueIfPublished($board);

        $board->load('squares');

        return response()->json(['success' => true, 'squares' => $board->squaresArray(), 'canvas_rows' => $board->canvas_rows, 'canvas_cols' => $board->canvas_cols, 'path_data' => $board->path_data]);
    }

    /* ── Publishing to the community list ── */

    public function publish(Board $board)
    {
        $this->checkOwnership($board);

        if ($board->squares()->count() < 2 || empty($board->path_data['all'] ?? null)) {
            return back()->with('error', __('play.publish_incomplete'));
        }

        if (in_array($board->publish_status, [Board::PUBLISH_PENDING, Board::PUBLISH_APPROVED], true)) {
            return back()->with('error', __('play.publish_already'));
        }

        $board->update([
            'publish_status' => Board::PUBLISH_PENDING,
            'publish_note' => null,
        ]);

        return back()->with('success', __('play.publish_submitted'));
    }

    public function unpublish(Board $board)
    {
        $this->checkOwnership($board);

        $board->update([
            'publish_status' => null,
            'published_at' => null,
            'publish_note' => null,
        ]);

        return back()->with('success', __('play.publish_withdrawn'));
    }

    /** Public community list of user-published, admin-approved boards. */
    public function community()
    {
        $boards = Board::withCount('squares')
            ->with('user:id,name')
            ->published()
            // Defense-in-depth: publish() already requires >=2 squares, but a
            // board approved through other paths must never list unplayable.
            ->has('squares', '>=', 2)
            ->orderByDesc('published_at')
            ->paginate(12);

        return view('boards.community', compact('boards'));
    }

    /* ── Templates ── */

    public function templates()
    {
        $templates = Board::withCount('squares')
            ->where('is_template', true)
            ->orderBy('is_premium_template')
            ->latest()
            ->get();

        return view('boards.templates', compact('templates'));
    }

    public function templatePreview(Request $request, Board $board)
    {
        if (! $board->is_template) {
            abort(404);
        }

        $board->load('squares');

        // 付費範本沒有權限時只開一部分。遮住的格子**根本不會送出文字**,
        // 不是用 CSS 糊掉 —— 糊的那種打開開發者工具就看完了,等於沒鎖。
        $canSeeAll = ! $board->is_premium_template
            || PremiumAccess::content($request->user());

        return view('boards.template-preview', [
            'board' => $board,
            'canSeeAll' => $canSeeAll,
            'openPositions' => $canSeeAll ? [] : $board->previewOpenPositions(),
            'previewOpenSquares' => Board::PREVIEW_OPEN_SQUARES,
        ]);
    }

    public function cloneTemplate(Request $request, Board $board)
    {
        if (! $board->is_template) {
            abort(404);
        }

        if ($board->is_premium_template) {
            /* keepsakes() 而不是 isPremium():「存一份到收藏」是留得住的東西,
               有金流的時候只認會員資格;沒有金流的期間(現況)看廣告也算 ——
               否則這個功能對所有人都是永久鎖著的。見 PremiumAccess::keepsakes()。 */
            $user = $request->user();
            if (! PremiumAccess::keepsakes($user)) {
                return redirect()->route('premium.index')
                    ->with('error', __('play.err_premium_template_clone'));
            }
        }

        // Clone the board
        $newBoard = Board::create([
            'name' => __('play.clone_name', ['name' => $board->name]),
            'description' => $board->description,
            'user_id' => Auth::id(),
            'canvas_rows' => $board->canvas_rows,
            'canvas_cols' => $board->canvas_cols,
            'path_data' => $board->path_data,
            'start_wheel' => $board->start_wheel,
            'capture_enabled' => $board->capture_enabled,
            'recommended_players' => $board->recommended_players,
        ]);

        foreach ($board->squares as $sq) {
            BoardSquare::create([
                'board_id' => $newBoard->id,
                'position' => $sq->position,
                'text' => $sq->text,
                'color' => $sq->color,
                'fly_to' => $sq->fly_to,
                'grid_row' => $sq->grid_row,
                'grid_col' => $sq->grid_col,
            ]);
        }

        return redirect()->route('boards.edit', $newBoard)
            ->with('success', __('play.flash_template_cloned'));
    }
}
