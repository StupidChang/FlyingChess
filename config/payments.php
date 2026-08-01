<?php

/*
 * 金流商選擇。
 *
 * 目前是 disabled —— 站上沒有任何付款入口,這是刻意的決定,不是還沒設定完。
 * 綠界的 driver 與 config/ecpay.php 已經整份移除:台灣金流的特約商店條款
 * 基本都排除情色類,本站過不了審;就算勉強過了,被抽查停權時款項可能被凍結。
 *
 * 要接成人產業金流(CCBill / SegPay / Epoch)時:
 *
 *   1. 寫一個實作 App\Support\Payments\PaymentGateway 的類別
 *   2. 加進下面的 gateways
 *   3. 把 PAYMENT_GATEWAY 改成它
 *
 * PremiumController 不需要改。config/premium.php 裡的 USD / JPY 標價已經備好,
 * 新金流只要在 supportedCurrencies() 回報得出來,結帳就會放行 —— 付款按鈕與
 * 結帳路由也會自動跟著回來,兩者都掛在 gateway 的 isLive() 上。
 */
return [
    'default' => env('PAYMENT_GATEWAY', 'disabled'),

    'gateways' => [
        'disabled' => [
            'driver' => App\Support\Payments\DisabledGateway::class,
            'config' => null,
        ],
    ],
];
