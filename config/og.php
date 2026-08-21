<?php

return [

    /*
    |--------------------------------------------------------------------------
    | 分享卡片(og:image)
    |--------------------------------------------------------------------------
    |
    | 兩份測驗的結果頁各自產一張 1200×630 的 PNG,拿來當 og:image。之前 20 種屬性
    | 與 5 個級距共用同一張站台預設圖 —— 分享出去長得一模一樣,看不出對方測到什麼。
    |
    */

    /**
     * 卡片版本。**改繪圖程式或版面時要 +1**:快取鍵包含這個數字,不動它的話
     * 已經產出的圖會一直被沿用,改版看不到效果(語系檔改動會自動失效,程式改動不會)。
     */
    'version' => 1,

    /**
     * 字型。GD 畫中文一定要一份含 CJK 字符的 TTF/OTF/TTC —— 這台機器上是
     * `fonts-noto-cjk`(Debian/Ubuntu)。Alpine 是 `font-noto-cjk`,見 Dockerfile。
     *
     * 找不到字型時卡片不會硬畫成一排豆腐字,而是整個端點退回站台預設圖
     * (見 OgImageService::available())。
     */
    'fonts' => [
        /* 候選路徑,由前往後取第一個讀得到的 —— Debian/Ubuntu 與 Alpine 的字型
           目錄不一樣,寫死任一邊就會在另一邊靜靜退回預設圖。都找不到時服務還會
           再 glob 一次(見 OgImageService::font())。 */
        'regular' => array_values(array_filter([
            env('OG_FONT_REGULAR'),
            '/usr/share/fonts/opentype/noto/NotoSansCJK-Regular.ttc',  // Debian/Ubuntu: fonts-noto-cjk
            '/usr/share/fonts/noto/NotoSansCJK-Regular.ttc',           // Alpine: font-noto-cjk
        ])),
        'bold' => array_values(array_filter([
            env('OG_FONT_BOLD'),
            '/usr/share/fonts/opentype/noto/NotoSansCJK-Bold.ttc',
            '/usr/share/fonts/noto/NotoSansCJK-Bold.ttc',
        ])),
    ],

    /**
     * 產好的圖放哪。放 storage 而不是 public:內容跟著語系檔變,要能被
     * 「語系檔 mtime 變了就重畫」這個機制覆蓋掉,不適合當成永久靜態檔。
     */
    'cache_dir' => storage_path('framework/og'),

    /** 瀏覽器與各家爬蟲快取秒數。語系檔一改,URL 上的 v 參數會變,不必怕改了不生效。 */
    'ttl' => 86400,
];
