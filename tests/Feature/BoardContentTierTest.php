<?php

namespace Tests\Feature;

use App\Models\Board;
use Database\Seeders\BoardSeeder;
use Database\Seeders\BoardTemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BoardContentTierTest extends TestCase
{
    use RefreshDatabase;

    public function test_premium_is_fully_explicit_while_free_content_stays_mild(): void
    {
        $this->seed([BoardSeeder::class, BoardTemplateSeeder::class]);

        $premiumText = Board::where('is_premium_template', true)->with('squares')->get()
            ->flatMap->squares->pluck('text')->implode("\n");
        $freeText = Board::where('is_premium_template', false)->with('squares')->get()
            ->flatMap->squares->pluck('text')->implode("\n");

        $this->assertStringContainsString('私密處', $premiumText);
        $this->assertStringContainsString('脫一件', $premiumText);
        $this->assertStringContainsString('口交', $premiumText);
        $this->assertStringContainsString('體位', $premiumText);
        $this->assertStringContainsString('從後面做', $premiumText);
        $this->assertStringContainsString('舌吻', $freeText);
        $this->assertStringContainsString('私密處', $freeText);
        $this->assertStringNotContainsString('口交', $freeText);
        $this->assertStringNotContainsString('體位', $freeText);
        $this->assertTrue(Board::where('name', '情侶飛行棋 V2.0')->value('is_premium_template'));

        foreach (['口交', '性交', '肛交', '後插', '指逼', '體位', '觀音坐蓮', '插至少'] as $forbidden) {
            $this->assertStringNotContainsString($forbidden, $freeText);
        }
    }

    public function test_reseeding_changes_only_editorial_fields(): void
    {
        $this->seed(BoardSeeder::class);
        $board = Board::where('name', '情侶飛行棋 V2.0')->firstOrFail();
        $before = $board->squares()->orderBy('position')
            ->get(['position', 'grid_row', 'grid_col', 'fly_to'])->toArray();

        $board->squares()->where('position', 6)->update(['text' => '舊內容']);
        $this->seed(BoardSeeder::class);

        $this->assertSame(40, $board->squares()->count());
        $this->assertSame($before, $board->squares()->orderBy('position')
            ->get(['position', 'grid_row', 'grid_col', 'fly_to'])->toArray());
        $this->assertSame("從嘴唇一路親到\n對方的鎖骨", $board->squares()
            ->where('position', 6)->value('text'));
    }

    public function test_premium_board_moves_from_warmup_to_explicit_play(): void
    {
        $this->seed(BoardSeeder::class);
        $board = Board::where('name', '情侶飛行棋 V2.0')->firstOrFail();

        $warmup = $board->squares()->whereBetween('position', [1, 9])->pluck('text')->implode(' ');
        $teasing = $board->squares()->whereBetween('position', [11, 19])->pluck('text')->implode(' ');
        $foreplay = $board->squares()->whereBetween('position', [20, 30])->pluck('text')->implode(' ');
        $explicit = $board->squares()->whereBetween('position', [31, 39])->pluck('text')->implode(' ');

        $this->assertStringNotContainsString('口交', $warmup);
        $this->assertStringContainsString('脫掉一件', $teasing);
        $this->assertStringContainsString('口交', $foreplay);
        $this->assertStringContainsString('進去', $explicit);
        $this->assertTrue($board->is_premium_template);
        $this->assertTrue(Board::where('name', '輕度暖身版')->value('is_default'));
        $this->assertCount(6, $board->startWheel());
        $this->assertTrue(collect($board->startWheel())->contains('enter', true));
        $this->assertNull(Board::where('name', '輕度暖身版')->where('is_default', true)->firstOrFail()->startWheel());
    }

    public function test_every_system_board_has_a_complete_playable_path(): void
    {
        $this->seed([BoardSeeder::class, BoardTemplateSeeder::class]);

        Board::whereNull('user_id')->withCount('squares')->get()->each(function (Board $board): void {
            $this->assertSame(
                $board->squares_count,
                count($board->path_data['all'] ?? []),
                "{$board->name} 的路線必須涵蓋全部格子",
            );
            $this->assertSame(
                range(0, $board->squares_count - 1),
                array_values($board->path_data['all']),
                "{$board->name} 的路線必須連續且無缺格",
            );
        });
    }

    public function test_both_uploaded_reference_versions_are_available_as_complete_templates(): void
    {
        $this->seed(BoardTemplateSeeder::class);

        $expected = [
            '情侶互換飛行棋 V8.0（四人版）' => 'images/board-references/couples-flying-chess-v8.jpg',
            '情侶／炮友飛行棋 V1.0' => 'images/board-references/couples-flying-chess-v1.jpg',
        ];

        foreach ($expected as $name => $referenceImage) {
            $board = Board::where('name', $name)->withCount('squares')->firstOrFail();

            $this->assertTrue($board->is_template);
            $this->assertTrue($board->is_premium_template);
            $this->assertSame(40, $board->squares_count);
            $this->assertSame(range(0, 39), $board->path_data['all']);
            $this->assertSame($referenceImage, $board->reference_image);
            $this->assertCount(6, $board->startWheel());
        }
    }
}
