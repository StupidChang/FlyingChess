<?php

namespace Tests\Feature;

use App\Http\Middleware\AgeVerification;
use App\Models\Board;
use App\Models\BoardSquare;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 大廳卡片的快速預覽(games.board-preview)。
 *
 * 最要緊的是兩道牆:私人棋盤不能被換數字讀到;付費棋盤沒權限時只能看到固定那 8 格,
 * 其餘格子連文字都不能送出(用 CSS 藏起來等於沒鎖)。
 */
class BoardQuickPreviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(AgeVerification::class);
    }

    private function board(array $attrs, int $squares = 20): Board
    {
        $board = Board::create($attrs + ['name' => 'B'.uniqid()]);
        foreach (range(0, $squares - 1) as $p) {
            BoardSquare::create(['board_id' => $board->id, 'position' => $p, 'text' => "第{$p}格內容",
                'color' => $p === 0 ? 'start' : 'action', 'grid_row' => 1, 'grid_col' => $p + 1]);
        }

        return $board;
    }

    private function url(Board $board): string
    {
        return route('games.board-preview', ['locale' => 'tw', 'board' => $board]);
    }

    public function test_a_free_board_shows_every_square_in_path_order(): void
    {
        $board = $this->board(['is_template' => true, 'path_data' => ['all' => [0, 2, 1], 'male' => null, 'female' => null]], 3);

        $json = $this->getJson($this->url($board))->assertOk()->assertHeader('X-Robots-Tag', 'noindex')->json();

        $this->assertSame(['第0格內容', '第2格內容', '第1格內容'], array_column($json['squares'], 'text'));
        $this->assertFalse($json['locked']);
        $this->assertNotNull($json['play_url']);
    }

    public function test_a_premium_board_only_reveals_the_fixed_preview_squares(): void
    {
        $board = $this->board(['is_template' => true, 'is_premium_template' => true]);

        $json = $this->getJson($this->url($board))->assertOk()->json();
        $visible = array_filter($json['squares'], fn ($s) => $s['text'] !== null);

        $this->assertTrue($json['locked']);
        $this->assertCount(Board::PREVIEW_OPEN_SQUARES, $visible);
        $this->assertNull($json['play_url']);
        // 跟範本預覽頁開的是同一批,兩邊合起來也不會多看到
        $this->assertSame($board->fresh()->previewOpenPositions(), array_values(array_map(
            fn ($s) => (int) str_replace(['第', '格內容'], '', $s['text']), $visible)));
        // 被鎖的格子文字根本不在回應裡
        $raw = $this->getJson($this->url($board))->getContent();
        $hidden = array_diff(range(0, 19), $board->fresh()->previewOpenPositions());
        $this->assertStringNotContainsString('第'.reset($hidden).'格內容', $raw);
    }

    public function test_a_premium_member_sees_the_whole_premium_board(): void
    {
        $board = $this->board(['is_template' => true, 'is_premium_template' => true]);
        $member = User::factory()->create(['premium_expires_at' => now()->addMonth()]);

        $json = $this->actingAs($member)->getJson($this->url($board))->assertOk()->json();

        $this->assertFalse($json['locked']);
        $this->assertCount(20, array_filter(array_column($json['squares'], 'text')));
    }

    public function test_private_boards_cannot_be_read_by_guessing_ids(): void
    {
        $owner = User::factory()->create();
        $private = $this->board(['user_id' => $owner->id]);
        $pending = $this->board(['user_id' => $owner->id, 'publish_status' => Board::PUBLISH_PENDING]);
        $approved = $this->board(['user_id' => $owner->id, 'publish_status' => Board::PUBLISH_APPROVED, 'published_at' => now()]);

        $this->getJson($this->url($private))->assertNotFound();
        $this->getJson($this->url($pending))->assertNotFound();
        $this->getJson($this->url($approved))->assertOk();
    }

    public function test_every_lobby_card_has_a_quick_preview_button(): void
    {
        $this->board(['is_template' => true]);
        $this->board(['is_template' => true, 'is_premium_template' => true]);

        $html = $this->get('/tw/games')->assertOk()->getContent();

        $this->assertSame(2, substr_count($html, 'class="btn btn-sm btn-outline qp-open"'));
        $this->assertStringContainsString('id="qp-modal"', $html);
        $this->assertStringContainsString('js/sq-icons.js', $html);
    }
}
