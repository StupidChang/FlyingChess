<?php

namespace Database\Seeders;

use App\Models\Board;
use App\Models\BoardSquare;
use Illuminate\Database\Seeder;

class BoardSeeder extends Seeder
{
    /**
     * Default adult/couples board — 40 squares (positions 0–39)
     * Layout: 11×13 CSS Grid, cross/十字 shape
     */
    /* 十字外圈的 44 格。原本是 40 格,四個內轉角 (5,7)(7,7)(7,5)(5,5) 沒有格子 ——
       路徑在轉彎處是斜著跳過去的,畫面上就是缺一角。補齊之後轉彎才走得順。
       ⚠ 既有棋盤的格子不會被這裡改到(下面只在「一格都沒有」時才建立),
       線上資料由 2026_08_01_140000 的 migration 補格。 */
    private const GRID_POS = [
        0 => [1, 6], 1 => [1, 7], 2 => [2, 7], 3 => [3, 7], 4 => [4, 7], 5 => [5, 7],
        6 => [5, 8], 7 => [5, 9], 8 => [5, 10], 9 => [5, 11], 10 => [5, 12], 11 => [5, 13],
        12 => [6, 13], 13 => [7, 13], 14 => [7, 12], 15 => [7, 11], 16 => [7, 10], 17 => [7, 9],
        18 => [7, 8], 19 => [7, 7], 20 => [8, 7], 21 => [9, 7], 22 => [10, 7], 23 => [11, 7],
        24 => [11, 6], 25 => [11, 5], 26 => [10, 5], 27 => [9, 5], 28 => [8, 5], 29 => [7, 5],
        30 => [7, 4], 31 => [7, 3], 32 => [7, 2], 33 => [7, 1], 34 => [6, 1], 35 => [5, 1],
        36 => [5, 2], 37 => [5, 3], 38 => [5, 4], 39 => [5, 5], 40 => [4, 5], 41 => [3, 5],
        42 => [2, 5], 43 => [1, 5],
    ];

    /* ========================================================
       Board 1: 情侶飛行棋 V2.0 (original, is_default=true)
       ======================================================== */
    private const DEFAULT_SQUARES = [
        0 => ['text' => "起點\n擲骰子出發！",                  'color' => 'start'],
        1 => ['text' => '牽手對視20秒',                         'color' => 'move'],
        2 => ['text' => '喝一口再抱一下',                       'color' => 'drink'],
        3 => ['text' => '親對方耳朵10秒',                       'color' => 'action'],
        4 => ['text' => "後退2格\n說一句撩人的話",              'color' => 'move'],
        5 => ['text' => "轉角\n停下來親對方 10 秒", 'color' => 'action'],
        6 => ['text' => "大冒險！\n讓對方指定親哪裡",           'color' => 'dare'],
        7 => ['text' => "從嘴唇一路親到\n對方的鎖骨",            'color' => 'strip',  'fly_to' => 10],
        8 => ['text' => "真心話\n第一次想睡對方是何時",         'color' => 'truth'],
        9 => ['text' => "用嘴餵對方\n喝一口酒",                 'color' => 'drink'],
        10 => ['text' => "親脖子種草莓\n再前進3格",               'color' => 'action', 'fly_to' => 12],
        11 => ['text' => "下一輪休息\n跳過下次擲骰",             'color' => 'move'],
        12 => ['text' => "舌吻對方\n整整1分鐘",                 'color' => 'action'],
        13 => ['text' => "大冒險！\n脫掉一件衣物",               'color' => 'dare'],
        14 => ['text' => '♀ 拍一張性感照片給對方',              'color' => 'female'],
        15 => ['text' => "隔著內褲摸對方\n30秒",                'color' => 'action'],
        16 => ['text' => "真心話\n最想被對方怎麼玩",             'color' => 'truth'],
        17 => ['text' => '喝半杯再脫一件',                       'color' => 'drink'],
        18 => ['text' => "♂ 貼著對方熱舞\n1分鐘",               'color' => 'male'],
        19 => ['text' => "轉角\n幫對方脫掉一件外層衣物", 'color' => 'strip'],
        20 => ['text' => "手伸進衣服裡\n摸胸或屁股1分鐘",          'color' => 'action', 'fly_to' => 22],
        21 => ['text' => "坐到對方腿上\n磨蹭30秒",                'color' => 'strip'],
        22 => ['text' => "用嘴挑逗對方\n隔著內褲30秒",            'color' => 'action'],
        23 => ['text' => "打屁股5下\n力道讓對方選",              'color' => 'dare'],
        24 => ['text' => "把對方壓在床上\n深吻1分鐘",             'color' => 'action'],
        25 => ['text' => "後退3格\n幫對方脫掉內褲",              'color' => 'move'],
        26 => ['text' => "露出私處\n讓對方看30秒",               'color' => 'strip'],
        27 => ['text' => "用手幫對方刺激\n1分鐘",                'color' => 'action'],
        28 => ['text' => "舔大腿內側直到\n對方喊停",               'color' => 'action'],
        29 => ['text' => "轉角\n從背後抱住對方 30 秒", 'color' => 'action'],
        30 => ['text' => "幫對方乳交\n1分鐘",                    'color' => 'action'],
        31 => ['text' => "♀ 坐到對方臉上\n磨蹭30秒",              'color' => 'female'],
        32 => ['text' => '喝一口再口交30秒',                     'color' => 'drink'],
        33 => ['text' => "幫對方口交\n2分鐘",                    'color' => 'action'],
        34 => ['text' => "大冒險！\n用手指進去玩1分鐘",           'color' => 'dare'],
        35 => ['text' => "挑一樣情趣玩具\n玩2分鐘",              'color' => 'action'],
        36 => ['text' => "真心話\n說出最想玩的體位",             'color' => 'truth'],
        37 => ['text' => "戴好保險套\n由對方選體位",              'color' => 'action'],
        38 => ['text' => "照選好的體位\n進去動1分鐘",              'color' => 'action'],
        39 => ['text' => "轉角\n說出你現在最想被碰的地方", 'color' => 'truth'],
        40 => ['text' => '換個體位再做1分鐘',                    'color' => 'drink'],
        41 => ['text' => '從後面做30下',                         'color' => 'move'],
        42 => ['text' => '對方說多快多深都照做',                 'color' => 'strip'],
        43 => ['text' => "終點\n想怎麼做就做3分鐘",             'color' => 'end'],
    ];

    /* ========================================================
       Board 2: 輕度暖身版 (romantic, mild)
       ======================================================== */
    private const WARMUP_SQUARES = [
        0 => ['text' => "起點\n出發囉！",                       'color' => 'start'],
        1 => ['text' => '前進2格',                              'color' => 'move'],
        2 => ['text' => "輕抱對方\n30秒",                       'color' => 'action'],
        3 => ['text' => "真心話\n說一件喜歡對方的事",           'color' => 'truth'],
        4 => ['text' => '親吻對方額頭',                         'color' => 'action'],
        5 => ['text' => "轉角\n牽著手對視 15 秒", 'color' => 'action'],
        6 => ['text' => "大冒險！\n由對方出題（溫和版）",       'color' => 'dare'],
        7 => ['text' => "握著對方的手\n說一句甜蜜的話",         'color' => 'action'],
        8 => ['text' => "真心話\n說出第一次見面的感覺",         'color' => 'truth'],
        9 => ['text' => '後退1格',                              'color' => 'move'],
        10 => ['text' => "幫對方按摩肩膀\n1分鐘",                'color' => 'action'],
        11 => ['text' => '跳過一輪',                             'color' => 'move'],
        12 => ['text' => '親吻對方臉頰',                         'color' => 'action'],
        13 => ['text' => '大冒險！',                             'color' => 'dare'],
        14 => ['text' => "♀ 女生\n撒嬌說一句話",                'color' => 'female'],
        15 => ['text' => "輕撫對方頭髮\n30秒",                   'color' => 'action'],
        16 => ['text' => "真心話\n最近最開心的一件事",           'color' => 'truth'],
        17 => ['text' => '前進1格',                              'color' => 'move'],
        18 => ['text' => "♂ 男生\n說一句讚美的話",              'color' => 'male'],
        19 => ['text' => "轉角\n說一句你今天沒說出口的話", 'color' => 'truth'],
        20 => ['text' => "對方出題\n唱一首情歌片段",             'color' => 'dare'],
        21 => ['text' => "互相對視\n10秒不說話",                 'color' => 'action'],
        22 => ['text' => "拿手機\n選一張最喜歡的合照",           'color' => 'action'],
        23 => ['text' => "大冒險！\n扮鬼臉逗對方笑",            'color' => 'dare'],
        24 => ['text' => "坐到對方腿上\n貼緊30秒",                'color' => 'action'],
        25 => ['text' => '後退2格',                              'color' => 'move'],
        26 => ['text' => '說出對方最可愛的小習慣',               'color' => 'truth'],
        27 => ['text' => '幫對方整理頭髮',                       'color' => 'action'],
        28 => ['text' => '前進2格',                              'color' => 'move'],
        29 => ['text' => "轉角\n從背後輕輕抱住對方", 'color' => 'action'],
        30 => ['text' => "互相說一個\n小秘密",                   'color' => 'truth'],
        31 => ['text' => "做一個\n愛心手勢",                     'color' => 'action'],
        32 => ['text' => "真心話\n最想一起去的地方",             'color' => 'truth'],
        33 => ['text' => "一起唱\n生日快樂歌",                   'color' => 'action'],
        34 => ['text' => "大冒險！\n模仿對方走路",               'color' => 'dare'],
        35 => ['text' => "誇獎對方\n外表一個優點",               'color' => 'action'],
        36 => ['text' => "真心話\n說出最想要的禮物",             'color' => 'truth'],
        37 => ['text' => "手牽手\n走一圈",                       'color' => 'action'],
        38 => ['text' => '後退1格',                              'color' => 'move'],
        39 => ['text' => "轉角\n親一下對方自己指的位置", 'color' => 'dare'],
        40 => ['text' => '前進1格',                              'color' => 'move'],
        41 => ['text' => "說出一個\n約會夢想清單",               'color' => 'truth'],
        42 => ['text' => "對方親你\n一下",                       'color' => 'action'],
        43 => ['text' => "終點\n抱著對方說今晚還想繼續",         'color' => 'end'],
    ];

    /* ========================================================
       Board 3: 飲酒開嗨版 (drinking game focused)
       ======================================================== */
    private const DRINKING_SQUARES = [
        0 => ['text' => "起點\n乾杯開始！",                     'color' => 'start'],
        1 => ['text' => '喝一口',                               'color' => 'drink'],
        2 => ['text' => '前進2格',                              'color' => 'move'],
        3 => ['text' => "真心話\n說出最近喝掛的故事",           'color' => 'truth'],
        4 => ['text' => '喝半杯',                               'color' => 'drink'],
        5 => ['text' => "轉角\n和對方乾一杯", 'color' => 'drink'],
        6 => ['text' => "大冒險！\n學動物叫",                   'color' => 'dare'],
        7 => ['text' => "喝一口\n並往前跑一格",                 'color' => 'drink',  'fly_to' => 7],
        8 => ['text' => "真心話\n說出最不想被問的事",           'color' => 'truth'],
        9 => ['text' => "罰喝1杯\n大輸家！",                    'color' => 'drink'],
        10 => ['text' => "大冒險！\n比賽喝最快",                 'color' => 'dare'],
        11 => ['text' => '跳過一輪',                             'color' => 'move'],
        12 => ['text' => '喝一口',                               'color' => 'drink'],
        13 => ['text' => "大冒險！\n用腳夾東西走路",             'color' => 'dare'],
        14 => ['text' => "♀ 女生\n幫男生倒酒",                  'color' => 'female'],
        15 => ['text' => '喝半杯',                               'color' => 'drink'],
        16 => ['text' => "真心話\n說出最想去的地方",             'color' => 'truth'],
        17 => ['text' => '後退2格',                              'color' => 'move'],
        18 => ['text' => "♂ 男生\n乾一杯",                      'color' => 'male'],
        19 => ['text' => "轉角\n說出在場你最想灌醉的人", 'color' => 'truth'],
        20 => ['text' => "大冒險！\n模仿對方喝酒",               'color' => 'dare'],
        21 => ['text' => "喝一口\n說出一個秘密",                 'color' => 'drink'],
        22 => ['text' => '前進1格',                              'color' => 'move'],
        23 => ['text' => "大冒險！\n兩人輪流喝",                 'color' => 'dare'],
        24 => ['text' => "坐到對方腿上\n喝一口再親20秒",           'color' => 'action'],
        25 => ['text' => '後退2格',                              'color' => 'move'],
        26 => ['text' => '喝兩口',                               'color' => 'drink'],
        27 => ['text' => '前進2格',                              'color' => 'move'],
        28 => ['text' => "大冒險！\n唱廣告歌",                   'color' => 'dare'],
        29 => ['text' => "轉角\n輸的人喝一口", 'color' => 'drink'],
        30 => ['text' => '喝一口',                               'color' => 'drink'],
        31 => ['text' => "真心話\n說最近最尷尬的事",             'color' => 'truth'],
        32 => ['text' => "罰喝\n若說不出，喝一口",               'color' => 'drink'],
        33 => ['text' => "大冒險！\n原地旋轉5圈再走",            'color' => 'dare'],
        34 => ['text' => '喝半杯',                               'color' => 'drink'],
        35 => ['text' => '前進1格',                              'color' => 'move'],
        36 => ['text' => "真心話\n今天最想說的話",               'color' => 'truth'],
        37 => ['text' => '喝一口',                               'color' => 'drink'],
        38 => ['text' => "大冒險！\n雙手背後開瓶蓋",             'color' => 'dare'],
        39 => ['text' => "轉角\n讓對方指定你做一件事", 'color' => 'dare'],
        40 => ['text' => '後退1格',                              'color' => 'move'],
        41 => ['text' => '喝一口',                               'color' => 'drink'],
        42 => ['text' => "大冒險！\n連說5個繞口令",              'color' => 'dare'],
        43 => ['text' => "終點\n喝一口再抱緊對方",              'color' => 'end'],
    ];

    private function seedBoard(
        string $name,
        string $description,
        bool $isDefault,
        array $squares,
        ?string $referenceImage = null,
        bool $isPremium = false,
        bool $hasStartWheel = false,
    ): void {
        if (!$isPremium) {
            $squares = $this->adultFreeSquares($squares);
        }

        $board = Board::firstOrCreate(
            ['name' => $name],
            [
                'description' => $description,
                'reference_image' => $referenceImage,
                'is_default' => $isDefault,
                'is_template' => $isPremium,
                'is_premium_template' => $isPremium,
                'canvas_rows' => 11,
                'canvas_cols' => 13,
                'path_data' => ['all' => range(0, count($squares) - 1), 'male' => null, 'female' => null],
                'start_wheel' => $hasStartWheel
                    ? ['enabled' => true, 'segments' => Board::DEFAULT_START_WHEEL]
                    : null,
                'user_id' => null,
            ]
        );
        if ($referenceImage && $board->reference_image !== $referenceImage) {
            $board->update(['reference_image' => $referenceImage]);
        }
        if ($board->is_default !== $isDefault
            || $board->is_template !== $isPremium
            || $board->is_premium_template !== $isPremium) {
            $board->update([
                'is_default' => $isDefault,
                'is_template' => $isPremium,
                'is_premium_template' => $isPremium,
            ]);
        }
        $board->update([
            'path_data' => ['all' => range(0, count($squares) - 1), 'male' => null, 'female' => null],
            'start_wheel' => $hasStartWheel
                ? ['enabled' => true, 'segments' => Board::DEFAULT_START_WHEEL]
                : null,
        ]);

        // Create a missing board once; otherwise keep the existing geometry and
        // effects intact while synchronising editorial content.
        if ($board->squares()->count() === 0) {
            foreach ($squares as $pos => $data) {
                [$row, $col] = self::GRID_POS[$pos];
                BoardSquare::create([
                    'board_id' => $board->id,
                    'position' => $pos,
                    'text' => $data['text'],
                    'color' => $data['color'],
                    'fly_to' => $data['fly_to'] ?? null,
                    'grid_row' => $row,
                    'grid_col' => $col,
                ]);
            }
        } else {
            foreach ($squares as $pos => $data) {
                $board->squares()
                    ->where('position', $pos)
                    ->update([
                        'text' => $data['text'],
                        'color' => $data['color'],
                    ]);
            }
        }

        $board->squares()->whereNotIn('position', array_keys($squares))->delete();
    }

    /** Give every free system board an adult tone that rises gradually. */
    private function adultFreeSquares(array $squares): array
    {
        $pools = [
            1 => [
                '盯著對方放電15秒', '牽手貼近說一句撩人的話', '從臉頰慢慢親到耳邊',
                '從背後抱緊對方20秒', '親對方脖子10秒', '說出對方最性感的地方',
            ],
            2 => [
                '舌吻對方30秒', '坐到對方腿上貼緊20秒', '隔著衣服摸胸口20秒',
                '從肩膀摸到腰30秒', '親到鎖骨再停10秒', '幫對方脫一件外層衣物',
            ],
            3 => [
                '慢慢脫掉自己一件衣物', '按摩大腿內側30秒', '手伸進衣襬摸腰30秒',
                '隔著衣物磨蹭30秒', '讓對方親一個敏感部位20秒', '貼著耳朵說今晚最想做什麼',
            ],
            4 => [
                '再脫一件衣物，不用一次脫光', '隔著內褲摸私密處30秒', '親大腿內側30秒',
                '手伸進內褲挑逗20秒', '跨坐磨蹭30秒', '互相說出一個想玩的成人任務',
            ],
        ];

        $last = array_key_last($squares);

        foreach ($squares as $position => &$square) {
            if ($position === 0) {
                $square['text'] = "起點\n先從調情慢慢升溫";
                $square['color'] = 'start';
                continue;
            }

            if ($position === $last) {
                $square['text'] = "終點\n抱緊對方，接下來自己決定";
                $square['color'] = 'end';
                continue;
            }

            $stage = min(4, max(1, (int) ceil($position / max(1, $last) * 4)));
            $adult = $pools[$stage][($position - 1) % count($pools[$stage])];
            $original = $square['text'];
            $square['text'] = in_array($square['color'], ['move', 'drink'], true)
                ? $original."\n再做：".$adult
                : $adult;
        }

        unset($square);

        return $squares;
    }

    public function run(): void
    {
        $this->seedBoard(
            '情侶飛行棋 V2.0',
            '雙人同機情趣版（十字棋盤 40格）——起點在頂端，終點在底端，支援飛行格、男女專屬格',
            false,
            self::DEFAULT_SQUARES,
            'images/board-references/couples-flying-chess-v8.jpg',
            true,
            true,
        );

        $this->seedBoard(
            '輕度暖身版',
            '溫馨甜蜜風格，適合剛開始約會或想來點浪漫互動的情侶（40格，十字棋盤）',
            true,
            self::WARMUP_SQUARES,
            null,
            false,
            false,
        );

        $this->seedBoard(
            '飲酒開嗨版',
            '以喝酒罰則為主題，歡樂派對必備！適合多人聚會或好友一起玩（40格，十字棋盤）',
            false,
            self::DRINKING_SQUARES,
            null,
            false,
            true,
        );
    }
}
