<?php

/*
 * 色度測驗的骨架 —— 兩條軸的合併版。
 *
 * 為什麼是兩條軸:單獨量「你有多色」或單獨量「你有多壓抑」都會漏掉最常見的那種
 * 人。慾望和煞車是兩件獨立的事:
 *
 *   很想要 + 踩著煞車 = 悶燒(最多人不知道自己在這裡)
 *   很想要 + 不踩煞車 = 敢玩
 *   不太想 + 不踩煞車 = 佛系(這完全沒問題,不是缺陷)
 *   不太想 + 踩著煞車 = 上鎖
 *
 * 只給一個數字的話,「悶燒」和「佛系」會拿到很像的分數,而那兩種人需要的東西
 * 完全相反。所以結果是**象限位置**,不是一個級距。
 *
 * 和 config/traits.php、config/repression.php 同一套做法:這裡只放「結構」——
 * 軸、面向、題目歸屬、正反向。所有看得到的文字都在 lang/{locale}/horny.php,
 * 兩者用同一組 key 對起來。正反向表(等於答案卷)只留在伺服器。
 *
 * 計分在 App\Services\HornyTestService。
 */

return [

    /*
     * 兩條軸。每條軸的分數 = 它底下所有面向的加權平均(0–100)。
     *
     * desire 越高 = 慾望越強;brake 越高 = 煞車越重。兩條軸都是「越高越多」,
     * 不是一條正一條反 —— 象限圖的四個角才讀得直觀。
     */
    'axes' => [
        'desire' => ['dims' => ['drive', 'fantasy', 'initiate', 'arousal']],
        'brake' => ['dims' => ['guilt', 'shame', 'anxiety', 'avoid', 'voice']],
    ],

    /*
     * 九個面向。colour 對應 app.css 既有的 tt-c-* 變數。
     *
     * 權重目前一律 1。有實際資料之前不要憑感覺加權,那只會讓分數難以解釋。
     */
    'dimensions' => [
        // ── 油門 ──
        'drive' => ['axis' => 'desire', 'weight' => 1, 'colour' => 'rose'],
        'fantasy' => ['axis' => 'desire', 'weight' => 1, 'colour' => 'indigo'],
        'initiate' => ['axis' => 'desire', 'weight' => 1, 'colour' => 'gold'],
        'arousal' => ['axis' => 'desire', 'weight' => 1, 'colour' => 'green'],
        // ── 煞車 ──
        'guilt' => ['axis' => 'brake', 'weight' => 1, 'colour' => 'rose'],
        'shame' => ['axis' => 'brake', 'weight' => 1, 'colour' => 'indigo'],
        'anxiety' => ['axis' => 'brake', 'weight' => 1, 'colour' => 'gold'],
        'avoid' => ['axis' => 'brake', 'weight' => 1, 'colour' => 'green'],
        'voice' => ['axis' => 'brake', 'weight' => 1, 'colour' => 'rose'],
    ],

    /*
     * 64 題:油門四個面向各 6 題(0–23),煞車五個面向各 8 題(24–63)。
     *
     *   section  分段標題的 key(只在該段第一題出現)
     *   dim      這題算進哪一個面向
     *   dir      1 = 同意代表該面向的分數高;-1 = 反向題,同意代表低
     *
     * 每個面向都是一半正向一半反向。不是為了好看 —— 一路同意就能拿滿分的量表,
     * 測到的是「這個人會不會一路按到底」,不是他的狀態。數量也要對稱:6 題裡 4:2
     * 的話,每題都按「完全符合」的人基線就會偏掉,整條軸默默偏高。
     * HornyTestTest 有一條測試守住每個面向的對稱,加題時會叫。
     *
     * 順序就是顯示順序,而且 lang/{locale}/horny.php 的 questions 必須逐一對齊 ——
     * 第 N 個結構配第 N 句題目。插題一律加在「該段末尾」,不要插在中間,否則後面
     * 所有題目文字都會錯位;題號也是使用者回報問題時唯一的座標。
     *
     * 煞車那 40 題沿用性壓抑測驗的題目與正反向(同一批題目量的是同一件事),
     * 所以它們的文字在 lang 檔裡也維持原順序,方便對照。
     */
    'questions' => [
        // ── 0-5 想要的頻率(drive)──
        ['section' => 'drive', 'dim' => 'drive', 'dir' => 1],
        ['dim' => 'drive', 'dir' => -1],
        ['dim' => 'drive', 'dir' => 1],
        ['dim' => 'drive', 'dir' => -1],
        ['dim' => 'drive', 'dir' => 1],
        ['dim' => 'drive', 'dir' => -1],

        // ── 6-11 腦子裡的畫面(fantasy)──
        ['section' => 'fantasy', 'dim' => 'fantasy', 'dir' => 1],
        ['dim' => 'fantasy', 'dir' => -1],
        ['dim' => 'fantasy', 'dir' => 1],
        ['dim' => 'fantasy', 'dir' => -1],
        ['dim' => 'fantasy', 'dir' => 1],
        ['dim' => 'fantasy', 'dir' => -1],

        // ── 12-17 會不會主動(initiate)──
        ['section' => 'initiate', 'dim' => 'initiate', 'dir' => 1],
        ['dim' => 'initiate', 'dir' => -1],
        ['dim' => 'initiate', 'dir' => 1],
        ['dim' => 'initiate', 'dir' => -1],
        ['dim' => 'initiate', 'dir' => 1],
        ['dim' => 'initiate', 'dir' => -1],

        // ── 18-23 多快被點燃(arousal)──
        ['section' => 'arousal', 'dim' => 'arousal', 'dir' => 1],
        ['dim' => 'arousal', 'dir' => -1],
        ['dim' => 'arousal', 'dir' => 1],
        ['dim' => 'arousal', 'dir' => -1],
        ['dim' => 'arousal', 'dir' => 1],
        ['dim' => 'arousal', 'dir' => -1],

        // ── 24-31 慾望與罪惡感(guilt)──
        ['section' => 'guilt', 'dim' => 'guilt', 'dir' => 1],
        ['dim' => 'guilt', 'dir' => -1],
        ['dim' => 'guilt', 'dir' => 1],
        ['dim' => 'guilt', 'dir' => -1],
        ['dim' => 'guilt', 'dir' => 1],
        ['dim' => 'guilt', 'dir' => -1],
        ['dim' => 'guilt', 'dir' => 1],
        ['dim' => 'guilt', 'dir' => -1],

        // ── 32-39 身體與羞恥(shame)──
        ['section' => 'shame', 'dim' => 'shame', 'dir' => 1],
        ['dim' => 'shame', 'dir' => -1],
        ['dim' => 'shame', 'dir' => 1],
        ['dim' => 'shame', 'dir' => -1],
        ['dim' => 'shame', 'dir' => -1],
        ['dim' => 'shame', 'dir' => 1],
        ['dim' => 'shame', 'dir' => -1],
        ['dim' => 'shame', 'dir' => 1],

        // ── 40-47 放鬆與表現焦慮(anxiety)──
        ['section' => 'anxiety', 'dim' => 'anxiety', 'dir' => 1],
        ['dim' => 'anxiety', 'dir' => 1],
        ['dim' => 'anxiety', 'dir' => -1],
        ['dim' => 'anxiety', 'dir' => -1],
        ['dim' => 'anxiety', 'dir' => 1],
        ['dim' => 'anxiety', 'dir' => -1],
        ['dim' => 'anxiety', 'dir' => 1],
        ['dim' => 'anxiety', 'dir' => -1],

        // ── 48-55 好奇與迴避(avoid)──
        ['section' => 'avoid', 'dim' => 'avoid', 'dir' => -1],
        ['dim' => 'avoid', 'dir' => 1],
        ['dim' => 'avoid', 'dir' => -1],
        ['dim' => 'avoid', 'dir' => 1],
        ['dim' => 'avoid', 'dir' => 1],
        ['dim' => 'avoid', 'dir' => -1],
        ['dim' => 'avoid', 'dir' => 1],
        ['dim' => 'avoid', 'dir' => -1],

        // ── 56-63 表達與界線(voice)──
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
     * 五個結果。四個象限 + 一個「兩軸都在中間」。
     *
     * middle 不是第五個象限,是**中央那一塊**:兩條軸都落在 50±MIDDLE_BAND 之內。
     * 少了它,49 分和 51 分會被丟到完全不同的兩頁,而那個差距在一份自陳量表裡沒有
     * 意義(見結果頁的「離邊界多遠」)。
     *
     * desire / brake 是這個象限在該軸上的方向:'high' | 'low' | 'mid'。
     * slug 寫在 lang 檔裡,因為那是網址的一部分。
     */
    'middle_band' => 10,

    'quadrants' => [
        'simmering' => ['desire' => 'high', 'brake' => 'high', 'colour' => 'rose'],
        'open' => ['desire' => 'high', 'brake' => 'low', 'colour' => 'gold'],
        'easy' => ['desire' => 'low', 'brake' => 'low', 'colour' => 'green'],
        'locked' => ['desire' => 'low', 'brake' => 'high', 'colour' => 'indigo'],
        'middle' => ['desire' => 'mid', 'brake' => 'mid', 'colour' => 'gold'],
    ],

    /*
     * 有翻譯的語系。沒列在這裡的語系會退回繁中文案並標 noindex ——
     * 中文內容配英文網址被收錄,對排名是扣分不是加分。
     */
    'translated' => ['zh_TW'],
];
