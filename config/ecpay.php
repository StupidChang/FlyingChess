<?php

return [
    'merchant_id'  => env('ECPAY_MERCHANT_ID', '3002607'),
    'hash_key'     => env('ECPAY_HASH_KEY', 'pwFHCqoQZGmho4w6'),
    'hash_iv'      => env('ECPAY_HASH_IV', 'EkRm7iFT261dpevs'),
    'service_url'  => env('ECPAY_SERVICE_URL', 'https://payment-stage.ecpay.com.tw/Cashier/AioCheckOut/V5'),
    'is_sandbox'   => env('ECPAY_SANDBOX', true),
];
