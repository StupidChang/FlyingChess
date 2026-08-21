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
        0 => ['text' => "起點\n從最輕的開始，慢慢往上", 'color' => 'start'],
        1 => ['text' => "前進2格\n再做：盯著對方看 15 秒，誰先笑誰輸", 'color' => 'move'],
        2 => ['text' => '十指交扣，額頭抵著額頭 20 秒', 'color' => 'action'],
        3 => ['text' => '你今天最想從我這裡拿到什麼？', 'color' => 'truth'],
        4 => ['text' => '親對方的臉頰，然後停在耳邊呵一口氣', 'color' => 'action'],
        5 => ['text' => "轉角\n牽著手對視 15 秒", 'color' => 'action'],
        6 => ['text' => '在對方耳邊說一句你平常不好意思說的話', 'color' => 'dare'],
        7 => ['text' => '從背後抱住對方，手扣在腰上 20 秒', 'color' => 'action'],
        8 => ['text' => '我身上你最喜歡看的是哪裡？', 'color' => 'truth'],
        9 => ['text' => "後退1格\n再做：親對方的脖子 10 秒", 'color' => 'move'],
        10 => ['text' => '舌吻，數到 30 才准分開', 'color' => 'action'],
        11 => ['text' => "跳過一輪\n再做：從肩膀一路摸到腰，30 秒", 'color' => 'move'],
        12 => ['text' => '親到鎖骨，停在那裡 10 秒', 'color' => 'action'],
        13 => ['text' => '幫對方脫掉最外面那一件', 'color' => 'dare'],
        14 => ['text' => "♀ 女生\n指出你現在最想被碰的一個地方", 'color' => 'female'],
        15 => ['text' => '坐到對方腿上，面對面貼著 30 秒', 'color' => 'action'],
        16 => ['text' => '你比較喜歡我主動，還是等你來？', 'color' => 'truth'],
        17 => ['text' => "前進1格\n再做：隔著衣服摸對方的胸口 20 秒", 'color' => 'move'],
        18 => ['text' => "♂ 男生\n說出你現在最想做的一件事", 'color' => 'male'],
        19 => ['text' => "轉角\n說一句你今天沒說出口的話", 'color' => 'truth'],
        20 => ['text' => '幫對方再脫一件，這次自己選', 'color' => 'dare'],
        21 => ['text' => '用嘴含住對方的乳頭，數 20 秒', 'color' => 'action'],
        22 => ['text' => '按摩大腿內側 30 秒，越靠越裡面', 'color' => 'action'],
        23 => ['text' => '手伸進衣襬，貼著皮膚摸到胸口', 'color' => 'dare'],
        24 => ['text' => '隔著衣物磨蹭 30 秒', 'color' => 'action'],
        25 => ['text' => "後退2格\n再做：讓對方指一個地方，你要親那裡 20 秒", 'color' => 'move'],
        26 => ['text' => '貼著耳朵，說出你今晚最想做的一件事', 'color' => 'truth'],
        27 => ['text' => '慢慢脫掉自己一件，讓對方看著', 'color' => 'action'],
        28 => ['text' => "前進2格\n再做：隔著內褲用手掌貼住對方 20 秒", 'color' => 'move'],
        29 => ['text' => "轉角\n從背後抱住對方，手往下放", 'color' => 'action'],
        30 => ['text' => '你現在硬了／濕了嗎？', 'color' => 'truth'],
        31 => ['text' => '親大腿內側，一路往上但先停住', 'color' => 'action'],
        32 => ['text' => '接下來手還是嘴，你挑一個', 'color' => 'truth'],
        33 => ['text' => '幫對方脫到只剩內衣褲', 'color' => 'action'],
        34 => ['text' => '隔著內褲，用手指沿著私密處的形狀慢慢描 30 秒', 'color' => 'dare'],
        35 => ['text' => '隔著內褲用嘴呵氣，再隔著布料舔一下', 'color' => 'action'],
        36 => ['text' => '剛剛哪一下最有感覺？', 'color' => 'truth'],
        37 => ['text' => '換人，換他隔著內褲對你做一樣的事', 'color' => 'action'],
        38 => ['text' => "後退1格\n再做：跨坐上去，貼著磨 30 秒", 'color' => 'move'],
        39 => ['text' => "轉角\n說出你今晚最想被怎麼對待", 'color' => 'dare'],
        40 => ['text' => "前進1格\n再做：照他剛說的，做給他看 1 分鐘", 'color' => 'move'],
        41 => ['text' => '棋盤到這裡就停了 —— 你想停在這，還是自己接下去？', 'color' => 'truth'],
        42 => ['text' => '抱著對方，什麼都不做 30 秒', 'color' => 'action'],
        43 => ['text' => "終點\n棋盤到這裡結束，剩下的自己決定", 'color' => 'end'],
    ];

    /* ========================================================
       Board 3: 飲酒開嗨版 (drinking game focused)
       ======================================================== */
    private const DRINKING_SQUARES = [
        0 => ['text' => "起點\n規則：每一格可以選做，或選喝", 'color' => 'start'],
        1 => ['text' => "喝一口\n再做：盯著對方看 15 秒，先笑的再喝一口", 'color' => 'drink'],
        2 => ['text' => "前進2格\n再做：在對方耳邊講一句撩人的話", 'color' => 'move'],
        3 => ['text' => '你喝多之後最容易對誰動手動腳？', 'color' => 'truth'],
        4 => ['text' => "喝半杯\n再做：從背後抱住對方 20 秒", 'color' => 'drink'],
        5 => ['text' => "轉角\n親對方 10 秒，不親就喝一口", 'color' => 'action'],
        6 => ['text' => '親對方的脖子 10 秒，留不留痕跡自己決定', 'color' => 'dare'],
        7 => ['text' => "喝一口\n並往前跑一格\n再做：說出對方身上最讓你想咬一口的地方", 'color' => 'drink', 'fly_to' => 7],
        8 => ['text' => '你最近一次喝完就直接上床是什麼時候？', 'color' => 'truth'],
        9 => ['text' => "罰喝1杯\n大輸家！\n再做：讓贏的人親你一個地方", 'color' => 'drink'],
        10 => ['text' => '用嘴把一口酒餵給對方', 'color' => 'dare'],
        11 => ['text' => "跳過一輪\n再做：從肩膀摸到腰，中途停手就喝一口", 'color' => 'move'],
        12 => ['text' => "喝一口\n再做：親到鎖骨停住 10 秒，先動的再喝", 'color' => 'drink'],
        13 => ['text' => '幫對方脫一件，不脫就乾一杯', 'color' => 'dare'],
        14 => ['text' => "♀ 女生\n舌吻對方 30 秒，或連喝兩口", 'color' => 'female'],
        15 => ['text' => "喝半杯\n再做：坐到對方腿上貼緊 20 秒", 'color' => 'drink'],
        16 => ['text' => '你喝醉之後最想被怎麼對待？', 'color' => 'truth'],
        17 => ['text' => "後退2格\n再做：隔著衣服摸胸口 20 秒，不摸就喝半杯", 'color' => 'move'],
        18 => ['text' => "♂ 男生\n說出你現在最想脫掉對方哪一件", 'color' => 'male'],
        19 => ['text' => "轉角\n幫對方脫掉一件，不脫的人喝一口", 'color' => 'strip'],
        20 => ['text' => '用冰塊在對方身上滑一圈，融化前不准停', 'color' => 'dare'],
        21 => ['text' => "喝一口\n說出一個秘密\n再做：舌吻對方 30 秒，分開就再喝", 'color' => 'drink'],
        22 => ['text' => "前進1格\n再做：按摩大腿內側 30 秒，手抖了就喝", 'color' => 'move'],
        23 => ['text' => '手伸進衣襬，貼著皮膚一路摸到胸口', 'color' => 'dare'],
        24 => ['text' => '隔著衣物磨蹭 30 秒，笑場的喝一口', 'color' => 'action'],
        25 => ['text' => "後退2格\n再做：對方指一個地方，你親 20 秒，不親喝一杯", 'color' => 'move'],
        26 => ['text' => "喝兩口\n再做：貼著耳朵說出你今晚想做的事", 'color' => 'drink'],
        27 => ['text' => "前進2格\n再做：慢慢脫掉自己一件", 'color' => 'move'],
        28 => ['text' => '含住對方的乳頭 20 秒，做不到就喝兩口', 'color' => 'dare'],
        29 => ['text' => "轉角\n從背後抱住，手往下放，放不下去就喝", 'color' => 'action'],
        30 => ['text' => "喝一口\n再做：隔著內褲用手掌貼住 20 秒，抽手就喝", 'color' => 'drink'],
        31 => ['text' => '你現在硬了／濕了嗎？說謊被抓到罰一杯', 'color' => 'truth'],
        32 => ['text' => "罰喝\n若說不出，喝一口\n再做：說出你今晚最想被怎麼對待，說不出來就再喝", 'color' => 'drink'],
        33 => ['text' => '幫對方脫到只剩內衣褲，不脫就自己乾一杯', 'color' => 'dare'],
        34 => ['text' => "喝半杯\n再做：隔著內褲用手指描 30 秒，抽手就喝", 'color' => 'drink'],
        35 => ['text' => "前進1格\n再做：隔著內褲用嘴呵氣 20 秒，笑場罰一杯", 'color' => 'move'],
        36 => ['text' => '剛剛那一分鐘，你腦袋裡在想什麼？', 'color' => 'truth'],
        37 => ['text' => "喝一口\n再做：換他隔著內褲對你做一樣的事，出聲就喝", 'color' => 'drink'],
        38 => ['text' => '跨坐上去磨 30 秒，站起來就喝', 'color' => 'dare'],
        39 => ['text' => "轉角\n說出你現在最想被摸哪裡，說謊罰一杯", 'color' => 'truth'],
        40 => ['text' => "後退1格\n再做：照他剛說的地方摸 1 分鐘，停手就喝", 'color' => 'move'],
        41 => ['text' => "喝一口\n再做：兩個人都脫到只剩內衣褲，誰不脫誰喝", 'color' => 'drink'],
        42 => ['text' => '從背後抱住對方磨 30 秒，數出聲', 'color' => 'dare'],
        43 => ['text' => "終點\n酒還有，棋盤玩完了，剩下的自己談", 'color' => 'end'],
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
            '成人漸進版｜站上的標準線，也是新建棋盤的起點：從對視、耳語一路走到口交與插入，44 格走完一輪',
            true,
            self::WARMUP_SQUARES,
            null,
            false,
            false,
        );

        $this->seedBoard(
            '飲酒開嗨版',
            '成人漸進版｜酒是機制不是裝飾：每一格都是「做，或者喝」二選一，一路賭到最後',
            false,
            self::DRINKING_SQUARES,
            null,
            false,
            true,
        );
    }
}
