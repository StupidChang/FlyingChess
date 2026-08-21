<?php

namespace Tests\Feature;

use App\Http\Middleware\AgeVerification;
use App\Models\Board;
use App\Models\User;
use App\Support\BoardShapes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 棋盤版型(編輯器的「套用預設」)。
 *
 * 版型壞掉的方式都很安靜:格子重疊(後畫的蓋掉前一格)、斜著相鄰(箭頭消失,
 * 玩的人看不出下一步)、路徑沒蓋滿(有格子永遠走不到)。三種都不會報錯,
 * 要玩到才發現,所以這一組是幾何檢查而不是畫面檢查。
 */
class BoardShapesTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_shape_is_a_walkable_path(): void
    {
        foreach (BoardShapes::KEYS as $key) {
            // 十字是舊的鷹架,它本來就有斜走的步(那張預設棋盤的箭頭因此有幾格是空的)。
            // 改它的座標等於改站上的預設棋盤長相,不在這一支的守備範圍 —— 見下面那條測試。
            if ($key === 'cross') {
                continue;
            }

            $shape = BoardShapes::make($key);
            $cells = $shape['cells'];

            $this->assertGreaterThanOrEqual(20, count($cells), "版型 {$key} 太短,不像一張棋盤");

            $seen = [];
            foreach ($cells as $i => $cell) {
                $coord = $cell['row'].','.$cell['col'];
                if (isset($seen[$coord])) {
                    // 訊息要在這裡組,不是丟給 assert 的第三個參數 —— 那樣會先讀
                    // 一個不存在的鍵,測試掛在警告上而不是掛在真正的問題上
                    $this->fail("版型 {$key} 的第 {$i} 格與第 {$seen[$coord]} 格重疊");
                }
                $seen[$coord] = $i;

                $this->assertGreaterThanOrEqual(1, $cell['row'], "版型 {$key} 第 {$i} 格出界");
                $this->assertLessThanOrEqual($shape['rows'], $cell['row'], "版型 {$key} 第 {$i} 格出界");
                $this->assertGreaterThanOrEqual(1, $cell['col'], "版型 {$key} 第 {$i} 格出界");
                $this->assertLessThanOrEqual($shape['cols'], $cell['col'], "版型 {$key} 第 {$i} 格出界");

                if ($i === 0) {
                    continue;
                }

                /* 只能上下左右相鄰。board.js 的 computeArrowMap() 只處理 dr===0 或
                   dc===0,斜走的那一格會靜靜地沒有箭頭 —— 這一條就是在守它。 */
                $prev = $cells[$i - 1];
                $distance = abs($prev['row'] - $cell['row']) + abs($prev['col'] - $cell['col']);
                $this->assertSame(1, $distance, "版型 {$key} 第 {$i} 格與前一格不是上下左右相鄰(斜的或跳開)");
            }
        }
    }

    public function test_every_shape_starts_at_start_and_ends_at_end(): void
    {
        foreach (BoardShapes::KEYS as $key) {
            $shape = BoardShapes::make($key);
            $path = $shape['path'];

            // 看的是**路徑**的頭尾,不是格子陣列的頭尾 —— 十字有 17 格不在路徑上
            $first = $shape['cells'][$path[0]];
            $last = $shape['cells'][$path[count($path) - 1]];

            $this->assertSame('start', $first['color'], "版型 {$key} 路徑的第一格不是起點");
            $this->assertSame('end', $last['color'], "版型 {$key} 路徑的最後一格不是終點");
        }
    }

    public function test_the_canvas_is_no_bigger_than_the_shape_needs(): void
    {
        /* 畫布比版型大的話,棋盤會偏在一角、旁邊留一片空白。之前六角環宣告 9 列
           但只用到 7 —— 純幾何上沒錯,畫面上就是歪的。 */
        foreach (BoardShapes::KEYS as $key) {
            $shape = BoardShapes::make($key);

            $this->assertSame(
                $shape['rows'],
                max(array_column($shape['cells'], 'row')),
                "版型 {$key} 宣告的列數比實際用到的多"
            );
            $this->assertSame(
                $shape['cols'],
                max(array_column($shape['cells'], 'col')),
                "版型 {$key} 宣告的欄數比實際用到的多"
            );
        }
    }

    public function test_dimensions_match_what_make_produces(): void
    {
        // 按鈕上的數字是現算的。這一條防的是有人日後改成寫死的表而忘了同步。
        foreach (BoardShapes::dimensions() as $key => $dim) {
            $shape = BoardShapes::make($key);

            $this->assertSame($shape['rows'], $dim['rows']);
            $this->assertSame($shape['cols'], $dim['cols']);
            $this->assertSame(count($shape['cells']), $dim['count']);
        }
    }

    public function test_applying_a_shape_replaces_the_board_and_covers_every_square(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $board = Board::create(['name' => '版型測試', 'user_id' => $user->id]);

        foreach (BoardShapes::KEYS as $key) {
            $expected = BoardShapes::make($key);

            $response = $this->actingAs($user)
                ->withoutMiddleware(AgeVerification::class)
                ->postJson("/tw/boards/{$board->id}/preset", ['preset' => $key])
                ->assertOk()
                ->assertJson(['success' => true]);

            $board->refresh();

            $this->assertSame($expected['rows'], $board->canvas_rows);
            $this->assertSame($expected['cols'], $board->canvas_cols);
            $this->assertCount(count($expected['cells']), $response->json('squares'));

            $this->assertSame($expected['path'], $board->path_data['all'], "版型 {$key} 的路徑不對");
        }
    }

    public function test_a_board_with_a_new_shape_still_plays(): void
    {
        /* 版型是幾何,但畫面是另一支程式在畫(board.js 會自己算出邊界與位移)。
           23 張系統棋盤全是十字與矩形環,所以「非矩形的版型能不能正常渲染」
           在這之前沒有任何一條路徑驗證過。 */
        $user = User::factory()->create(['email_verified_at' => now()]);
        $board = Board::create(['name' => '愛心版型', 'user_id' => $user->id]);

        $this->actingAs($user)
            ->withoutMiddleware(AgeVerification::class)
            ->postJson("/tw/boards/{$board->id}/preset", ['preset' => 'heart'])
            ->assertOk();

        $this->actingAs($user)
            ->withoutMiddleware(AgeVerification::class)
            ->get("/tw/play/{$board->id}")
            ->assertOk()
            ->assertSee('愛心版型');
    }

    public function test_an_unknown_shape_is_rejected(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $board = Board::create(['name' => '版型測試', 'user_id' => $user->id]);

        $this->actingAs($user)
            ->withoutMiddleware(AgeVerification::class)
            ->postJson("/tw/boards/{$board->id}/preset", ['preset' => 'pentagram'])
            ->assertStatus(422);
    }

    public function test_someone_elses_board_cannot_be_reshaped(): void
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $other = User::factory()->create(['email_verified_at' => now()]);
        $board = Board::create(['name' => '版型測試', 'user_id' => $owner->id]);

        $this->actingAs($other)
            ->withoutMiddleware(AgeVerification::class)
            ->postJson("/tw/boards/{$board->id}/preset", ['preset' => 'heart'])
            ->assertForbidden();
    }

    public function test_the_legacy_cross_keeps_its_shorter_path(): void
    {
        /* 十字鷹架有 40 格,但路徑只走前 23 格 —— 第 22 格就是那張棋盤的終點,
           後面 17 格是另外兩條臂,擺著當裝飾。把路徑改成「蓋滿全部格子」會讓遊戲
           走過終點、停在一格大冒險上,而且不會有任何錯誤訊息。 */
        $shape = BoardShapes::make('cross');

        $this->assertCount(40, $shape['cells']);
        $this->assertSame(range(0, 22), $shape['path']);
        $this->assertSame('end', $shape['cells'][22]['color']);
    }

    public function test_every_shape_has_a_name_in_the_editor(): void
    {
        /* 版型清單住在 PHP,按鈕從它長出來 —— 少一個翻譯 key 的症狀是按鈕上寫著
           「play.preset_xxx」,不是報錯。 */
        foreach (BoardShapes::KEYS as $key) {
            $label = __('play.preset_'.$key, [], 'zh_TW');

            $this->assertNotSame('play.preset_'.$key, $label, "版型 {$key} 沒有名稱");
        }
    }
}
