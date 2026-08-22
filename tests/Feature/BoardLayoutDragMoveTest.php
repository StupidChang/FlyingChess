<?php

namespace Tests\Feature;

use App\Http\Middleware\AgeVerification;
use App\Models\Board;
use App\Models\BoardSquare;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 版面編輯器的「拖曳搬移格子」。
 *
 * 這個批次端點在 routes 裡放了很久卻沒有任何呼叫端 —— 想換位置只能刪掉再重新加
 * 一格,而那會換掉 position 編號、清空文字,還要重排路徑。所以這裡要守兩件事:
 * 搬移本身要對,以及**不能搬出救不回來的盤面** —— 兩格疊在同一個座標或跑到畫布
 * 外的格子,編輯器都不會把它畫出來,也就再也刪不掉、搬不動。
 */
class BoardLayoutDragMoveTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Board $board;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(AgeVerification::class);

        $this->owner = User::factory()->create(['email_verified_at' => now()]);
        $this->board = Board::create([
            'name' => '拖曳測試盤',
            'user_id' => $this->owner->id,
            'canvas_rows' => 5,
            'canvas_cols' => 5,
        ]);

        foreach ([[0, 1, 1], [1, 1, 2], [2, 2, 2]] as [$pos, $row, $col]) {
            BoardSquare::create([
                'board_id' => $this->board->id,
                'position' => $pos,
                'text' => "第 {$pos} 格",
                'color' => 'normal',
                'grid_row' => $row,
                'grid_col' => $col,
            ]);
        }
    }

    private function bulk(array $squares)
    {
        return $this->actingAs($this->owner)
            ->patchJson("/tw/boards/{$this->board->id}/squares", ['squares' => $squares]);
    }

    private function cellOf(int $position): array
    {
        $sq = BoardSquare::where('board_id', $this->board->id)->where('position', $position)->first();

        return [$sq->grid_row, $sq->grid_col];
    }

    public function test_a_square_moves_to_an_empty_cell(): void
    {
        $this->bulk([['position' => 0, 'grid_row' => 4, 'grid_col' => 5]])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSame([4, 5], $this->cellOf(0));
        // 搬移不動 position,所以文字與編號都還在(刪掉重加就是在這裡出事的)
        $this->assertSame('第 0 格', BoardSquare::where('board_id', $this->board->id)
            ->where('position', 0)->value('text'));
    }

    public function test_dropping_onto_an_occupied_cell_swaps_the_two(): void
    {
        // 0 在 (1,1)、1 在 (1,2):互換就是兩筆一起送
        $this->bulk([
            ['position' => 0, 'grid_row' => 1, 'grid_col' => 2],
            ['position' => 1, 'grid_row' => 1, 'grid_col' => 1],
        ])->assertOk();

        $this->assertSame([1, 2], $this->cellOf(0));
        $this->assertSame([1, 1], $this->cellOf(1));
    }

    public function test_a_move_that_would_stack_two_squares_is_rejected(): void
    {
        /* 只送一筆、目標卻已經有人 —— 前端的互換少送了第二筆就會長這樣。
           放過去的話 (1,2) 上會有兩格,而編輯器一個座標只畫一格,被蓋住的那一格
           再也點不到。 */
        $this->bulk([['position' => 0, 'grid_row' => 1, 'grid_col' => 2]])
            ->assertStatus(422)
            ->assertJsonPath('success', false);

        // 整批退回,不做一半
        $this->assertSame([1, 1], $this->cellOf(0));
        $this->assertSame([1, 2], $this->cellOf(1));
    }

    public function test_a_move_outside_the_canvas_is_rejected(): void
    {
        // 畫布是 5×5,第 6 列根本不會被畫出來
        $this->bulk([['position' => 0, 'grid_row' => 1, 'grid_col' => 6]])
            ->assertStatus(422);

        $this->assertSame([1, 1], $this->cellOf(0));
    }

    public function test_a_swap_is_all_or_nothing(): void
    {
        /* 互換的第二筆若指向畫布外,第一筆也不能生效 —— 否則兩格會停在同一個
           座標,正是上面那個救不回來的狀態。 */
        $this->bulk([
            ['position' => 0, 'grid_row' => 1, 'grid_col' => 2],
            ['position' => 1, 'grid_row' => 9, 'grid_col' => 9],
        ])->assertStatus(422);

        $this->assertSame([1, 1], $this->cellOf(0));
        $this->assertSame([1, 2], $this->cellOf(1));
    }

    public function test_someone_elses_board_cannot_be_rearranged(): void
    {
        $other = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($other)
            ->patchJson("/tw/boards/{$this->board->id}/squares", [
                'squares' => [['position' => 0, 'grid_row' => 4, 'grid_col' => 4]],
            ])->assertForbidden();

        $this->assertSame([1, 1], $this->cellOf(0));
    }

    public function test_the_editor_page_ships_the_endpoint_and_the_ui_strings(): void
    {
        /* 端點對了但前端沒接上就等於沒做(這個端點本來就是這樣躺著的)。
           順便守住 PLAY_I18N:這一頁原本沒帶,tp() 找不到鍵就把鍵名當文字用,
           畫面上會出現「centerTitle」「saveFailed」這種字。 */
        $response = $this->actingAs($this->owner)
            ->get("/tw/boards/{$this->board->id}/edit")
            ->assertOk();

        $response->assertSee('squaresBulk', false);
        $response->assertSee('window.PLAY_I18N', false);
        $response->assertSee('dragMoveSq', false);
        /* @json() 預設會把中文轉成 \uXXXX,所以比對的是轉義後的字串。
           重點是「鍵名沒有被當成值用」—— 那是 PLAY_I18N 沒帶進來時的長相。 */
        $response->assertDontSee('"centerTitle":"centerTitle"', false);
        $response->assertSee(trim(json_encode(__('play.js_drag_move_sq')), '"'), false);
    }
}
