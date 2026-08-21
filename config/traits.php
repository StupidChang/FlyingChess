<?php

/*
 * 枕邊屬性測驗的骨架。
 *
 * 這裡只放「結構」—— 屬性代碼、題目權重、光譜方向。所有看得到的文字都在
 * lang/{locale}/traits.php,兩者用同一組 key 對起來。分開的理由有兩個:
 *
 *   1. 翻譯是加上去的,不用碰計分邏輯。
 *   2. 權重表(等於答案卷)只留在伺服器 —— 送到瀏覽器的只有題目文字。
 *      整份權重表送出去的話,別人抄走的不只是題目,是整個測驗。
 *
 * 計分在 App\Services\TraitTestService。
 */

return [

    /*
     * 20 種屬性。不是「你是哪一型」,而是每一種各給一個百分比 —— 大多數人會有
     * 三到五種同時偏高,那才是正常的。組合數因此是實質無限,不是固定 20 種結果。
     *
     * colour 用在結果頁的長條與分享圖,對應 app.css 既有的變數。
     */
    'traits' => [
        'dom' => ['colour' => 'rose'],
        'caregiver' => ['colour' => 'rose'],
        'tease' => ['colour' => 'rose'],
        'coach' => ['colour' => 'rose'],
        'sub' => ['colour' => 'indigo'],
        'brat' => ['colour' => 'indigo'],
        'pleaser' => ['colour' => 'indigo'],
        'devotee' => ['colour' => 'indigo'],
        'switch' => ['colour' => 'gold'],
        'sensual' => ['colour' => 'gold'],
        'romantic' => ['colour' => 'gold'],
        'verbal' => ['colour' => 'gold'],
        'voyeur' => ['colour' => 'green'],
        'exhib' => ['colour' => 'green'],
        'explorer' => ['colour' => 'green'],
        'ritual' => ['colour' => 'green'],
        'guardian' => ['colour' => 'green'],
        'spark' => ['colour' => 'gold'],
        'slowburn' => ['colour' => 'gold'],
        'aftercare' => ['colour' => 'gold'],
    ],

    /*
     * 四個光譜。20 條屬性線畫成時間軸太雜,四條才看得出走向,所以個人資料頁的
     * 走勢圖用的是這四條。
     */
    'axes' => ['DS', 'PE', 'OR', 'IG'],

    /*
     * 102 題,分七段。
     *
     *   section  分段標題的 key(只在該段第一題出現)
     *   axis     [光譜, 方向]。方向 0 表示這題不計入任何光譜,只餵屬性
     *   weights  這題餵給哪些屬性、各多少權重(可負)
     *
     * 一題同時餵好幾個屬性,所以撐得起 20 種屬性的分數。屬性百分比是各自對自己的
     * 權重上限正規化的,所以某個屬性被幾題餵到不影響「算不算得出來」,只影響解析度。
     *
     * 順序就是顯示順序,而且 lang/{locale}/traits.php 的 questions 必須逐一對齊 ——
     * 第 N 個結構配第 N 句題目。插題一律加在「該段末尾 / 全部末尾」,不要插在中間,
     * 否則所有後面的題目文字都會錯位;題號也是使用者回報問題時唯一的座標。
     */
    'questions' => [
        // ── 0-15 節奏與主導(DS)──
        ['section' => 'lead', 'axis' => ['DS', 1], 'weights' => ['dom' => 2, 'tease' => 1, 'sub' => -1]],
        ['axis' => ['DS', -1], 'weights' => ['sub' => 2, 'devotee' => 1, 'dom' => -1]],
        ['axis' => ['DS', 1], 'weights' => ['dom' => 2, 'tease' => 1, 'pleaser' => 1]],
        ['axis' => ['DS', -1], 'weights' => ['sub' => 2, 'devotee' => 1]],
        ['axis' => ['DS', 1], 'weights' => ['tease' => 3, 'dom' => 1]],
        ['axis' => ['DS', -1], 'weights' => ['brat' => 3, 'sub' => 1]],
        ['axis' => ['DS', 0], 'weights' => ['pleaser' => 3, 'caregiver' => 1]],
        ['axis' => ['DS', 1], 'weights' => ['caregiver' => 3, 'coach' => 1, 'dom' => 1]],
        ['axis' => ['DS', 0], 'weights' => ['switch' => 3, 'dom' => -1, 'sub' => -1]],
        ['axis' => ['DS', 1], 'weights' => ['dom' => 3, 'coach' => 1]],
        ['axis' => ['DS', -1], 'weights' => ['sub' => 2, 'devotee' => 1, 'brat' => -1]],
        ['axis' => ['DS', 1], 'weights' => ['coach' => 3, 'dom' => 1]],
        ['axis' => ['DS', -1], 'weights' => ['sub' => 2, 'devotee' => 1]],
        ['axis' => ['DS', 0], 'weights' => ['switch' => 3]],
        ['axis' => ['DS', 1], 'weights' => ['dom' => 2, 'coach' => 1]],
        ['axis' => ['DS', -1], 'weights' => ['brat' => 3, 'sub' => 1]],

        // ── 16-30 感受從哪裡來(PE)──
        ['section' => 'feel', 'axis' => ['PE', 1], 'weights' => ['sensual' => 3]],
        ['axis' => ['PE', -1], 'weights' => ['romantic' => 3]],
        ['axis' => ['PE', 0], 'weights' => ['verbal' => 3, 'romantic' => 1]],
        ['axis' => ['PE', 1], 'weights' => ['sensual' => 2, 'ritual' => 1]],
        ['axis' => ['PE', 0], 'weights' => ['verbal' => 3, 'dom' => 1]],
        ['axis' => ['PE', -1], 'weights' => ['pleaser' => 2, 'devotee' => 2, 'romantic' => 1]],
        ['axis' => ['PE', -1], 'weights' => ['aftercare' => 3, 'caregiver' => 1]],
        ['axis' => ['PE', 1], 'weights' => ['sensual' => 3, 'slowburn' => 1]],
        ['axis' => ['PE', -1], 'weights' => ['romantic' => 3, 'devotee' => 1]],
        ['axis' => ['PE', -1], 'weights' => ['verbal' => 2, 'romantic' => 1, 'exhib' => 1]],
        ['axis' => ['PE', 1], 'weights' => ['sensual' => 3, 'aftercare' => 1]],
        ['axis' => ['PE', 0], 'weights' => ['verbal' => 3, 'dom' => 1]],
        ['axis' => ['PE', -1], 'weights' => ['romantic' => 2, 'tease' => 1]],
        ['axis' => ['PE', 1], 'weights' => ['sensual' => 3]],
        ['axis' => ['PE', -1], 'weights' => ['aftercare' => 3, 'devotee' => 1]],

        // ── 31-46 尺度與想像(OR)──
        ['section' => 'limits', 'axis' => ['OR', 1], 'weights' => ['explorer' => 3]],
        ['axis' => ['OR', -1], 'weights' => ['guardian' => 3, 'explorer' => -1]],
        ['axis' => ['OR', 1], 'weights' => ['exhib' => 3, 'explorer' => 1]],
        ['axis' => ['OR', -1], 'weights' => ['guardian' => 2, 'ritual' => 2]],
        ['axis' => ['OR', 1], 'weights' => ['voyeur' => 3]],
        ['axis' => ['OR', 1], 'weights' => ['exhib' => 3]],
        ['axis' => ['OR', -1], 'weights' => ['ritual' => 3, 'guardian' => 1, 'spark' => -1]],
        ['axis' => ['OR', 1], 'weights' => ['explorer' => 2, 'dom' => 1]],
        ['axis' => ['OR', 1], 'weights' => ['exhib' => 2, 'voyeur' => 1]],
        ['axis' => ['OR', 1], 'weights' => ['exhib' => 3]],
        ['axis' => ['OR', -1], 'weights' => ['guardian' => 3]],
        ['axis' => ['OR', 1], 'weights' => ['explorer' => 2, 'exhib' => 1, 'brat' => 1]],
        ['axis' => ['OR', -1], 'weights' => ['ritual' => 3, 'sensual' => 1]],
        ['axis' => ['OR', 1], 'weights' => ['voyeur' => 3]],
        ['axis' => ['OR', -1], 'weights' => ['guardian' => 2, 'ritual' => 1, 'spark' => -1]],
        ['axis' => ['OR', 1], 'weights' => ['exhib' => 2, 'pleaser' => 1]],

        // ── 47-60 速度與升溫(IG)──
        ['section' => 'pace', 'axis' => ['IG', 1], 'weights' => ['spark' => 3]],
        ['axis' => ['IG', -1], 'weights' => ['slowburn' => 3]],
        ['axis' => ['IG', 1], 'weights' => ['spark' => 3, 'ritual' => -1]],
        ['axis' => ['IG', -1], 'weights' => ['slowburn' => 3, 'sensual' => 1]],
        ['axis' => ['IG', -1], 'weights' => ['slowburn' => 2, 'tease' => 2]],
        ['axis' => ['IG', -1], 'weights' => ['aftercare' => 2, 'devotee' => 2, 'romantic' => 1]],
        ['axis' => ['IG', 1], 'weights' => ['spark' => 3]],
        ['axis' => ['IG', -1], 'weights' => ['slowburn' => 2, 'tease' => 1, 'romantic' => 1]],
        ['axis' => ['IG', 1], 'weights' => ['spark' => 2, 'explorer' => 1]],
        ['axis' => ['IG', -1], 'weights' => ['slowburn' => 3]],
        ['axis' => ['IG', 1], 'weights' => ['spark' => 2, 'dom' => 1]],
        ['axis' => ['IG', -1], 'weights' => ['slowburn' => 3]],
        ['axis' => ['IG', 1], 'weights' => ['spark' => 2, 'guardian' => -1]],
        ['axis' => ['IG', -1], 'weights' => ['slowburn' => 2, 'tease' => 1, 'devotee' => 1]],

        // ── 61-75 親密裡的依附(多為 axis 0,只餵屬性)──
        ['section' => 'bond', 'axis' => ['DS', 0], 'weights' => ['devotee' => 3]],
        ['axis' => ['DS', 0], 'weights' => ['caregiver' => 3]],
        ['axis' => ['DS', 0], 'weights' => ['pleaser' => 3]],
        ['axis' => ['DS', 0], 'weights' => ['devotee' => 3, 'romantic' => 1]],
        ['axis' => ['DS', 0], 'weights' => ['caregiver' => 3, 'coach' => 1]],
        ['axis' => ['DS', 0], 'weights' => ['pleaser' => 2, 'caregiver' => 1, 'devotee' => 1]],
        ['axis' => ['DS', -1], 'weights' => ['devotee' => 2, 'sub' => 1, 'sensual' => 1]],
        ['axis' => ['DS', 0], 'weights' => ['devotee' => 3]],
        ['axis' => ['DS', 0], 'weights' => ['pleaser' => 3]],
        ['axis' => ['DS', 1], 'weights' => ['caregiver' => 3]],
        ['axis' => ['DS', 0], 'weights' => ['switch' => 3]],
        ['axis' => ['DS', 0], 'weights' => ['caregiver' => 2, 'devotee' => 1]],
        ['axis' => ['DS', -1], 'weights' => ['sub' => 2, 'guardian' => 1]],
        ['axis' => ['DS', 0], 'weights' => ['aftercare' => 3]],
        ['axis' => ['DS', 0], 'weights' => ['exhib' => 2, 'devotee' => 1]],

        // ── 76-89 表達、界線與收尾 ──
        ['section' => 'voice', 'axis' => ['PE', 0], 'weights' => ['verbal' => 3]],
        ['axis' => ['OR', -1], 'weights' => ['guardian' => 3]],
        ['axis' => ['DS', -1], 'weights' => ['pleaser' => 2, 'devotee' => 1, 'sub' => 1]],
        ['axis' => ['IG', 0], 'weights' => ['aftercare' => 3]],
        ['axis' => ['PE', 0], 'weights' => ['dom' => 3, 'verbal' => 1]],
        ['axis' => ['PE', -1], 'weights' => ['romantic' => 2, 'verbal' => 1]],
        ['axis' => ['OR', -1], 'weights' => ['ritual' => 2, 'guardian' => 1]],
        ['axis' => ['DS', 0], 'weights' => ['verbal' => 1, 'coach' => 1, 'guardian' => 1]],
        ['axis' => ['IG', 0], 'weights' => ['aftercare' => 3]],
        ['axis' => ['PE', 0], 'weights' => ['verbal' => 2, 'tease' => 1, 'dom' => 1]],
        ['axis' => ['DS', 0], 'weights' => ['aftercare' => 2, 'devotee' => 1, 'romantic' => 1]],
        ['axis' => ['DS', 1], 'weights' => ['coach' => 3, 'caregiver' => 1]],
        ['axis' => ['DS', -1], 'weights' => ['sub' => 2, 'devotee' => 1]],
        ['axis' => ['OR', -1], 'weights' => ['guardian' => 3]],

        /* ── 90-101 角色、觀看與抵抗 ──
           2026-08-21 補題。加在**全部末尾**而不是各段末尾:插在中間會把後面所有
           題號往後推,而題號是使用者回報問題時唯一的座標。

           為什麼補這三個屬性:switch 原本只有 3 題、而且權重全是 3,百分比只有
           七種可能的取值(0/17/33/50/67/83/100),使用者看到的是跳格不是分數;
           voyeur 3 題、brat 4 題同樣偏低。題目數少還有第二個後果 —— 變異大、
           容易衝到極端,所以隨機作答時 voyeur 當上主屬性的機率是 devotee 的
           二十倍,結果頁的流量會極度不均。

           新題的權重刻意混用 1/2/3。全部給 3 的話取值只能跳三格,補題也補不到
           解析度 —— 那正是 switch 原本的問題。 */
        ['section' => 'flow', 'axis' => ['DS', 0], 'weights' => ['switch' => 2, 'coach' => 1]],
        ['axis' => ['DS', 0], 'weights' => ['switch' => 3]],
        ['axis' => ['DS', 0], 'weights' => ['switch' => 2, 'explorer' => 1]],
        /* 只餵 switch、權重 1:解析度需要一個「跳一格」的題目。原本這題還加了
           dom+1 與 sub+1(壓人和被壓都享受,兩邊確實都算),但那會抵掉第 8 題的
           switch+3/dom-1/sub-1 —— 共現表上 switch 和 dom 的淨值變成 0,
           「容易互為反面」那一區就少了最該出現的一組。留單一權重乾淨得多。 */
        ['axis' => ['DS', 0], 'weights' => ['switch' => 1]],
        ['axis' => ['DS', 0], 'weights' => ['switch' => 3, 'pleaser' => 1]],
        ['axis' => ['OR', 1], 'weights' => ['voyeur' => 2, 'exhib' => 1]],
        ['axis' => ['OR', 1], 'weights' => ['voyeur' => 1, 'verbal' => 1]],
        ['axis' => ['OR', 1], 'weights' => ['voyeur' => 2, 'tease' => 1]],
        ['axis' => ['OR', 0], 'weights' => ['voyeur' => 3, 'sensual' => -1]],
        ['axis' => ['DS', -1], 'weights' => ['brat' => 2, 'tease' => 1]],
        ['axis' => ['DS', -1], 'weights' => ['brat' => 3, 'verbal' => 1]],
        ['axis' => ['DS', 0], 'weights' => ['brat' => 1, 'guardian' => 1]],
    ],

    /*
     * 有完整翻譯的語系。沒在這裡的語系會退回繁中文案,而且那一頁會標 noindex ——
     * 讓搜尋引擎收錄一頁中文內容配英文網址,對排名是扣分不是加分。
     * 翻好一個語系就把它加進來。
     */
    'translated' => ['zh_TW'],
];
