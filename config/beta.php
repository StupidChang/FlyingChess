<?php

/*
 * 測試版公告。
 *
 * 站台還在測試階段:題目會增刪、功能會改、資料有可能被重置。與其讓使用者遇到
 * 變動時以為是壞掉了,不如一開始就講清楚 —— 桌機版在右下角浮一張小卡,手機版
 * 收成底部一條細的,兩邊都可以關掉。
 *
 * 正式上線時把 BETA_NOTICE 設成 false,整塊就消失,不必動 Blade。
 *
 * notice_version 是「關掉之後就不再出現」那份記憶的依據(存在瀏覽器
 * localStorage,不是 cookie,所以不會多送一份東西到伺服器)。之後想再宣布一次
 * 別的事情,把版號 +1,所有人就會重新看到一次。
 */
return [
    'notice' => env('BETA_NOTICE', true),
    'notice_version' => env('BETA_NOTICE_VERSION', '1'),
];
