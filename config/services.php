<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // 廣告聯播網設定一律在 config/ads.php — 這裡不要重複定義。

    // Google Analytics 4 — views 透過 config() 讀取（env() 在 config:cache 後
    // 於 view 內不可靠）
    'ga4' => [
        'id' => env('GOOGLE_GA4_ID'),
    ],

    // Google 登入（Socialite）。三個值都填齊才會顯示登入按鈕，
    // 未設定時登入頁不會出現 Google 按鈕，也不會註冊任何路由行為。
    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],

    /*
     * 站長工具的驗證碼。Search Console 對這個站特別重要:成人站在自然搜尋上
     * 本來就受 SafeSearch 影響,沒有 Search Console 就連「有沒有被收錄」都
     * 只能用猜的。Bing 一起放 —— 成人查詢在 Bing 的佔比遠高於它的整體市佔。
     */
    'google' => [
        'site_verification' => env('GOOGLE_SITE_VERIFICATION'),
    ],

    'bing' => [
        'site_verification' => env('BING_SITE_VERIFICATION'),
    ],
];
