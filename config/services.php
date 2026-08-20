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

        // 只接受這個 SNS Topic 送來的退信/客訴通知。留空會 fail-closed(拒收並
        // 記一行含真實 ARN 的警告),因為不綁 Topic 的話,任何人都能用自己 AWS
        // 帳號的 Topic 發一則簽章合法的「某地址退信了」,把任意信箱加進抑制清單。
        // 值長這樣:arn:aws:sns:us-east-1:123456789012:ses-feedback
        'topic_arn' => env('SNS_TOPIC_ARN'),
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

    /*
     * Google 登入（Socialite）+ 站長工具驗證碼。
     *
     * ⚠ 這兩組設定一定要放在同一個 'google' 鍵底下。PHP 陣列的重複字面鍵是
     * 「後者覆蓋前者」,先前拆成兩個 'google' 區塊,導致 client_id/secret/redirect
     * 整組被第二個(只有 site_verification 的)蓋掉 —— Google 登入因此在正式站
     * 完全失效,而且沒有任何錯誤,只是按鈕默默不出現。合併後三個登入值才讀得到。
     *
     * 登入:三個值都填齊,登入頁才會顯示 Google 按鈕。
     * site_verification:Search Console 驗證碼,成人站在自然搜尋受 SafeSearch
     * 影響,沒有 Search Console 連「有沒有被收錄」都只能用猜的。
     */
    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
        'site_verification' => env('GOOGLE_SITE_VERIFICATION'),
    ],

    'bing' => [
        'site_verification' => env('BING_SITE_VERIFICATION'),
    ],
];
