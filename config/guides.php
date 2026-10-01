<?php

/*
 * 站內文章(玩法指南)的骨架。
 *
 * 和 config/traits.php、config/horny.php 同一套做法:結構在這裡,文案全部在
 * lang/{locale}/guides.php,兩邊用同一組 key(文章的 slug)對起來。
 *
 * 為什麼不用資料庫:文章量在二十篇以內的時候,檔案的優點壓倒性 —— 進版控、可以
 * code review、不用 migration、不用後台、不用擔心 UGC 審核。哪天要交給別人寫、
 * 或篇數上百,再搬進資料庫。
 *
 * ── 關鍵字策略(改標題前務必先讀)──────────────────────────
 *
 * 這些文章吃的是**資訊型**意圖(「怎麼玩」「有什麼」「怎麼辦」),遊戲頁吃的是
 * **工具型**意圖(「我現在要玩」)。這條界線一定要守住:如果寫一篇「真心話大冒險」
 * 的文章,它會跟 /truth-dare 互相搶排名,兩邊都掉 —— 這就是 keyword
 * cannibalization,而且是自己造成的。
 *
 * 所以新增文章之前先問:站內有沒有頁面已經在吃這個字?有的話換一個意圖切入,
 * 或者去補強那一頁,不要新開一篇。
 *
 * related 是文章末尾「站內可以直接玩」那一區的路由名稱。這是文章存在的商業理由
 * (把資訊型流量帶進工具頁),但也只放在末尾與段落中 —— 不要進導覽列。
 */

return [

    /*
     * 文章清單。順序就是列表頁的顯示順序,把最想被看到的放前面。
     *
     *   updated   最後更新日期,顯示在文章上並餵給 Article schema 的 dateModified。
     *             改內容的時候要一起改 —— 掛著半年前的日期比沒有日期更糟。
     *   priority  sitemap 的權重。
     *   related   末尾推薦區的路由名稱(依序)。
     */
    'articles' => [
        'couple-home-games' => [
            'updated' => '2026-08-21',
            'priority' => '0.7',
            'related' => ['games.lobby', 'truth-dare.lobby', 'play'],
        ],
        'date-awkward-silence' => [
            'updated' => '2026-08-21',
            'priority' => '0.7',
            'related' => ['who-most-likely.show', 'truth-dare.lobby', 'trait-test.show'],
        ],
        'two-player-games' => [
            'updated' => '2026-08-21',
            'priority' => '0.7',
            'related' => ['play', 'games.lobby', 'card-game.show'],
        ],
        /* 紀念日、異地戀、性需求溝通。三篇都刻意避開站內已經有頁面在吃的字:
           寫一篇「真心話大冒險」會直接跟 /truth-dare 互搶,而「約會後怎麼加溫」
           會跟上面那篇 date-awkward-silence 的後半段重疊。 */
        'anniversary-at-home' => [
            'updated' => '2026-08-21',
            'priority' => '0.7',
            'related' => ['play', 'boards.templates', 'truth-dare.lobby'],
        ],
        'long-distance-couples' => [
            'updated' => '2026-08-21',
            'priority' => '0.7',
            'related' => ['trait-test.compare', 'play'],
        ],
        'talk-about-sex-needs' => [
            'updated' => '2026-08-21',
            'priority' => '0.8',
            'related' => ['horny-test.show', 'trait-test.show', 'truth-dare.lobby'],
        ],
    ],

    /*
     * 有翻譯的語系。沒列在這裡的語系,文章頁會退回繁中文案並標 noindex,而且
     * hreflang 只會宣告有翻譯的那幾個 —— 見 LocaleHelper::hreflangSet()。
     */
    'translated' => ['zh_TW', 'en', 'zh_CN', 'ja'],
];
