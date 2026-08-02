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
     * 30 題。
     *
     *   section  分段標題的 key(只在該段第一題出現)
     *   axis     [光譜, 方向]。方向 0 表示這題不計入任何光譜,只餵屬性
     *   weights  這題餵給哪些屬性、各多少權重(可負)
     *
     * 一題同時餵好幾個屬性,所以 30 題撐得起 20 種屬性的分數。
     * 順序就是顯示順序;插題請加在該段末尾,不要插在中間 —— 題號是使用者
     * 回報問題時唯一的座標。
     */
    'questions' => [
        ['section' => 'lead', 'axis' => ['DS', 1], 'weights' => ['dom' => 2, 'tease' => 1, 'sub' => -1]],
        ['axis' => ['DS', -1], 'weights' => ['sub' => 2, 'devotee' => 1, 'dom' => -1]],
        ['axis' => ['DS', 1], 'weights' => ['dom' => 2, 'tease' => 1, 'pleaser' => 1]],
        ['axis' => ['DS', -1], 'weights' => ['sub' => 2, 'devotee' => 1]],
        ['axis' => ['DS', 1], 'weights' => ['tease' => 3, 'dom' => 1]],
        ['axis' => ['DS', -1], 'weights' => ['brat' => 3, 'sub' => 1]],
        ['axis' => ['DS', 0], 'weights' => ['pleaser' => 3, 'caregiver' => 1]],
        ['axis' => ['DS', 1], 'weights' => ['caregiver' => 3, 'coach' => 1, 'dom' => 1]],
        ['axis' => ['DS', 0], 'weights' => ['switch' => 3, 'dom' => -1, 'sub' => -1]],

        ['section' => 'feel', 'axis' => ['PE', 1], 'weights' => ['sensual' => 3]],
        ['axis' => ['PE', -1], 'weights' => ['romantic' => 3]],
        ['axis' => ['PE', -1], 'weights' => ['verbal' => 3, 'romantic' => 1]],
        ['axis' => ['PE', 1], 'weights' => ['sensual' => 2, 'ritual' => 1]],
        ['axis' => ['PE', -1], 'weights' => ['verbal' => 3, 'dom' => 1]],
        ['axis' => ['PE', -1], 'weights' => ['pleaser' => 2, 'devotee' => 2, 'romantic' => 1]],
        ['axis' => ['PE', -1], 'weights' => ['aftercare' => 3, 'caregiver' => 1]],

        ['section' => 'limits', 'axis' => ['OR', 1], 'weights' => ['explorer' => 3]],
        ['axis' => ['OR', -1], 'weights' => ['guardian' => 3, 'explorer' => -1]],
        ['axis' => ['OR', 1], 'weights' => ['exhib' => 3, 'explorer' => 1]],
        ['axis' => ['OR', -1], 'weights' => ['guardian' => 2, 'ritual' => 2]],
        ['axis' => ['OR', 1], 'weights' => ['voyeur' => 3]],
        ['axis' => ['OR', 1], 'weights' => ['exhib' => 3]],
        ['axis' => ['OR', -1], 'weights' => ['ritual' => 3, 'guardian' => 1, 'spark' => -1]],
        ['axis' => ['OR', 1], 'weights' => ['explorer' => 2, 'dom' => 1]],

        ['section' => 'pace', 'axis' => ['IG', 1], 'weights' => ['spark' => 3]],
        ['axis' => ['IG', -1], 'weights' => ['slowburn' => 3]],
        ['axis' => ['IG', 1], 'weights' => ['spark' => 3, 'ritual' => -1]],
        ['axis' => ['IG', -1], 'weights' => ['slowburn' => 3, 'sensual' => 1]],
        ['axis' => ['IG', -1], 'weights' => ['slowburn' => 2, 'tease' => 2]],
        ['axis' => ['IG', -1], 'weights' => ['aftercare' => 2, 'devotee' => 2, 'romantic' => 1]],
    ],

    /*
     * 有完整翻譯的語系。沒在這裡的語系會退回繁中文案,而且那一頁會標 noindex ——
     * 讓搜尋引擎收錄一頁中文內容配英文網址,對排名是扣分不是加分。
     * 翻好一個語系就把它加進來。
     */
    'translated' => ['zh_TW'],
];
