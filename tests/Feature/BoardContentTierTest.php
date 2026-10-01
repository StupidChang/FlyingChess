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

    /*
     * 2026-09-27 使用者改了界線:**免費棋盤也要走到口交與插入**,只是份量比付費少;
     * 付費是賣點,後半段要大量的口交、插入與體位變化。以前這裡守的是反方向
     * (免費不准出現口交)—— 那條線已經不存在了。
     */
    // 「口交／手指」這一段也包括用玩具刺激私處(玩具開箱版就是這樣走的)
    private const ORAL = '/口交|69|深喉|舔(?:他|她|對方)?的?(小穴|陰蒂|陰唇|私處|下面)|含住(?:他|她|對方)?的?(陰莖|肉棒|龜頭)|手指(伸)?進去|手指伸入|跳蛋|震動棒|按摩棒|飛機杯/u';

    private const PENETRATION = '/插入|插進|抽插|做愛|性交|體位|騎乘|騎上去|後入|從後面(做|進|來|插|頂)|傳教士|戴套|戴好保險套|進去(?!房|浴|廁|被窩)/u';

    private function texts(Board $board): array
    {
        $byPos = $board->squares->keyBy('position');

        return array_map(fn ($p) => (string) $byPos[$p]->text, $board->resolvedPath('all'));
    }

    private function hits(array $texts, string $re): int
    {
        return count(array_filter($texts, fn ($t) => preg_match($re, $t)));
    }

    public function test_every_board_reaches_sex_and_premium_has_more_of_it(): void
    {
        $this->seed([BoardSeeder::class, BoardTemplateSeeder::class]);

        $boards = Board::with('squares')->whereNull('user_id')
            ->where(fn ($q) => $q->where('is_template', true)->orWhere('is_default', true))->get();

        $free = $paid = [];
        foreach ($boards as $board) {
            $t = $this->texts($board);
            $this->assertGreaterThanOrEqual(1, $this->hits($t, self::ORAL), "{$board->name} 沒有口交／手指");
            $this->assertGreaterThanOrEqual(1, $this->hits($t, self::PENETRATION), "{$board->name} 沒有插入");
            $explicit = $this->hits($t, self::ORAL) + $this->hits($t, self::PENETRATION);
            $board->is_premium_template ? $paid[] = $explicit : $free[] = $explicit;
        }

        $this->assertNotEmpty($free);
        $this->assertNotEmpty($paid);
        // 免費有性交,但付費的份量明顯多
        // 關鍵字只抓得到一部分寫法(付費格常寫「頂」「動」「騎在」),實際比例依階段標記約 1.8 倍
        $this->assertGreaterThan(array_sum($free) / count($free) * 1.25, array_sum($paid) / count($paid));
        $this->assertTrue(Board::where('name', '情侶飛行棋 V2.0')->value('is_premium_template'));
    }

    public function test_every_board_escalates_in_order(): void
    {
        $this->seed([BoardSeeder::class, BoardTemplateSeeder::class]);

        // 脫衣 → 胸部 → 私處 → 口交／手指 → 插入,每一段第一次出現都要在前一段之後
        $ladder = [
            '脫衣' => '/脫|內衣|內褲/u',
            // 「靠在胸口」是暖身,不算
            '胸部' => '/乳頭|胸部|胸罩|(揉|摸|舔|親|吸).{0,6}胸(?!口)|的胸(?!口)/u',
            '私處' => '/私處|私密處|陰蒂|陰莖|肉棒|小穴|龜頭|打手槍|脫光|全裸|內褲裡|伸進.{0,4}內褲|脫掉.{0,6}內褲|(摸|弄|揉).{0,4}下面/u',
            '口交' => self::ORAL,
            '插入' => self::PENETRATION,
        ];

        $boards = Board::with('squares')->whereNull('user_id')
            ->where(fn ($q) => $q->where('is_template', true)->orWhere('is_default', true))->get();

        foreach ($boards as $board) {
            $t = $this->texts($board);
            $n = count($t);
            $prev = -1;
            $prevName = '起點';
            foreach ($ladder as $name => $re) {
                $first = null;
                foreach ($t as $i => $text) {
                    if (preg_match($re, $text)) {
                        $first = $i;
                        break;
                    }
                }
                $this->assertNotNull($first, "{$board->name} 沒有「{$name}」這一段");
                $this->assertGreaterThan($prev, $first, "{$board->name}:「{$name}」出現在第 ".($first + 1)." 步,比「{$prevName}」還早");
                [$prev, $prevName] = [$first, $name];
            }

            // 插入不能太早:免費在 70% 之後,付費在 55% 之後
            $floor = $board->is_premium_template ? .55 : .70;
            $this->assertGreaterThanOrEqual($floor, ($prev + 1) / $n, "{$board->name} 太早插入(第 ".($prev + 1)."/{$n} 步)");
        }
    }

    public function test_reseeding_changes_only_editorial_fields(): void
    {
        $this->seed(BoardSeeder::class);
        $board = Board::where('name', '情侶飛行棋 V2.0')->firstOrFail();
        $before = $board->squares()->orderBy('position')
            ->get(['position', 'grid_row', 'grid_col', 'fly_to'])->toArray();

        $board->squares()->where('position', 7)->update(['text' => '舊內容']);
        $this->seed(BoardSeeder::class);

        // 44 而不是 40:十字的四個內轉角補上格子之後,外圈是 44 格。
        // 轉角插在 index 5,所以原本 index 6 的內容現在落在 7。
        $this->assertSame(44, $board->squares()->count());
        $this->assertSame($before, $board->squares()->orderBy('position')
            ->get(['position', 'grid_row', 'grid_col', 'fly_to'])->toArray());
        $this->assertSame("猜拳\n輸的人脫上衣，贏的人親他鎖骨", $board->squares()
            ->where('position', 7)->value('text'));
    }

    public function test_premium_board_moves_from_warmup_to_explicit_play(): void
    {
        $this->seed(BoardSeeder::class);
        $board = Board::where('name', '情侶飛行棋 V2.0')->firstOrFail();

        /* 四個內轉角補進外圈之後,原本 40 格的編號整個往後挪:轉角插在新編號的
           5、19、29、39,所以之後的每一格依序 +1、+2、+3、+4。下面的區間是把
           原本的 1-9 / 11-19 / 20-30 / 31-39 換算過來的,分級的意思沒有變。 */
        $warmup = $board->squares()->whereBetween('position', [1, 10])->pluck('text')->implode(' ');
        $teasing = $board->squares()->whereBetween('position', [12, 21])->pluck('text')->implode(' ');
        $foreplay = $board->squares()->whereBetween('position', [22, 33])->pluck('text')->implode(' ');
        $explicit = $board->squares()->whereBetween('position', [34, 43])->pluck('text')->implode(' ');

        $this->assertDoesNotMatchRegularExpression(self::ORAL, $warmup);
        $this->assertDoesNotMatchRegularExpression(self::PENETRATION, $warmup);
        $this->assertMatchesRegularExpression('/脫/u', $teasing);
        $this->assertMatchesRegularExpression(self::ORAL, $foreplay.' '.$explicit);
        $this->assertMatchesRegularExpression(self::PENETRATION, $explicit);
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
            $this->assertSame(44, $board->squares_count);
            $this->assertSame(range(0, 43), $board->path_data['all']);
            $this->assertSame($referenceImage, $board->reference_image);
            $this->assertCount(6, $board->startWheel());
        }
    }
}
