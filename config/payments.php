<?php

/*
 * 金流商選擇。
 *
 * 目前是綠界(只收台幣、且成人向網站不見得過得了他們的審核)。要換成
 * CCBill / SegPay / Epoch 這類支援多幣別的成人產業金流時,流程是:
 *
 *   1. 寫一個實作 App\Support\Payments\PaymentGateway 的類別
 *   2. 加進下面的 gateways
 *   3. 把 PAYMENT_GATEWAY 改成它
 *
 * PremiumController 不需要改。config/premium.php 裡的 USD / JPY 標價已經備好,
 * 新金流只要在 supportedCurrencies() 回報得出來,結帳就會放行。
 */
return [
    'default' => env('PAYMENT_GATEWAY', 'ecpay'),

    'gateways' => [
        'ecpay' => [
            'driver' => App\Support\Payments\EcpayGateway::class,
            'config' => 'ecpay',   // 讀 config/ecpay.php
        ],
    ],
];
