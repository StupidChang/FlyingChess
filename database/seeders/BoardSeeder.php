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
        0 => ['text' => "起點\n擲骰子出發！", 'color' => 'start'],
        1 => ['text' => '對視20秒，先笑的人親另一半一下', 'color' => 'move'],
        2 => ['text' => '喝一口，嘴裡含著酒去親他', 'color' => 'drink'],
        3 => ['text' => '輕咬他的耳垂，說一句今晚想做的事', 'color' => 'action'],
        4 => ['text' => "後退2格\n學他平常撒嬌的語氣，跟他討一個吻", 'color' => 'move'],
        5 => ['text' => "轉角\n咬住他的下唇，3秒後再放開", 'color' => 'action'],
        6 => ['text' => "大冒險！\n讓他指一個地方，你親到他說夠", 'color' => 'dare'],
        7 => ['text' => "猜拳\n輸的人脫上衣，贏的人親他鎖骨", 'color' => 'strip',  'fly_to' => 10],
        8 => ['text' => "真心話\n你第一次想著我自慰是什麼時候？", 'color' => 'truth'],
        9 => ['text' => "用嘴餵對方\n喝一口酒", 'color' => 'drink'],
        10 => ['text' => "親脖子種草莓\n再前進3格", 'color' => 'action', 'fly_to' => 12],
        11 => ['text' => "下一輪休息\n跳過下次擲骰", 'color' => 'move'],
        12 => ['text' => "一邊舌吻\n一邊把手伸進衣服裡揉胸", 'color' => 'action'],
        13 => ['text' => "大冒險！\n脫一件，脫哪件由對方挑", 'color' => 'dare'],
        14 => ['text' => '♀ 解開胸罩，把乳頭送到他嘴邊', 'color' => 'female'],
        15 => ['text' => "隔著內褲摸他\n摸到他硬了或濕了", 'color' => 'action'],
        16 => ['text' => "真心話\n你最近一次自己弄，腦子裡想的是誰？", 'color' => 'truth'],
        17 => ['text' => '喝半杯，不想喝就脫一件', 'color' => 'drink'],
        18 => ['text' => "♂ 掏出肉棒\n讓對方從根部套到龜頭，力道對方說", 'color' => 'male'],
        19 => ['text' => "轉角\n互相脫掉內褲，誰先遮誰喝一口", 'color' => 'strip'],
        20 => ['text' => "手伸進內褲\n揉他私處，他叫出聲才能停", 'color' => 'action', 'fly_to' => 22],
        21 => ['text' => "全裸跨坐在他腿上\n私處貼著私處磨", 'color' => 'strip'],
        22 => ['text' => "張開他的腿\n從大腿內側一路舔上去", 'color' => 'action'],
        23 => ['text' => "打屁股5下\n力道讓對方選", 'color' => 'dare'],
        24 => ['text' => "把他壓在床上\n手指伸入，勾到他腿軟", 'color' => 'action'],
        25 => ['text' => "後退3格\n跪下來幫他口交，他說停才停", 'color' => 'move'],
        26 => ['text' => "躺好張開腿\n讓他舔，你只能說快或慢", 'color' => 'action'],
        27 => ['text' => "躺成69\n誰先停下來誰喝一杯", 'color' => 'action'],
        28 => ['text' => "戴好保險套\n傳教士，一邊插一邊接吻", 'color' => 'action'],
        29 => ['text' => "轉角\n側躺從後面進去，貼著耳朵說感覺", 'color' => 'action'],
        30 => ['text' => "幫對方乳交\n1分鐘", 'color' => 'action'],
        31 => ['text' => "♀ 騎上去\n自己決定吃多深，動30下", 'color' => 'female'],
        32 => ['text' => '喝一口含在嘴裡，再幫他口交', 'color' => 'drink'],
        33 => ['text' => "換後入\n被插的人喊「還要」才准繼續", 'color' => 'action'],
        34 => ['text' => "大冒險！\n插到最深，停10秒不准動", 'color' => 'dare'],
        35 => ['text' => "一邊插\n一邊拿玩具抵著前面", 'color' => 'action'],
        36 => ['text' => "真心話\n哪個體位你一直不好意思開口？", 'color' => 'truth'],
        37 => ['text' => "就是你剛說的那個\n現在換過去做", 'color' => 'action'],
        38 => ['text' => "站著從後面插\n扶著牆做到他腿軟", 'color' => 'action'],
        39 => ['text' => "轉角\n你想要我射在哪裡？說了不能改", 'color' => 'truth'],
        40 => ['text' => '換個體位，不想換就喝一杯', 'color' => 'drink'],
        41 => ['text' => '坐到椅子上，讓他坐上來自己動', 'color' => 'move'],
        42 => ['text' => "壓住他的手\n多快多深都由你決定", 'color' => 'action'],
        43 => ['text' => "終點\n做到兩個人都到，先到的人明天做早餐", 'color' => 'end'],
    ];

    /* ========================================================
       Board 2: 輕度暖身版 (romantic, mild)
       ======================================================== */
    private const WARMUP_SQUARES = [
        0 => ['text' => "起點\n先各說一件今晚不行的事，再擲骰子", 'color' => 'start'],
        1 => ['text' => "前進2格\n再做：盯著另一半看 15 秒，先笑的人親對方一下", 'color' => 'move'],
        2 => ['text' => '用嘴唇從對方的下巴蹭到耳朵，就是不親下去', 'color' => 'action'],
        3 => ['text' => '你第一次想跟我上床是哪一天？那天我穿什麼？', 'color' => 'truth'],
        4 => ['text' => '在另一半手心寫一個字，猜錯的人脫一隻襪子', 'color' => 'action'],
        5 => ['text' => "轉角\n牽著手對視 15 秒，先移開視線的人說一句色的話", 'color' => 'action'],
        6 => ['text' => '學對方平常撒嬌的語氣，跟他討一個吻', 'color' => 'dare'],
        7 => ['text' => '從背後抱住另一半，跟他說今天哪一刻突然很想要他', 'color' => 'action'],
        8 => ['text' => '你上次偷看我換衣服是什麼時候？看到哪裡了？', 'color' => 'truth'],
        9 => ['text' => "後退1格\n再做：親對方的脖子，親到他縮起來為止", 'color' => 'move'],
        10 => ['text' => '舌吻，數到 30 才准分開', 'color' => 'action'],
        11 => ['text' => "跳過一輪\n再做：用嘴咬開另一半的一顆釦子", 'color' => 'move'],
        12 => ['text' => '你最喜歡我穿什麼上床？今晚有穿嗎？', 'color' => 'truth'],
        13 => ['text' => '幫對方脫掉最外面那一件，只能用一隻手', 'color' => 'dare'],
        14 => ['text' => "♀ 女生\n拉起他的手，放到你現在最想被摸的地方", 'color' => 'female'],
        15 => ['text' => '坐到另一半腿上面對面磨 30 秒，先喊停的人脫一件', 'color' => 'action'],
        16 => ['text' => '你比較想被我慢慢脫，還是自己脫給我看？', 'color' => 'truth'],
        17 => ['text' => "前進1格\n再做：手伸進衣襬，貼著皮膚摸到胸口", 'color' => 'move'],
        18 => ['text' => "♂ 男生\n把另一半壓到牆邊親，親到對方先投降", 'color' => 'male'],
        19 => ['text' => "轉角\n說出你最想舔我身上哪裡，說完就去舔", 'color' => 'truth'],
        20 => ['text' => '幫另一半再脫一件，這次你挑，他不能反對', 'color' => 'dare'],
        21 => ['text' => '解開對方的內衣，含住乳頭 20 秒', 'color' => 'action'],
        22 => ['text' => '一邊揉胸一邊親大腿內側，越親越上面', 'color' => 'action'],
        23 => ['text' => '猜拳，輸的人脫到只剩內褲，贏的人坐著看', 'color' => 'dare'],
        24 => ['text' => '兩個人都只剩內衣褲，貼在一起磨 30 秒', 'color' => 'action'],
        25 => ['text' => "後退2格\n再做：讓另一半挑一個地方，你要舔那裡 20 秒", 'color' => 'move'],
        26 => ['text' => '你現在比較想被摸上面還是下面？選了就不能改', 'color' => 'truth'],
        27 => ['text' => '舔對方的乳頭，另一隻手慢慢滑到褲頭', 'color' => 'action'],
        28 => ['text' => "前進2格\n再做：隔著內褲用手掌貼住另一半，感覺他的反應", 'color' => 'move'],
        29 => ['text' => "轉角\n從背後抱住，手伸進內褲摸他 30 秒", 'color' => 'action'],
        30 => ['text' => '你現在硬了還是濕了？讓對方用手檢查', 'color' => 'truth'],
        31 => ['text' => '幫另一半脫掉內褲，讓他躺好把腿打開 10 秒', 'color' => 'action'],
        32 => ['text' => '用手幫對方，從慢到快，他喊停你才能停', 'color' => 'dare'],
        33 => ['text' => '換他用手幫你，你只能說「快一點」或「慢一點」', 'color' => 'action'],
        34 => ['text' => '幫另一半口交 30 秒', 'color' => 'dare'],
        35 => ['text' => '換他幫你口交，你要一直看著他', 'color' => 'action'],
        36 => ['text' => '你想要我用嘴幫你到快不行，還是直接進去？', 'color' => 'truth'],
        37 => ['text' => '戴好保險套，讓對方慢慢坐下去', 'color' => 'action'],
        38 => ['text' => "後退1格\n再做：騎在上面，自己決定要多深，動 20 下", 'color' => 'move'],
        39 => ['text' => "轉角\n換後入，從後面插 30 下，數出聲", 'color' => 'dare'],
        40 => ['text' => "前進1格\n再做：換傳教士，一邊插一邊接吻 1 分鐘", 'color' => 'move'],
        41 => ['text' => '想被怎麼做就大聲說出來，另一半照做 1 分鐘', 'color' => 'action'],
        42 => ['text' => '做到其中一個人高潮，先到的人要說出來', 'color' => 'action'],
        43 => ['text' => "終點\n高潮之後誰都不准先起來，抱著躺 1 分鐘", 'color' => 'end'],
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
            // 說明也是內容,跟格子一樣以 seeder 為準(firstOrCreate 只在建立時寫一次)
            'description' => $description,
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
            '雙人同機情趣版（十字棋盤）：從對視、猜拳脫衣一路到口交、換體位，最後做到兩個人都到',
            false,
            self::DEFAULT_SQUARES,
            'images/board-references/couples-flying-chess-v8.jpg',
            true,
            true,
        );

        $this->seedBoard(
            '輕度暖身版',
            '站上的標準線，也是新建棋盤的起點：猜拳、脫衣、摸到下面，最後口交、戴套一路做到高潮',
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
