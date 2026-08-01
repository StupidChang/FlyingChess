<?php

use App\Models\Board;
use App\Models\BoardSquare;
use Illuminate\Database\Migrations\Migration;

/**
 * 補上十字棋盤缺的四個內轉角。
 *
 * 原本的十字外圈是 40 格,(5,7) (7,7) (7,5) (5,5) 這四個內轉角沒有格子 ——
 * 路徑在每個轉彎處是斜著跳過去的,棋盤上看起來就是缺一角。
 *
 * BoardSeeder 只在「這張棋盤一格都沒有」時才建立格子(為了不覆蓋管理員後來的
 * 編輯),所以既有棋盤補不到,得由這支 migration 動手。
 *
 * ⚠ 只處理**確定是那個 40 格十字**的棋盤:格數正好 40、而且每一格的座標都對得上
 * 舊的位置表。任何被編輯過、加過格子、或換過形狀的棋盤一律跳過 —— 猜錯棋盤形狀
 * 然後把格子插進別人的自訂棋盤,比留著一個缺角嚴重得多。
 */
return new class extends Migration
{
    /** 補完之後的 44 格外圈。 */
    private const NEW_POS = [
        [1, 6], [1, 7], [2, 7], [3, 7], [4, 7], [5, 7],
        [5, 8], [5, 9], [5, 10], [5, 11], [5, 12], [5, 13],
        [6, 13], [7, 13], [7, 12], [7, 11], [7, 10], [7, 9],
        [7, 8], [7, 7], [8, 7], [9, 7], [10, 7], [11, 7],
        [11, 6], [11, 5], [10, 5], [9, 5], [8, 5], [7, 5],
        [7, 4], [7, 3], [7, 2], [7, 1], [6, 1], [5, 1],
        [5, 2], [5, 3], [5, 4], [5, 5], [4, 5], [3, 5],
        [2, 5], [1, 5],
    ];

    /** 新表裡屬於轉角的索引。 */
    private const CORNER_AT = [5, 19, 29, 39];

    private const CORNER_SQUARES = [
        ["轉角\n停下來親對方 10 秒", 'action'],
        ["轉角\n幫對方脫掉一件外層衣物", 'strip'],
        ["轉角\n從背後抱住對方 30 秒", 'action'],
        ["轉角\n說出你現在最想被碰的地方", 'truth'],
    ];

    public function up(): void
    {
        foreach (Board::with('squares')->get() as $board) {
            if (! $this->isLegacyCross($board)) {
                continue;
            }

            /* 由後往前插入。插入點是**位移前**的索引:第 n 個轉角在最終陣列裡
               的位置是 CORNER_AT[n],但它前面還會被插進 n 個轉角,所以此刻
               要插在 CORNER_AT[n] - n。從後面開始做,前面的索引才不會被動到。 */
            foreach (array_reverse(self::CORNER_AT, true) as $slot => $at) {
                $insertAt = $at - $slot;

                /* 由大到小逐格往後推。順序反了會撞上 (board_id, position) 的
                   唯一索引 —— 而且不能走 $board->squares():那個關聯自帶
                   orderBy('position') 遞增,再加 orderByDesc 只會變成次要排序,
                   遞增仍然勝出。直接查 BoardSquare 才拿得到真正的遞減。 */
                BoardSquare::where('board_id', $board->id)
                    ->where('position', '>=', $insertAt)
                    ->orderByDesc('position')
                    ->get()
                    ->each(fn ($sq) => $sq->update(['position' => $sq->position + 1]));

                [$text, $color] = self::CORNER_SQUARES[$slot];
                BoardSquare::create([
                    'board_id' => $board->id,
                    'position' => $insertAt,
                    'text' => $text,
                    'color' => $color,
                    'grid_row' => self::NEW_POS[$at][0],
                    'grid_col' => self::NEW_POS[$at][1],
                ]);
            }

            // 座標整份重寫:插入之後每一格的 position 都對應到新表的同一個索引。
            $board->load('squares');
            foreach ($board->squares as $sq) {
                if (isset(self::NEW_POS[$sq->position])) {
                    $sq->update([
                        'grid_row' => self::NEW_POS[$sq->position][0],
                        'grid_col' => self::NEW_POS[$sq->position][1],
                    ]);
                }
            }

            // path_data 是走訪順序,格數變了就要跟著長。
            $path = $board->path_data ?? [];
            $path['all'] = range(0, count(self::NEW_POS) - 1);
            $board->update(['path_data' => $path]);
        }
    }

    /**
     * 是不是「原封不動的 40 格十字」。格數與每一格的座標都要對得上舊表,
     * 差一格就代表有人動過,那就不該由我們重排。
     */
    private function isLegacyCross(Board $board): bool
    {
        $old = self::NEW_POS;
        foreach (array_reverse(self::CORNER_AT) as $at) {
            array_splice($old, $at, 1);
        }

        if ($board->squares->count() !== count($old)) {
            return false;
        }

        foreach ($board->squares as $sq) {
            $want = $old[$sq->position] ?? null;
            if (! $want || $sq->grid_row !== $want[0] || $sq->grid_col !== $want[1]) {
                return false;
            }
        }

        return true;
    }

    public function down(): void
    {
        // 不還原:回退等於刪掉四格內容,而那四格在這之後可能已經被編輯過。
    }
};
