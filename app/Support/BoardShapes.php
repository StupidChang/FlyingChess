<?php

namespace App\Support;

/**
 * 棋盤版型(編輯器的「套用預設」)。
 *
 * 只管**幾何**:哪些格子、走哪個順序。內容是另一件事 —— 除了 `cross`(那是舊的
 * 預設棋盤鷹架,連文字一起帶著)之外,其他版型產出的格子都是空白的,由使用者
 * 或 seeder 填。
 *
 * 兩條硬規則,不是美感問題:
 *
 * 1. **相鄰的兩格只能上下左右,不能斜的**。`public/js/board.js` 的
 *    `computeArrowMap()` 只處理 dr===0 或 dc===0 兩種,斜走的那一格會靜靜地
 *    沒有箭頭 —— 玩的人看不出下一步往哪。所以菱形、六角、愛心這些「斜邊」
 *    全部畫成階梯。
 * 2. **格子不能重複**,而且 position 的順序就是走的順序。路徑是一條線(或一個圈),
 *    不能是樹 —— 像「工」字那種兩橫一豎的形狀無論怎麼排都會需要走回頭路,
 *    所以不在這裡。
 *
 * 中間留一塊 3×3(退而 2×2)空心的版型,開場轉盤才放得進去(見 board.js 的
 * `findWheelSlot`)。密實的版型(螺旋、蛇行)沒有轉盤,那是取捨不是壞掉。
 */
class BoardShapes
{
    /** 版型代號。編輯器的按鈕與 applyPreset 的驗證都從這裡長出來。 */
    public const KEYS = [
        'cross',
        'square',
        'diamond',
        'hexagon',
        'triangle',
        'horseshoe',
        'serpentine',
        'spiral',
        'heart',
        'double_ring',
    ];

    /**
     * 一個版型。回傳畫布大小、**照走的順序**排好的格子,以及路徑。
     *
     * 路徑通常就是全部格子(range(0, n-1)),但十字鷹架不是:它有 40 格,路徑只走
     * 前 23 格 —— 第 22 格就是那張棋盤的終點,後面 17 格是另外兩條臂,擺在畫面上
     * 當裝飾。把路徑改成蓋滿全部格子會讓遊戲**走過終點**,停在一格「大冒險」上。
     *
     * @return array{rows:int, cols:int, cells:array<int, array{row:int, col:int, color:string, text:string}>, path:array<int, int>}
     */
    public static function make(string $key): array
    {
        $shape = match ($key) {
            'cross' => self::cross(),
            'square' => self::ring(11, 11),
            'diamond' => self::diamond(4),
            'hexagon' => self::hexagon(),
            'triangle' => self::triangle(),
            'horseshoe' => self::horseshoe(),
            'serpentine' => self::serpentine(),
            'spiral' => self::spiral(),
            'heart' => self::heart(),
            'double_ring' => self::doubleRing(),
            default => self::ring(11, 11),
        };

        $cells = self::paint($shape['cells']);

        return [
            'rows' => $shape['rows'],
            'cols' => $shape['cols'],
            'cells' => $cells,
            'path' => $shape['path'] ?? range(0, count($cells) - 1),
        ];
    }

    /**
     * 每個版型的畫布與格數,給編輯器的按鈕標在名字旁邊。
     *
     * 從 make() 現算,不是另外寫一份表 —— 寫死的話改了版型而忘了改標籤,
     * 按鈕上的數字就會騙人。這裡全是純陣列運算,渲染一次的成本可以忽略。
     *
     * @return array<string, array{rows:int, cols:int, count:int}>
     */
    public static function dimensions(): array
    {
        $out = [];

        foreach (self::KEYS as $key) {
            $shape = self::make($key);
            $out[$key] = [
                'rows' => $shape['rows'],
                'cols' => $shape['cols'],
                'count' => count($shape['cells']),
            ];
        }

        return $out;
    }

    /**
     * 把座標清單變成格子:第一格 start、最後一格 end、其餘 normal,文字留空。
     *
     * @param  array<int, array{0:int, 1:int}|array{row:int, col:int, color:string, text:string}>  $cells
     * @return array<int, array{row:int, col:int, color:string, text:string}>
     */
    private static function paint(array $cells): array
    {
        $last = count($cells) - 1;
        $out = [];

        foreach ($cells as $i => $cell) {
            // cross 的鷹架已經自帶顏色與文字,不要覆蓋
            if (isset($cell['row'])) {
                $out[] = $cell;

                continue;
            }

            $out[] = [
                'row' => $cell[0],
                'col' => $cell[1],
                'color' => $i === 0 ? 'start' : ($i === $last ? 'end' : 'normal'),
                'text' => '',
            ];
        }

        return $out;
    }

    /* ────────────────────────── 版型 ────────────────────────── */

    /**
     * 十字(11×13)。舊的預設棋盤鷹架 —— 連文字一起,因為 `/play` 的預設棋盤
     * 長這樣,使用者按「十字形」是想要那一份骨架而不是 44 個空格。
     */
    private static function cross(): array
    {
        $map = [
            [1, 6, 'start', "起點\n擲骰子出發！"], [1, 7, 'move', '前進2格'],
            [2, 7, 'drink', '喝一口'], [3, 7, 'action', '舔對方耳根10秒'],
            [4, 7, 'move', "後退2格\n並脫一件衣物"], [5, 8, 'dare', "大冒險！\n由對方出題"],
            [5, 9, 'strip', "為對方口交\n至流水或堅挺10秒"], [5, 10, 'truth', "真心話\n說出最近的秘密幻想"],
            [5, 11, 'drink', "用嘴餵對方\n喝一口酒"], [5, 12, 'action', "咬吸對方脖子\n種一顆草莓"],
            [5, 13, 'move', "下一輪休息\n跳過下次擲骰"], [6, 13, 'action', "與對方舌吻\n整整1分鐘"],
            [7, 13, 'dare', '大冒險！'], [7, 12, 'female', '♀ 女生拍一張性感照片'],
            [7, 11, 'action', "手伸對方內褲裡\n隨意發揮30秒"], [7, 10, 'truth', "真心話\n說出最想讓對方做的事"],
            [7, 9, 'drink', '喝半杯'], [7, 8, 'male', "♂ 男生停留此格\n後插對方1分鐘"],
            [8, 7, 'action', "為對方擋管或\n指逼1分鐘"], [9, 7, 'strip', "選一個姿勢\n讓對方插至少10下"],
            [10, 7, 'action', "對方口交\n1分鐘"], [11, 7, 'dare', "打對方屁股\n3下"],
            [11, 6, 'end', "終點\n恭喜！為愛鼓掌！"], [11, 5, 'move', "後退3格\n並脫一件衣物"],
            [10, 5, 'strip', "露出私處\n允許對方拍照一張"], [9, 5, 'action', "從背後抱住\n隨意撫摸1分鐘"],
            [8, 5, 'action', "舔對方大腿內側\n對方若笑則罰喝半杯"], [7, 4, 'action', "對方乳交\n1分鐘"],
            [7, 3, 'female', "♀ 女生坐在\n男生臉上摩擦"], [7, 2, 'drink', '喝一口'],
            [7, 1, 'action', "和對方用觀音坐蓮\n自己動至少10下"], [6, 1, 'dare', "大冒險！\n由對方出題"],
            [5, 1, 'action', "為對方口交\n3分鐘"], [5, 2, 'truth', "真心話\n說出最喜歡的體位"],
            [5, 3, 'action', "讓對方從耳根\n舔到胸口"], [5, 4, 'action', "手伸對方內褲裡\n隨意發揮30秒"],
            [4, 5, 'drink', '喝半杯'], [3, 5, 'move', '前進2格'],
            [2, 5, 'strip', '自己脫一件衣物'], [1, 5, 'dare', "嚼對方口水\n喝下"],
        ];

        $cells = [];
        foreach ($map as [$row, $col, $color, $text]) {
            $cells[] = ['row' => $row, 'col' => $col, 'color' => $color, 'text' => $text];
        }

        // 路徑只走到第 22 格(那一格是 end);23 之後是另外兩條臂,只當裝飾
        return ['rows' => 11, 'cols' => 13, 'cells' => $cells, 'path' => range(0, 22)];
    }

    /** 方形環。順時針一圈,終點回到起點旁邊。 */
    private static function ring(int $rows, int $cols): array
    {
        return ['rows' => $rows, 'cols' => $cols, 'cells' => self::ringCells(1, 1, $rows, $cols)];
    }

    /**
     * 一圈矩形的外框,從左上角順時針走。最後一格停在起點下面(不重複起點)。
     *
     * @return array<int, array{0:int, 1:int}>
     */
    private static function ringCells(int $r0, int $c0, int $r1, int $c1): array
    {
        $cells = [];

        for ($c = $c0; $c <= $c1; $c++) {
            $cells[] = [$r0, $c];
        }
        for ($r = $r0 + 1; $r <= $r1; $r++) {
            $cells[] = [$r, $c1];
        }
        for ($c = $c1 - 1; $c >= $c0; $c--) {
            $cells[] = [$r1, $c];
        }
        for ($r = $r1 - 1; $r > $r0; $r--) {
            $cells[] = [$r, $c0];
        }

        return $cells;
    }

    /**
     * 菱形。四個斜邊都是「右一步、下一步」的階梯 —— 真正的斜線走法會讓箭頭消失。
     *
     * @param  int  $k  半徑(格),畫布是 (2k+1)×(2k+1)
     */
    private static function diamond(int $k): array
    {
        $size = 2 * $k + 1;
        $cells = [[1, $k + 1]];
        $r = 1;
        $c = $k + 1;

        // 右下 → 左下 → 左上 → 右上,四段各 k 個階梯
        foreach ([[0, 1, 1, 0], [0, -1, 1, 0], [0, -1, -1, 0], [0, 1, -1, 0]] as $i => [$ar, $ac, $br, $bc]) {
            for ($n = 0; $n < $k; $n++) {
                // 最後一個階梯的最後一步會踩回起點,收在它前面一格 —— 兩者相鄰,圈自然閉合
                if ($i === 3 && $n === $k - 1) {
                    $r += $br;
                    $c += $bc;
                    $cells[] = [$r, $c];

                    break;
                }
                $r += $ar;
                $c += $ac;
                $cells[] = [$r, $c];
                $r += $br;
                $c += $bc;
                $cells[] = [$r, $c];
            }
        }

        return ['rows' => $size, 'cols' => $size, 'cells' => $cells];
    }

    /** 六角環(7×11)。上下各一條橫邊,四個斜邊走階梯。 */
    private static function hexagon(): array
    {
        $cells = [];

        // 上橫邊
        for ($c = 4; $c <= 8; $c++) {
            $cells[] = [1, $c];
        }
        // 右上斜邊:下一步、右一步
        $r = 1;
        $c = 8;
        for ($n = 0; $n < 3; $n++) {
            $cells[] = [++$r, $c];
            $cells[] = [$r, ++$c];
        }
        // 右下斜邊:下一步、左一步
        for ($n = 0; $n < 3; $n++) {
            $cells[] = [++$r, $c];
            $cells[] = [$r, --$c];
        }
        // 下橫邊(往左)
        for ($c = $c - 1; $c >= 4; $c--) {
            $cells[] = [$r, $c];
        }
        // 左下斜邊:上一步、左一步
        $c = 4;
        for ($n = 0; $n < 3; $n++) {
            $cells[] = [--$r, $c];
            $cells[] = [$r, --$c];
        }
        // 左上斜邊:上一步、右一步。最後一步會踩回上橫邊的第一格,所以少走一步
        for ($n = 0; $n < 3; $n++) {
            $cells[] = [--$r, $c];
            if ($n < 2) {
                $cells[] = [$r, ++$c];
            }
        }

        return ['rows' => 7, 'cols' => 11, 'cells' => $cells];
    }

    /** 三角(8×13)。從頂點沿右斜邊下去、走底邊、再沿左斜邊回到頂點下方。 */
    private static function triangle(): array
    {
        $cells = [[1, 7]];
        $r = 1;
        $c = 7;

        // 右斜邊:下一步、右一步 ×6 → 落在 (7,13)
        for ($n = 0; $n < 6; $n++) {
            $cells[] = [++$r, $c];
            $cells[] = [$r, ++$c];
        }
        // 底邊往左
        $cells[] = [++$r, $c];
        for ($c = $c - 1; $c >= 1; $c--) {
            $cells[] = [$r, $c];
        }
        // 左斜邊:上一步、右一步 ×6,最後一步省掉(不然踩回頂點)
        $c = 1;
        for ($n = 0; $n < 6; $n++) {
            $cells[] = [--$r, $c];
            if ($n < 5) {
                $cells[] = [$r, ++$c];
            }
        }

        return ['rows' => 8, 'cols' => 13, 'cells' => $cells];
    }

    /**
     * ㄩ 字(9×13)。開口朝上的馬蹄形:左邊下去、走底邊、右邊上來。
     *
     * 唯一一個**不閉合**的版型 —— 起點與終點各在一個開口上,遠遠相望。
     * 中間空一大塊,轉盤放得下。
     */
    private static function horseshoe(): array
    {
        $cells = [];

        for ($r = 1; $r <= 9; $r++) {
            $cells[] = [$r, 1];
        }
        for ($c = 2; $c <= 13; $c++) {
            $cells[] = [9, $c];
        }
        for ($r = 8; $r >= 1; $r--) {
            $cells[] = [$r, 13];
        }

        return ['rows' => 9, 'cols' => 13, 'cells' => $cells];
    }

    /**
     * 蛇行(7×9)。三條橫排,排與排之間隔兩列,靠端點的兩格垂直連起來。
     *
     * 隔兩列是為了看得出是三條分開的排 —— 隔一列的話整片會看起來像實心方塊。
     */
    private static function serpentine(): array
    {
        $cells = [];
        $rows = [1, 4, 7];

        foreach ($rows as $i => $row) {
            $range = $i % 2 === 0 ? range(1, 9) : range(9, 1);
            foreach ($range as $c) {
                $cells[] = [$row, $c];
            }

            if ($i < count($rows) - 1) {
                // 轉折:停在剛剛那排的末端那一列,往下兩格接到下一排
                $col = $i % 2 === 0 ? 9 : 1;
                $cells[] = [$row + 1, $col];
                $cells[] = [$row + 2, $col];
            }
        }

        return ['rows' => 7, 'cols' => 9, 'cells' => $cells];
    }

    /** 螺旋(7×9)。從外圈一路繞到中心,終點在最裡面 —— 走到中間就是走完。 */
    private static function spiral(): array
    {
        $cells = [];
        $top = 1;
        $bottom = 7;
        $left = 1;
        $right = 9;

        while ($top <= $bottom && $left <= $right) {
            for ($c = $left; $c <= $right; $c++) {
                $cells[] = [$top, $c];
            }
            for ($r = $top + 1; $r <= $bottom; $r++) {
                $cells[] = [$r, $right];
            }
            if ($top < $bottom) {
                for ($c = $right - 1; $c >= $left; $c--) {
                    $cells[] = [$bottom, $c];
                }
            }
            if ($left < $right) {
                // 往上停在 $top + 2:$top + 1 那一列要留空,不然下一圈會貼上這一圈
                for ($r = $bottom - 1; $r >= $top + 2; $r--) {
                    $cells[] = [$r, $left];
                }
            }

            $top += 2;
            $bottom -= 2;
            $left += 2;
            $right -= 2;

            /* 往內收的那一步。少了這格走道,上一圈的尾端與下一圈的開頭會隔一格 ——
               路徑看起來是斷的,箭頭也接不上(computeArrowMap 只認相鄰兩格)。 */
            if ($top <= $bottom && $left <= $right) {
                $cells[] = [$top, $left - 1];
            }
        }

        return ['rows' => 7, 'cols' => 9, 'cells' => $cells];
    }

    /**
     * 愛心(9×11)。手工排的閉合外框 —— 心形的曲線用階梯逼近,每一步都是上下左右。
     *
     * 從左上凹處出發,順時針:左半上緣 → 右半上緣 → 右側往下收 → 底部尖端 →
     * 左側往上開 → 回到凹處旁邊。
     */
    private static function heart(): array
    {
        $cells = [
            // 凹處往右,爬上右邊那一瓣
            [3, 6], [3, 7], [2, 7], [2, 8], [1, 8], [1, 9], [1, 10],
            // 右瓣外緣下來,到最寬的地方
            [2, 10], [2, 11], [3, 11], [4, 11], [5, 11],
            // 往內收,一路收到尖端
            [5, 10], [6, 10], [6, 9], [7, 9], [7, 8], [8, 8], [8, 7], [9, 7], [9, 6],
            // 尖端往左,鏡像走回去
            [9, 5], [8, 5], [8, 4], [7, 4], [7, 3], [6, 3], [6, 2], [5, 2], [5, 1],
            // 左邊最寬處往上,爬上左邊那一瓣
            [4, 1], [3, 1], [2, 1], [2, 2], [1, 2], [1, 3], [1, 4],
            // 左瓣內緣回到凹處旁邊
            [2, 4], [2, 5], [3, 5],
        ];

        return ['rows' => 9, 'cols' => 11, 'cells' => $cells];
    }

    /**
     * 回字雙環(7×9)。外圈走完之後從一段走道進到內圈 —— 一張棋盤要繞兩圈,
     * 中後段會回到棋盤中央,節奏跟一圈到底的環形不一樣。
     */
    private static function doubleRing(): array
    {
        $cells = self::ringCells(1, 1, 7, 9);

        // 外圈最後停在 (2,1);往右再往下兩步接進內圈的左上角
        $cells[] = [2, 2];
        $cells[] = [3, 2];

        foreach (self::ringCells(3, 3, 5, 7) as $cell) {
            $cells[] = $cell;
        }

        return ['rows' => 7, 'cols' => 9, 'cells' => $cells];
    }
}
