<?php

/*
 * Premium 的幣別與方案設定。
 *
 * 原本這裡只有 'price' => 99 與 'duration_days' => 30,幣別符號「NT$」散寫在四個
 * 語系的 seo/home/faq 字串與 premium/index.blade.php 裡(共 17 處),所以改價或換
 * 幣別要動十幾個檔案,而且一定會漏。現在數字與幣別都只在這個檔案裡,顯示一律走
 * App\Support\Pricing。
 *
 * ── 關於 locale_currency(重要)──
 * 預設是空的,也就是所有語系都顯示 default_currency。這是刻意的:顯示 NT$ 卻用美元
 * 扣款會產生爭議與退款。**只有在金流商真的能用該幣別結算時,才把語系加進去。**
 * 例如確定能收台幣之後,再加 'zh_TW' => 'TWD'。
 *
 * ── 關於 amounts ──
 * 每個方案在每種幣別各自標價,不做匯率換算。國際訂閱的慣例是各幣別訂在心理價位
 * (US$7.99 / NT$249)而不是換算出來的零頭,而且即時匯率會讓價格每天跳動。
 */
return [
    /*
     * 實際結算幣別。沒有為某語系指定幣別時,顯示的就是這個。
     *
     * 現在是 TWD,因為目前串的綠界只收新台幣 —— 設成 USD 的話每一次結帳都會被
     * PremiumController 擋下(它寧可回 503 也不要用台幣金額去收美元標價的單)。
     * 換到支援多幣別的金流之後,把這裡改成 USD(或設 PREMIUM_CURRENCY=USD)就
     * 全站切換,美元的標價下面已經備好了。
     */
    'default_currency' => env('PREMIUM_CURRENCY', 'TWD'),

    'currencies' => [
        // decimals 同時決定顯示的小數位與「最小單位」的換算:
        // USD 2 位 → US$7.99 存成 799;TWD/JPY 0 位 → NT$249 存成 249。
        'USD' => ['symbol' => 'US$', 'decimals' => 2],
        'TWD' => ['symbol' => 'NT$', 'decimals' => 0],
        'JPY' => ['symbol' => '¥', 'decimals' => 0],
    ],

    // 語系 → 幣別。空的 = 全部顯示 default_currency。加之前請先讀上面的說明。
    'locale_currency' => [
        // 'zh_TW' => 'TWD',
        // 'ja'    => 'JPY',
    ],

    // 文案裡「起價」引用的方案(首頁 FAQ、meta description 等)
    'entry_plan' => 'monthly',

    // 結帳沒有指定方案時的預設,也是頁面上標記為推薦的那個
    'default_plan' => 'yearly',

    'plans' => [
        'monthly' => [
            'days' => 30,
            'amounts' => ['USD' => 7.99, 'TWD' => 249, 'JPY' => 1200],
        ],
        'yearly' => [
            'days' => 365,
            'amounts' => ['USD' => 34.99, 'TWD' => 1090, 'JPY' => 5200],
        ],
    ],

    // 免費會員在遊玩紀錄裡看得到的場次數。付費會員不受限,並且會看到時間軸。
    'free_history_limit' => (int) env('PREMIUM_FREE_HISTORY_LIMIT', 5),

    /*
     * 看廣告換一段時間的付費內容。
     *
     * minutes 設 30 是對著「一場派對大約多久」抓的:比一場短的話會在興頭上斷掉,
     * 比一場長的話等於白送。要調的話請對照實際的平均遊玩時長,不要憑感覺。
     *
     * min_watch_seconds 是伺服器端認可的最短觀看秒數,擋隨手重打 API 的人。
     * 真正的防作弊要靠聯播網的 server-to-server reward callback,
     * 見 App\Support\PremiumAccess::issueAdToken() 的說明。
     */
    'rewarded' => [
        'minutes' => (int) env('PREMIUM_REWARDED_MINUTES', 30),
        'min_watch_seconds' => (int) env('PREMIUM_REWARDED_MIN_WATCH', 15),
    ],
];
