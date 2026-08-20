<?php

/*
 * 性壓抑指數測驗的骨架。
 *
 * 和 config/traits.php 同一套做法:這裡只放「結構」—— 面向代碼、題目歸屬、
 * 正反向。所有看得到的文字都在 lang/{locale}/repression.php,兩者用同一組 key
 * 對起來。分開的理由和屬性測驗一樣:
 *
 *   1. 翻譯是加上去的,不用碰計分邏輯。
 *   2. 正反向表(等於答案卷)只留在伺服器 —— 送到瀏覽器的只有題目文字。
 *      反向題一旦外流,這份測驗就可以被隨意刷成任何分數。
 *
 * 計分在 App\Services\RepressionTestService。
 *
 * 這份測驗和屬性測驗**刻意不重疊**:屬性測驗問的是「你偏好哪一種玩法」,
 * 這一份問的是「你有多容易對自己的慾望踩煞車」。前者是類型,後者是程度,
 * 所以結果頁是 5 個級距而不是 20 種屬性,關鍵字也不會互相吃掉。
 */

return [

    /*
     * 五個面向。指數是這五個的加權平均,權重目前一致 —— 有實際資料之後再調,
     * 不要先憑感覺加權,那只會讓分數難以解釋。
     *
     * colour 對應 app.css 既有的 tt-c-* 變數,結果頁的長條直接沿用。
     */
    'dimensions' => [
        'guilt' => ['weight' => 1, 'colour' => 'rose'],
        'shame' => ['weight' => 1, 'colour' => 'indigo'],
        'anxiety' => ['weight' => 1, 'colour' => 'gold'],
        'avoid' => ['weight' => 1, 'colour' => 'green'],
        'voice' => ['weight' => 1, 'colour' => 'rose'],
    ],

    /*
     * 40 題,五個面向各 8 題。
     *
     *   section  分段標題的 key(只在該段第一題出現)
     *   dim      這題算進哪一個面向
     *   dir      1 = 同意代表壓抑高;-1 = 反向題,同意代表壓抑低
     *
     * 每個面向都是「4 題正向 + 4 題反向」,不是為了好看 —— 一路同意就能拿滿分的
     * 量表,測到的是「這個人會不會一路按到底」,不是他的狀態。數量也要對稱:5:3 的
     * 話,每題都按「完全符合」的人基線就是 58 而不是 50,整份測驗默默偏高。
     * RepressionTestTest 有一條測試守住這個對稱,加題時會叫。
     *
     * 順序就是顯示順序,而且 lang/{locale}/repression.php 的 questions 必須逐一
     * 對齊 —— 第 N 個結構配第 N 句題目。插題一律加在「該段末尾」,不要插在中間,
     * 否則後面所有題目文字都會錯位;題號也是使用者回報問題時唯一的座標。
     */
    'questions' => [
        // ── 0-7 慾望與罪惡感 ──
        ['section' => 'guilt', 'dim' => 'guilt', 'dir' => 1],
        ['dim' => 'guilt', 'dir' => -1],
        ['dim' => 'guilt', 'dir' => 1],
        ['dim' => 'guilt', 'dir' => -1],
        ['dim' => 'guilt', 'dir' => 1],
        ['dim' => 'guilt', 'dir' => -1],
        ['dim' => 'guilt', 'dir' => 1],
        ['dim' => 'guilt', 'dir' => -1],

        // ── 8-15 身體與羞恥 ──
        ['section' => 'shame', 'dim' => 'shame', 'dir' => 1],
        ['dim' => 'shame', 'dir' => -1],
        ['dim' => 'shame', 'dir' => 1],
        ['dim' => 'shame', 'dir' => -1],
        ['dim' => 'shame', 'dir' => -1],
        ['dim' => 'shame', 'dir' => 1],
        ['dim' => 'shame', 'dir' => -1],
        ['dim' => 'shame', 'dir' => 1],

        // ── 16-23 放鬆與表現焦慮 ──
        ['section' => 'anxiety', 'dim' => 'anxiety', 'dir' => 1],
        ['dim' => 'anxiety', 'dir' => 1],
        ['dim' => 'anxiety', 'dir' => -1],
        ['dim' => 'anxiety', 'dir' => -1],
        ['dim' => 'anxiety', 'dir' => 1],
        ['dim' => 'anxiety', 'dir' => -1],
        ['dim' => 'anxiety', 'dir' => 1],
        ['dim' => 'anxiety', 'dir' => -1],

        // ── 24-31 好奇與迴避 ──
        ['section' => 'avoid', 'dim' => 'avoid', 'dir' => -1],
        ['dim' => 'avoid', 'dir' => 1],
        ['dim' => 'avoid', 'dir' => -1],
        ['dim' => 'avoid', 'dir' => 1],
        ['dim' => 'avoid', 'dir' => 1],
        ['dim' => 'avoid', 'dir' => -1],
        ['dim' => 'avoid', 'dir' => 1],
        ['dim' => 'avoid', 'dir' => -1],

        // ── 32-39 表達與界線 ──
        ['section' => 'voice', 'dim' => 'voice', 'dir' => -1],
        ['dim' => 'voice', 'dir' => 1],
        ['dim' => 'voice', 'dir' => 1],
        ['dim' => 'voice', 'dir' => -1],
        ['dim' => 'voice', 'dir' => 1],
        ['dim' => 'voice', 'dir' => -1],
        ['dim' => 'voice', 'dir' => 1],
        ['dim' => 'voice', 'dir' => -1],
    ],

    /*
     * 五個級距。min 是**下界含**、上界由下一個級距的 min 決定,最後一個到 100。
     *
     * 級距順序 = 由低到高,結果頁的刻度尺直接照這個順序畫。slug 寫在 lang 檔裡,
     * 因為那是網址的一部分,將來若要換成在地化的網址片段就不用動結構。
     */
    'bands' => [
        'very_low' => ['min' => 0, 'colour' => 'green'],
        'low' => ['min' => 20, 'colour' => 'green'],
        'moderate' => ['min' => 40, 'colour' => 'gold'],
        'high' => ['min' => 60, 'colour' => 'indigo'],
        'very_high' => ['min' => 80, 'colour' => 'rose'],
    ],

    /*
     * 有翻譯的語系。沒列在這裡的語系會退回繁中文案並標 noindex ——
     * 中文內容配英文網址被收錄,對排名是扣分不是加分。
     */
    'translated' => ['zh_TW'],
];
