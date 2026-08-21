<?php

namespace Database\Seeders;

use App\Models\Board;
use App\Models\BoardSquare;
use Illuminate\Database\Seeder;

/**
 * 《情侶飛行棋 V8.0（互換無限-四人版）》的**逐格復刻**。
 *
 * 原圖是 CENJA 設計的實體棋盤(cenja.notion.site),使用者上傳原圖要求「格子完全
 * 相同」的版本,用來看我們的畫布渲染這種傳統飛行棋盤面的效果。所以這一支和
 * BoardTemplateSeeder 裡那些**自己寫內容**的範本不一樣:這裡每一格的文字都是照
 * 原圖轉錄的(簡體轉繁體,用詞不動)。
 *
 * ── 版面(13×13)──────────────────────────────────────────
 *
 * 傳統飛行棋是一個**空心的八角環** + 四條穿過中間的家門:
 *
 *   - 外圈 48 格:四條邊各 7 格(含兩端的斜切角),四個肩角各 2+1+2 格
 *   - 家門 4×5 格:上綠、右黃、下紅、左藍,各從自己的入口指向中央
 *   - 中央 1 格:終點
 *   - 四個肩角是「飛躍對面」格,對角互飛(fly_to)
 *   - 四個角落的 1–6 轉盤 = 我們的進場轉盤(Board::DEFAULT_START_WHEEL 就是它)
 *
 * ── 一個做不到的地方(重要)────────────────────────────────
 *
 * 原盤是四人各走一條路線的飛行棋;我們的自訂棋盤只有**一條線性路徑**
 * (path_data 最多 all/male/female 三條,而且遊玩引擎一次走一條)。所以路徑取
 * **藍色玩家的完整路線**:入口 → 順時針繞外圈一圈 → 轉進藍色家門 → 終點,
 * 共 54 格。另外三條家門的 15 格照原圖畫在盤面上,但不在路徑上 —— 它們是別人的
 * 家門,本來就不該被藍色玩家走到。
 *
 * 真正的四人飛行棋在站上是另一套引擎(/games,GameService 的 52 格賽道),
 * 那一套的格子目前沒有文字。要把這份內容接上去是另一件事。
 */
class FlyingChessV8ReplicaSeeder extends Seeder
{
    public const BOARD_NAME = '情侶飛行棋 V8.0 原版復刻（四人版）';

    /**
     * 外圈 48 格,從藍色入口開始**順時針**排。
     *
     * 每一筆是 [row, col, color, text]。color 是我們的語意色(原圖的顏色是玩家色,
     * 那個資訊已經寫在文字裡:「綠色玩家…」「紅色停留…」)。
     */
    private const RING = [
        // 左邊(往上)
        [7, 1, 'normal', "藍色玩家停留此格\n下回合可進入"],
        [6, 1, 'start', "和對方伴侶喝一杯交杯酒,\n手拉手門外呆一分鐘"],
        [5, 1, 'move', "後退 3 格\n並脫掉一件衣服"],
        [4, 1, 'drink', '喝一杯'],
        // 左上肩
        [4, 2, 'action', '手伸進對方伴侶內褲裡按摩 30 秒'],
        [4, 3, 'action', '自己指定一個地方讓對方伴侶種一顆草莓'],
        [4, 4, 'move', "黃色停留此格:脫光衣物讓對方伴侶\n從頭到腳撫摸一遍,可飛躍對面"],
        [3, 4, 'action', '按摩對方伴侶胸部 30 秒'],
        [2, 4, 'strip', '為對方伴侶脫一件衣物'],
        // 上邊(往右)
        [1, 4, 'move', '再投一次'],
        [1, 5, 'action', '手伸進對方伴侶內褲裡按摩 30 秒'],
        [1, 6, 'drink', "和對方伴侶喝一杯交杯酒,\n手拉手門外呆一分鐘"],
        [1, 7, 'normal', "綠色玩家停留此格\n下回合可進入"],
        [1, 8, 'action', '舔對方伴侶耳根 10 秒'],
        [1, 9, 'drink', '用嘴餵對方伴侶一口酒'],
        [1, 10, 'move', '再投一次'],
        // 右上肩
        [2, 10, 'strip', '為對方伴侶脫掉一件衣物'],
        [3, 10, 'action', '和對方伴侶舌吻 10 秒'],
        [4, 10, 'move', "紅色停留:讓對方伴侶插入自己私處 10 秒,\n可飛躍對面"],
        [4, 11, 'strip', "喝半杯並脫掉一件衣物,\n門外呆 30 秒"],
        [4, 12, 'action', '公主抱對方伴侶 10 秒'],
        // 右邊(往下)
        [4, 13, 'drink', '喝半杯'],
        [5, 13, 'action', "舔對方伴侶大腿內側 20 秒,\n對方若笑場則出門呆 30 秒"],
        [6, 13, 'move', "後退 2 格\n並脫一件衣物"],
        [7, 13, 'normal', "黃色玩家停留此格\n下回合可進入"],
        [8, 13, 'action', '手伸進對方伴侶內褲裡按摩 30 秒'],
        [9, 13, 'dare', '被其他人各打屁股一下'],
        [10, 13, 'drink', '喝一杯'],
        // 右下肩
        [10, 12, 'move', "後退 2 格\n並脫一件衣物"],
        [10, 11, 'drink', "用嘴餵對方伴侶一口酒,\n手拉手門外呆一分鐘"],
        [10, 10, 'move', "藍色停留:讓對方舔自己伴侶乳頭 30 秒,\n可飛躍對面"],
        [11, 10, 'strip', '為對方伴侶脫掉一件衣物'],
        [12, 10, 'action', '和對方伴侶舌吻 10 秒'],
        // 下邊(往左)
        [13, 10, 'move', '再投一次'],
        [13, 9, 'action', '手伸進對方伴侶內褲裡按摩 30 秒'],
        [13, 8, 'action', '自己指定一個地方讓對方伴侶種一顆草莓'],
        [13, 7, 'normal', "紅色玩家停留此格\n下回合可進入"],
        [13, 6, 'strip', '喝半杯並脫掉一件衣物'],
        [13, 5, 'action', '與對方伴侶手牽手去屋外呆一分鐘'],
        [13, 4, 'move', '再投一次'],
        // 左下肩
        [12, 4, 'move', "後退 2 格\n並喝半杯酒"],
        [11, 4, 'action', "撓或舔對方伴侶胸部 20 秒,\n對方若笑場則出門呆 30 秒"],
        [10, 4, 'move', "綠色停留:讓對方舔自己伴侶私處 30 秒,\n可飛躍對面"],
        [10, 3, 'dare', '打對方伴侶屁股 5 下'],
        [10, 2, 'action', '手伸進對方伴侶內褲裡隨意發揮 30 秒'],
        // 左邊(往上,回到入口下方)
        [10, 1, 'drink', '喝半杯'],
        [9, 1, 'action', '舔對方伴侶耳根到胸口'],
        [8, 1, 'action', '從背後抱住對方伴侶隨意撫摸 30 秒'],
    ];

    /**
     * 四條家門,各 5 格,從外圈往中央排。
     *
     * 藍色那條在路徑上(接在外圈之後);其餘三條照原圖畫出來,但不在路徑上 ——
     * 那是別人的家門。
     */
    private const LANES = [
        'blue' => [
            [7, 2, 'dare', '與對方伴侶去衛生間獨處 5 分鐘'],
            [7, 3, 'dare', "對方伴侶為自己手淫 30 秒,\n若勃起需後退 5 格"],
            [7, 4, 'action', '趴在對方伴侶身上做 10 個俯臥撐'],
            [7, 5, 'dare', "喝 6 杯酒,每少喝一杯,\n則對方可後入自己伴侶 10 秒"],
            [7, 6, 'strip', '喝一杯並脫光衣服'],
        ],
        'green' => [
            [2, 7, 'drink', "每喝一杯酒自己伴侶可前進一格,\n至少喝一杯"],
            [3, 7, 'dare', '對方下體摩擦自己伴侶私處 30 秒'],
            [4, 7, 'dare', "喝 6 杯酒,每少喝一杯,\n則對方可後入自己伴侶 10 秒"],
            [5, 7, 'dare', '門外抽插對方伴侶 30 秒'],
            [6, 7, 'strip', '喝一杯並脫光衣服'],
        ],
        'yellow' => [
            [7, 12, 'drink', "每喝一杯酒可前進一格,\n最少喝一杯"],
            [7, 11, 'dare', "和對方伴侶用觀音坐蓮姿勢\n自己動至少 20 秒"],
            [7, 10, 'dare', '選一個門外場所讓對方伴侶抽插 1 分鐘'],
            [7, 9, 'dare', '任選一位玩家口交 2 分鐘'],
            [7, 8, 'strip', '喝一杯並脫光衣服'],
        ],
        'red' => [
            [12, 7, 'dare', '與對方伴侶去衛生間獨處 5 分鐘'],
            [11, 7, 'dare', '含一口水在門外為對方伴侶口交 1 分鐘'],
            [10, 7, 'dare', '對方伴侶當面進入自己私處抽插 1 分鐘'],
            [9, 7, 'dare', '撅起屁股讓對方撫摸私處一分鐘'],
            [8, 7, 'strip', '喝一杯並脫光衣服'],
        ],
    ];

    private const CENTER = [7, 7, 'end', "終點\n第一位到達者可讓自己伴侶前進任意 1–6 格,\n同組雙人都進入終點即獲勝,\n可隨意要求對方二位做任意一件"];

    /**
     * 四個肩角「可飛躍對面」的對子,對角互飛:左上↔右下、右上↔左下。
     *
     * 這裡的數字是**路徑上的 position**,不是 RING 的索引 —— 外圈起跑點轉了一格
     * (見 run()),所以肩角的 position 是 RING 索引減一。
     */
    private const FLY_PAIRS = [[5, 29], [17, 41], [29, 5], [41, 17]];

    public function run(): void
    {
        $board = Board::updateOrCreate(
            ['name' => self::BOARD_NAME, 'is_template' => true],
            [
                'description' => '18+ 逐格復刻｜CENJA 設計的《情侶飛行棋 V8.0（互換無限-四人版）》實體棋盤，'
                    .'外圈 48 格、四條家門與中央終點全部照原圖轉錄。所有任務須雙方同意，任何時候都可跳過。',
                'is_template' => true,
                'is_premium_template' => true,
                'canvas_rows' => 13,
                'canvas_cols' => 13,
                // 原圖四個角落的 1–6 轉盤。DEFAULT_START_WHEEL 就是照這張圖做的
                'start_wheel' => ['enabled' => true, 'segments' => Board::DEFAULT_START_WHEEL],
                'reference_image' => 'images/board-references/couples-flying-chess-v8.jpg',
            ]
        );

        /* 路徑 = 藍色玩家的完整路線:外圈一圈(48)+ 藍色家門(5)+ 終點(1)= 54 格。
           其餘三條家門排在後面的 position,不進路徑 —— 見類別開頭的說明。

           外圈**從閘門的下一格開始**(RING[1]),繞完一圈之後最後一格才是閘門
           (RING[0]),然後轉進家門 —— 這樣「閘門 →(7,2)」是上下左右相鄰的。
           直接從閘門起跑的話,最後一步會從 (8,1) 斜跨到 (7,2),那一格不會有箭頭
           (board.js 的 computeArrowMap 只認正交),玩的人看不出要往哪走。 */
        $ring = self::RING;
        $gate = array_shift($ring);
        $ring[] = $gate;

        $cells = $ring;
        foreach (self::LANES['blue'] as $cell) {
            $cells[] = $cell;
        }
        $cells[] = self::CENTER;

        $pathLength = count($cells);

        foreach (['green', 'yellow', 'red'] as $colour) {
            foreach (self::LANES[$colour] as $cell) {
                $cells[] = $cell;
            }
        }

        $flyTo = [];
        foreach (self::FLY_PAIRS as [$from, $to]) {
            $flyTo[$from] = $to;
        }

        foreach ($cells as $pos => [$row, $col, $color, $text]) {
            BoardSquare::updateOrCreate(
                ['board_id' => $board->id, 'position' => $pos],
                [
                    'board_id' => $board->id,
                    'position' => $pos,
                    'text' => $text,
                    'color' => $color,
                    'grid_row' => $row,
                    'grid_col' => $col,
                    'fly_to' => $flyTo[$pos] ?? null,
                ]
            );
        }

        // 之前跑過、格數比較多的版本留下來的殘骸
        $board->squares()->where('position', '>=', count($cells))->delete();

        $board->update([
            'path_data' => ['all' => range(0, $pathLength - 1), 'male' => null, 'female' => null],
        ]);
    }
}
