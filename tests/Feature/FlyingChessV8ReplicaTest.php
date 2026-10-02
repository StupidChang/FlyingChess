<?php

namespace Tests\Feature;

use App\Http\Middleware\AgeVerification;
use App\Models\Board;
use App\Models\User;
use Database\Seeders\BoardTemplateSeeder;
use Database\Seeders\FlyingChessV8ReplicaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 《情侶飛行棋 V8.0》的逐格復刻盤面。
 *
 * 這一張和其他範本不同:它的每一格都是照原圖轉錄的,所以**格數與座標本身就是
 * 規格**。少一格、座標錯一格都不會報錯,只會變成一張長得不像原圖的棋盤。
 */
class FlyingChessV8ReplicaTest extends TestCase
{
    use RefreshDatabase;

    private function board(): Board
    {
        $this->seed(FlyingChessV8ReplicaSeeder::class);

        return Board::with('squares')->where('name', FlyingChessV8ReplicaSeeder::BOARD_NAME)->firstOrFail();
    }

    public function test_the_board_has_every_square_from_the_original(): void
    {
        $board = $this->board();

        // 外圈 48 + 四條家門各 5 + 中央終點 = 69
        $this->assertCount(69, $board->squares);
        $this->assertSame(13, $board->canvas_rows);
        $this->assertSame(13, $board->canvas_cols);

        $seen = [];
        foreach ($board->squares as $square) {
            $coord = $square->grid_row.','.$square->grid_col;
            if (isset($seen[$coord])) {
                $this->fail("第 {$square->position} 格與第 {$seen[$coord]} 格疊在一起 ({$coord})");
            }
            $seen[$coord] = $square->position;

            $this->assertGreaterThanOrEqual(1, $square->grid_row);
            $this->assertLessThanOrEqual(13, $square->grid_row);
            $this->assertGreaterThanOrEqual(1, $square->grid_col);
            $this->assertLessThanOrEqual(13, $square->grid_col);
            $this->assertNotSame('', trim((string) $square->text), "第 {$square->position} 格沒有文字");
        }
    }

    public function test_the_path_is_the_blue_route_and_never_steps_diagonally(): void
    {
        /* 路徑取藍色玩家的路線:繞外圈一圈(48)→ 藍色家門(5)→ 終點(1)。
           斜跨的那一步不會有箭頭(board.js 的 computeArrowMap 只認上下左右),
           玩的人會看不出下一格在哪 —— 這條就是在守它。 */
        $board = $this->board();
        $path = $board->path_data['all'];

        $this->assertCount(54, $path);
        $this->assertSame(range(0, 53), $path);

        $byPos = $board->squares->keyBy('position');
        for ($i = 1; $i < count($path); $i++) {
            $prev = $byPos[$path[$i - 1]];
            $cur = $byPos[$path[$i]];
            $distance = abs($prev->grid_row - $cur->grid_row) + abs($prev->grid_col - $cur->grid_col);

            $this->assertSame(1, $distance, "路徑第 {$i} 步是斜的:({$prev->grid_row},{$prev->grid_col}) → ({$cur->grid_row},{$cur->grid_col})");
        }

        // 起點在藍色閘門的下一格,終點是中央
        $this->assertSame('start', $byPos[$path[0]]->color);
        $end = $byPos[$path[count($path) - 1]];
        $this->assertSame('end', $end->color);
        $this->assertSame([7, 7], [$end->grid_row, $end->grid_col]);
    }

    public function test_the_shared_path_is_blue_and_leaves_the_other_three_lanes_to_their_seats(): void
    {
        /* path_data.all 是藍色那一條(路線編輯器與沒有座位概念的地方用),所以另外三條家門
           不在 all 上 —— 它們在各自座位的路線上,見 test_every_square_is_on_some_seats_route。 */
        $board = $this->board();
        $path = $board->path_data['all'];
        $byPos = $board->squares->keyBy('position');

        $offPath = $board->squares->reject(fn ($s) => in_array($s->position, $path, true));
        $this->assertCount(15, $offPath, '不在路徑上的應該剛好是另外三條家門');

        // 藍色家門的第一格必須在路徑上,不然這張盤走不到終點
        $blueLaneMouth = $board->squares->first(fn ($s) => $s->grid_row === 7 && $s->grid_col === 2);
        $this->assertContains($blueLaneMouth->position, $path);
    }

    public function test_the_four_shoulder_squares_fly_to_the_opposite_corner(): void
    {
        /* 原圖四個肩角都寫著「可飛躍對面」,而且是對角互飛。單向或指到不在路徑上
           的格子都會讓那個機制默默失效。 */
        $board = $this->board();
        $path = $board->path_data['all'];
        $byPos = $board->squares->keyBy('position');

        $flyers = $board->squares->whereNotNull('fly_to');
        $this->assertCount(4, $flyers);

        foreach ($flyers as $square) {
            $target = $byPos[$square->fly_to];

            $this->assertContains($square->position, $path, '飛躍格本身要在路徑上');
            $this->assertContains($target->position, $path, '飛躍的目標要在路徑上');
            // 互飛:對面那一格要飛回來
            $this->assertSame($square->position, $target->fly_to, '飛躍不是對稱的');
            // 對角:兩格分別在盤面的相對象限
            $this->assertSame(14, $square->grid_row + $target->grid_row);
            $this->assertSame(14, $square->grid_col + $target->grid_col);
        }
    }

    public function test_the_corner_wheel_matches_the_original(): void
    {
        // 原圖四個角落的 1–6 轉盤:2 與 5 是再擲一次,6 是進入棋盤
        $wheel = $this->board()->startWheel();

        // startWheel() 回的就是 6 段的清單本身,不是 ['segments' => …]
        $this->assertNotNull($wheel);
        $this->assertCount(6, $wheel);
        $this->assertCount(2, array_filter($wheel, fn ($s) => $s['reroll'] ?? false));
        $this->assertCount(1, array_filter($wheel, fn ($s) => $s['enter'] ?? false));
        $this->assertSame('進入棋盤', $wheel[5]['text']);
    }

    public function test_the_reference_image_actually_exists(): void
    {
        /* 參考圖是後台與範本預覽會載的圖。public/images/board-references/ 這個
           目錄之前根本不存在,所以站上兩張範本的參考圖是壞連結 —— 沒有人會回報
           一張看不到的圖。 */
        $board = $this->board();

        $this->assertNotNull($board->reference_image);
        $this->assertFileExists(public_path($board->reference_image));
    }

    public function test_the_template_seeder_does_not_delete_it(): void
    {
        /* BoardTemplateSeeder 會刪掉白名單外的所有系統範本。這張盤面是另一支
           seeder 建的,漏加白名單的話,跑完 db:seed 它就消失了,而且沒有任何錯誤。 */
        $this->board();

        $this->seed(BoardTemplateSeeder::class);

        $this->assertDatabaseHas('boards', [
            'name' => FlyingChessV8ReplicaSeeder::BOARD_NAME,
            'is_template' => true,
        ]);
    }

    public function test_it_renders_for_someone_who_can_see_it(): void
    {
        $board = $this->board();
        $this->assertTrue((bool) $board->is_premium_template, '內容有口交與抽插,應該是付費範本');

        $premium = User::factory()->create([
            'email_verified_at' => now(),
            'premium_expires_at' => now()->addMonth(),
        ]);

        $html = $this->actingAs($premium)
            ->withoutMiddleware(AgeVerification::class)
            ->get("/tw/play/{$board->id}")
            ->assertOk()
            ->getContent();

        /* 格子是丟給 JS 的(window.SQUARES_DATA),中文在 @json 裡會被轉成 \uXXXX,
           所以不能直接 assertSee 中文 —— 要把那包 JSON 解回來看。 */
        $this->assertMatchesRegularExpression('/window\.SQUARES_DATA\s*=/', $html);
        preg_match('/window\.SQUARES_DATA\s*=\s*(\[.*?\]);/s', $html, $m);
        $squares = json_decode($m[1] ?? '[]', true);

        $this->assertCount(69, $squares, '送到前端的格子數不對');
        $texts = array_map(fn ($s) => str_replace("\n", '', (string) ($s['text'] ?? '')), $squares);
        $this->assertContains('綠色玩家停留此格下回合可進入', $texts);
        $this->assertContains('喝一杯並脫光衣服', $texts);
    }

    public function test_every_square_is_on_some_seats_route(): void
    {
        /* 原本只有一條路線(藍色),另外三條家門的 15 格畫在盤面上卻永遠走不到。
           現在四個座位各走一條:四條合起來要涵蓋每一格。 */
        $this->seed(FlyingChessV8ReplicaSeeder::class);
        $board = Board::where('name', FlyingChessV8ReplicaSeeder::BOARD_NAME)->firstOrFail();
        $seats = $board->path_data['seats'];
        $cells = $board->squares->keyBy('position');

        $this->assertCount(4, $seats);
        $covered = array_unique(array_merge(...$seats));
        $this->assertEqualsCanonicalizing($cells->keys()->all(), $covered);

        foreach ($seats as $i => $path) {
            $this->assertCount(54, $path, '外圈 48 + 家門 5 + 終點 1');
            $this->assertSame($cells->max('position') >= 0 ? $path[53] : null, $board->path_data['all'][53], '終點是同一格');
            // 每一步都要上下左右相鄰,斜跨的那一步不會有箭頭
            for ($k = 0; $k < count($path) - 1; $k++) {
                $a = $cells[$path[$k]];
                $b = $cells[$path[$k + 1]];
                $this->assertSame(1, abs($a->grid_row - $b->grid_row) + abs($a->grid_col - $b->grid_col),
                    "座位 {$i} 的 {$path[$k]} → {$path[$k + 1]} 不相鄰");
            }
        }
        // 藍色(第 2 位)就是原本的那一條
        $this->assertSame($board->path_data['all'], $seats[1]);
    }
}
