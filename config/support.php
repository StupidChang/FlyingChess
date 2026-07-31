<?php

/*
 * 客服聯絡方式。
 *
 * 原本 support@couplefly.com 與 LINE @couplefly 是寫死在
 * resources/views/premium/index.blade.php 裡的 —— 而 couplefly.com 其實不是
 * 本站的網域(它掛在 Dan.com 待售),等於付費頁上的客服信箱從來沒有人收得到。
 * 抽到設定檔的目的:換網域時只改 .env,不必動 Blade,也不會再有「頁面上寫著
 * 一個我們並不擁有的網域」這種事。
 *
 * 留空的項目在頁面上會自動不顯示,不會留下半個空欄位。
 */
return [
    'email' => env('SUPPORT_EMAIL', ''),
    'line' => env('SUPPORT_LINE', ''),
];
