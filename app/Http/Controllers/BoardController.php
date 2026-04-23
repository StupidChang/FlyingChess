<?php

namespace App\Http\Controllers;

use App\Models\Board;
use App\Models\BoardSquare;
use App\Rules\NoBlockedWords;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BoardController extends Controller
{
    private function checkOwnership(Board $board): void
    {
        if ($board->user_id !== Auth::id()) {
            abort(403, '沒有權限操作此棋盤');
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
            'name'        => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
        ]);

        $board = Board::create(array_merge($data, [
            'canvas_rows' => 11,
            'canvas_cols' => 13,
            'user_id'     => Auth::id(),
        ]));

        // Clone default squares
        $default = Board::where('is_default', true)->first();
        if ($default) {
            foreach ($default->squares as $sq) {
                BoardSquare::create([
                    'board_id' => $board->id,
                    'position' => $sq->position,
                    'text'     => $sq->text,
                    'color'    => $sq->color,
                    'fly_to'   => $sq->fly_to,
                    'grid_row' => $sq->grid_row,
                    'grid_col' => $sq->grid_col,
                ]);
            }
            $board->update(['path_data' => $default->path_data]);
        } else {
            // Blank cross-shape (40 squares)
            $crossMap = [
                0=>[1,6],1=>[1,7],2=>[2,7],3=>[3,7],4=>[4,7],
                5=>[5,8],6=>[5,9],7=>[5,10],8=>[5,11],9=>[5,12],10=>[5,13],
                11=>[6,13],12=>[7,13],13=>[7,12],14=>[7,11],15=>[7,10],
                16=>[7,9],17=>[7,8],18=>[8,7],19=>[9,7],20=>[10,7],21=>[11,7],
                22=>[11,6],23=>[11,5],24=>[10,5],25=>[9,5],26=>[8,5],
                27=>[7,4],28=>[7,3],29=>[7,2],30=>[7,1],31=>[6,1],32=>[5,1],
                33=>[5,2],34=>[5,3],35=>[5,4],36=>[4,5],37=>[3,5],38=>[2,5],39=>[1,5],
            ];
            foreach ($crossMap as $i => [$row, $col]) {
                BoardSquare::create([
                    'board_id' => $board->id,
                    'position' => $i,
                    'text'     => '',
                    'color'    => $i === 0 ? 'start' : ($i === 22 ? 'end' : 'normal'),
                    'grid_row' => $row,
                    'grid_col' => $col,
                ]);
            }
            $board->update(['path_data' => ['all' => range(0, 22), 'male' => null, 'female' => null]]);
        }

        return redirect()->route('boards.edit', $board)->with('success', '棋盤已建立，請開始編輯！');
    }

    public function edit(Board $board)
    {
        $this->checkOwnership($board);
        $board->load('squares');
        $squares  = $board->squaresArray();
        $pathData = $board->path_data ?? ['all' => null, 'male' => null, 'female' => null];
        return view('boards.edit', compact('board', 'squares', 'pathData'));
    }

    public function update(Request $request, Board $board)
    {
        $this->checkOwnership($board);
        $data = $request->validate([
            'name'        => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
        ]);
        $board->update($data);
        return response()->json(['success' => true]);
    }

    public function destroy(Board $board)
    {
        $this->checkOwnership($board);
        if ($board->is_default) {
            return back()->with('error', '預設棋盤無法刪除');
        }
        $board->delete();
        return redirect()->route('boards.index')->with('success', '棋盤已刪除');
    }

    /* ── Individual square content update (text/color/fly_to) ── */
    public function updateSquare(Request $request, Board $board, int $position)
    {
        $this->checkOwnership($board);

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'text'   => ['nullable', 'string', 'max:200', new NoBlockedWords],
            'color'  => 'required|string|in:action,drink,dare,truth,strip,move,normal,start,end,male,female',
            'fly_to' => 'nullable|integer|min:0|max:999',
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

        BoardSquare::updateOrCreate(
            ['board_id' => $board->id, 'position' => $position],
            $data
        );

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
        return response()->json(['success' => true]);
    }

    /* ── Path: save path_data ── */
    public function updatePath(Request $request, Board $board)
    {
        $this->checkOwnership($board);
        $data = $request->validate([
            'all'      => 'required|array|min:2',
            'all.*'    => 'integer|min:0',
            'male'     => 'nullable|array',
            'male.*'   => 'integer|min:0',
            'female'   => 'nullable|array',
            'female.*' => 'integer|min:0',
        ]);

        // Verify positions belong to this board
        $validPositions = $board->squares()->pluck('position')->toArray();
        foreach ($data['all'] as $pos) {
            if (!in_array($pos, $validPositions)) {
                return response()->json(['success' => false, 'message' => "位置 {$pos} 不存在"], 422);
            }
        }

        $board->update(['path_data' => [
            'all'    => $data['all'],
            'male'   => empty($data['male'])   ? null : $data['male'],
            'female' => empty($data['female']) ? null : $data['female'],
        ]]);
        return response()->json(['success' => true]);
    }

    /* ── Layout: bulk update grid positions ── */
    public function bulkUpdateSquares(Request $request, Board $board)
    {
        $this->checkOwnership($board);
        $data = $request->validate([
            'squares'             => 'required|array',
            'squares.*.position'  => 'required|integer|min:0',
            'squares.*.grid_row'  => 'required|integer|min:1|max:30',
            'squares.*.grid_col'  => 'required|integer|min:1|max:30',
        ]);

        foreach ($data['squares'] as $sq) {
            BoardSquare::where('board_id', $board->id)
                ->where('position', $sq['position'])
                ->update(['grid_row' => $sq['grid_row'], 'grid_col' => $sq['grid_col']]);
        }
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
            return response()->json(['success' => false, 'message' => '此格已有格子'], 422);
        }

        $nextPos = ($board->squares()->max('position') ?? -1) + 1;

        $sq = BoardSquare::create([
            'board_id' => $board->id,
            'position' => $nextPos,
            'text'     => '',
            'color'    => 'normal',
            'grid_row' => $data['grid_row'],
            'grid_col' => $data['grid_col'],
        ]);

        return response()->json(['success' => true, 'position' => $nextPos, 'square' => [
            'text'     => '',
            'color'    => 'normal',
            'fly_to'   => null,
            'grid_row' => $sq->grid_row,
            'grid_col' => $sq->grid_col,
        ]]);
    }

    /* ── Layout: delete a square ── */
    public function destroySquare(Board $board, int $position)
    {
        $this->checkOwnership($board);
        $sq = BoardSquare::where('board_id', $board->id)->where('position', $position)->first();
        if (!$sq) {
            return response()->json(['success' => false, 'message' => '格子不存在'], 404);
        }
        $sq->delete();

        // Remove from path_data if present
        $pd = $board->path_data ?? [];
        foreach (['all', 'male', 'female'] as $group) {
            if (!empty($pd[$group])) {
                $pd[$group] = array_values(array_filter($pd[$group], fn($p) => $p !== $position));
                if (empty($pd[$group])) $pd[$group] = null;
            }
        }
        $board->update(['path_data' => $pd]);

        return response()->json(['success' => true]);
    }

    /* ── Apply preset (clears all squares, creates preset layout) ── */
    public function applyPreset(Request $request, Board $board)
    {
        $this->checkOwnership($board);
        $data = $request->validate([
            'preset' => 'required|string|in:cross,square',
        ]);

        $board->squares()->delete();

        if ($data['preset'] === 'cross') {
            $map = [
                0=>[1,6,'start','起點\n擲骰子出發！'],1=>[1,7,'move','前進2格'],
                2=>[2,7,'drink','喝一口'],3=>[3,7,'action','舔對方耳根10秒'],
                4=>[4,7,'move','後退2格\n並脫一件衣物'],5=>[5,8,'dare','大冒險！\n由對方出題'],
                6=>[5,9,'strip','為對方口交\n至流水或堅挺10秒'],7=>[5,10,'truth','真心話\n說出最近的秘密幻想'],
                8=>[5,11,'drink','用嘴餵對方\n喝一口酒'],9=>[5,12,'action','咬吸對方脖子\n種一顆草莓'],
                10=>[5,13,'move','下一輪休息\n跳過下次擲骰'],11=>[6,13,'action','與對方舌吻\n整整1分鐘'],
                12=>[7,13,'dare','大冒險！'],13=>[7,12,'female','♀ 女生拍一張性感照片'],
                14=>[7,11,'action','手伸對方內褲裡\n隨意發揮30秒'],15=>[7,10,'truth','真心話\n說出最想讓對方做的事'],
                16=>[7,9,'drink','喝半杯'],17=>[7,8,'male','♂ 男生停留此格\n後插對方1分鐘'],
                18=>[8,7,'action','為對方擋管或\n指逼1分鐘'],19=>[9,7,'strip','選一個姿勢\n讓對方插至少10下'],
                20=>[10,7,'action','對方口交\n1分鐘'],21=>[11,7,'dare','打對方屁股\n3下'],
                22=>[11,6,'end','終點\n恭喜！為愛鼓掌！'],23=>[11,5,'move','後退3格\n並脫一件衣物'],
                24=>[10,5,'strip','露出私處\n允許對方拍照一張'],25=>[9,5,'action','從背後抱住\n隨意撫摸1分鐘'],
                26=>[8,5,'action','舔對方大腿內側\n對方若笑則罰喝半杯'],27=>[7,4,'action','對方乳交\n1分鐘'],
                28=>[7,3,'female','♀ 女生坐在\n男生臉上摩擦'],29=>[7,2,'drink','喝一口'],
                30=>[7,1,'action','和對方用觀音坐蓮\n自己動至少10下'],31=>[6,1,'dare','大冒險！\n由對方出題'],
                32=>[5,1,'action','為對方口交\n3分鐘'],33=>[5,2,'truth','真心話\n說出最喜歡的體位'],
                34=>[5,3,'action','讓對方從耳根\n舔到胸口'],35=>[5,4,'action','手伸對方內褲裡\n隨意發揮30秒'],
                36=>[4,5,'drink','喝半杯'],37=>[3,5,'move','前進2格'],
                38=>[2,5,'strip','自己脫一件衣物'],39=>[1,5,'dare','嚼對方口水\n喝下'],
            ];
            foreach ($map as $pos => [$row, $col, $color, $text]) {
                BoardSquare::create(['board_id'=>$board->id,'position'=>$pos,'text'=>str_replace('\n',"\n",$text),'color'=>$color,'grid_row'=>$row,'grid_col'=>$col]);
            }
            $board->update(['canvas_rows'=>11,'canvas_cols'=>13,'path_data'=>['all'=>range(0,22),'male'=>null,'female'=>null]]);
        } else { // square ring
            $positions = [];
            $i = 0;
            for ($c=1;$c<=11;$c++) $positions[$i++]=[1,$c];      // top row
            for ($r=2;$r<=10;$r++) $positions[$i++]=[$r,11];     // right col
            for ($c=11;$c>=1;$c--) $positions[$i++]=[11,$c];     // bottom row
            for ($r=10;$r>=2;$r--) $positions[$i++]=[$r,1];      // left col
            foreach ($positions as $pos => [$row,$col]) {
                BoardSquare::create(['board_id'=>$board->id,'position'=>$pos,'text'=>'','color'=>$pos===0?'start':($pos===20?'end':'normal'),'grid_row'=>$row,'grid_col'=>$col]);
            }
            $board->update(['canvas_rows'=>11,'canvas_cols'=>11,'path_data'=>['all'=>range(0,20),'male'=>null,'female'=>null]]);
        }

        $board->load('squares');
        return response()->json(['success'=>true,'squares'=>$board->squaresArray(),'canvas_rows'=>$board->canvas_rows,'canvas_cols'=>$board->canvas_cols,'path_data'=>$board->path_data]);
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

    public function templatePreview(Board $board)
    {
        if (!$board->is_template) {
            abort(404);
        }

        $board->load('squares');

        return view('boards.template-preview', compact('board'));
    }

    public function cloneTemplate(Request $request, Board $board)
    {
        if (!$board->is_template) {
            abort(404);
        }

        if ($board->is_premium_template) {
            $user = $request->user();
            if (!$user || !$user->isPremium()) {
                return redirect()->route('premium.index')
                    ->with('error', '此模板僅限付費會員使用，請先升級。');
            }
        }

        // Clone the board
        $newBoard = Board::create([
            'name' => $board->name . ' (副本)',
            'description' => $board->description,
            'user_id' => Auth::id(),
            'canvas_rows' => $board->canvas_rows,
            'canvas_cols' => $board->canvas_cols,
            'path_data' => $board->path_data,
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
            ->with('success', '模板已複製到你的棋盤！');
    }
}
