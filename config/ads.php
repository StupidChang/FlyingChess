<?php

return [
    // Adapter: 'exoclick' | 'trafficjunky' | 'adsense'
    // NOTE: 本站屬成人向內容，Google AdSense 政策禁止成人內容 —
    // adsense adapter 僅保留給未來內容轉型或非成人子站使用，
    // 正式上線請使用 exoclick 或 trafficjunky。
    'adapter' => env('AD_ADAPTER', 'exoclick'),

    // /ads.txt 內容：多行以 | 分隔，例如：
    // ADS_TXT_LINES="exoclick.com, 123456, DIRECT|google.com, pub-0000, DIRECT, f08c47fec0942fa0"
    'txt_lines' => env('ADS_TXT_LINES', ''),

    'adsense' => [
        'publisher_id' => env('ADSENSE_PUBLISHER_ID'),
        'slot_home_banner' => env('ADSENSE_SLOT_HOME_BANNER'),
        'slot_home_mid' => env('ADSENSE_SLOT_HOME_MID'),
        'slot_lobby_side' => env('ADSENSE_SLOT_LOBBY_SIDE'),
        'slot_game_end' => env('ADSENSE_SLOT_GAME_END'),
        'slot_share' => env('ADSENSE_SLOT_SHARE'),
    ],

    'trafficjunky' => [
        'site_id' => env('TRAFFICJUNKY_SITE_ID'),
        'spot_home_banner' => env('TRAFFICJUNKY_SPOT_HOME_BANNER'),
        'spot_home_mid' => env('TRAFFICJUNKY_SPOT_HOME_MID'),
        'spot_lobby_side' => env('TRAFFICJUNKY_SPOT_LOBBY_SIDE'),
        'spot_game_end' => env('TRAFFICJUNKY_SPOT_GAME_END'),
        'spot_share' => env('TRAFFICJUNKY_SPOT_SHARE'),
    ],

    // ExoClick banner zones — 每個版位一個 zone id（後台 Sites & Zones 建立）
    'exoclick' => [
        'zone_home_banner' => env('EXOCLICK_ZONE_HOME_BANNER'),
        'zone_home_mid' => env('EXOCLICK_ZONE_HOME_MID'),
        'zone_lobby_side' => env('EXOCLICK_ZONE_LOBBY_SIDE'),
        'zone_game_end' => env('EXOCLICK_ZONE_GAME_END'),
        'zone_share' => env('EXOCLICK_ZONE_SHARE'),
    ],
];
