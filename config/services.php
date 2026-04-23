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

    // Google AdSense — 在 .env 填入後即啟用廣告
    'adsense' => [
        'publisher_id'  => env('ADSENSE_PUBLISHER_ID'),
        'slot_home_top' => env('ADSENSE_SLOT_HOME_TOP'),
        'slot_home_mid' => env('ADSENSE_SLOT_HOME_MID'),
        'slot_play'     => env('ADSENSE_SLOT_PLAY'),
    ],

    // TrafficJunky — 成人廣告網路（優先於 AdSense）
    'trafficjunky' => [
        'site_id'        => env('TRAFFICJUNKY_SITE_ID'),
        'spot_home_top'  => env('TRAFFICJUNKY_SPOT_HOME_TOP'),
        'spot_home_mid'  => env('TRAFFICJUNKY_SPOT_HOME_MID'),
        'spot_play'      => env('TRAFFICJUNKY_SPOT_PLAY'),
    ],

];
