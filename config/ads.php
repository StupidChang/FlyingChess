<?php

return [
    // Adapter: 'exoclick' | 'trafficjunky' | 'adsense'
    // NOTE: 本站屬成人向內容，Google AdSense 政策禁止成人內容 —
    // adsense adapter 僅保留給未來內容轉型或非成人子站使用，
    // 正式上線請使用 exoclick 或 trafficjunky。
    'adapter' => env('AD_ADAPTER', 'exoclick'),

    /*
     * 聯播網的後台網址,給 /admin 的廣告面板用。
     *
     * 放在這裡而不是寫死在 view 裡:adapter 的設定已經在這個檔案,「這個 adapter
     * 的後台在哪」是同一件事的一部分。只放主控台首頁、不放深層連結 —— 聯播網
     * 改版時深層連結會死掉,而死掉的後台連結沒有人會回報。
     */
    'networks' => [
        'exoclick' => [
            'label' => 'ExoClick',
            'dashboard' => 'https://admin.exoclick.com/',
            'site' => 'https://www.exoclick.com/',
            'hint' => '版位在 Sites & Zones,收益與填充率在 Statistics。',
        ],
        'trafficjunky' => [
            'label' => 'TrafficJunky',
            'dashboard' => 'https://www.trafficjunky.com/login',
            'site' => 'https://www.trafficjunky.com/',
            'hint' => '版位叫 Ad Spot;要先通過站台審核才會有量。',
        ],
        'adsense' => [
            'label' => 'Google AdSense',
            'dashboard' => 'https://www.google.com/adsense/',
            'site' => 'https://adsense.google.com/',
            'hint' => '本站是成人向內容,啟用會違反 AdSense 政策並可能導致帳號停用。',
            'forbidden' => true,
        ],
    ],

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
        'slot_discover_side' => env('ADSENSE_SLOT_DISCOVER_SIDE'),
    ],

    'trafficjunky' => [
        'site_id' => env('TRAFFICJUNKY_SITE_ID'),
        'spot_home_banner' => env('TRAFFICJUNKY_SPOT_HOME_BANNER'),
        'spot_home_mid' => env('TRAFFICJUNKY_SPOT_HOME_MID'),
        'spot_lobby_side' => env('TRAFFICJUNKY_SPOT_LOBBY_SIDE'),
        'spot_game_end' => env('TRAFFICJUNKY_SPOT_GAME_END'),
        'spot_share' => env('TRAFFICJUNKY_SPOT_SHARE'),
        'spot_discover_side' => env('TRAFFICJUNKY_SPOT_DISCOVER_SIDE'),
    ],

    // ExoClick banner zones — 每個版位一個 zone id（後台 Sites & Zones 建立）。
    //
    // ExoClick 的 zone 是固定尺寸的，所以「同一個版位、不同螢幕寬度」必須是兩個
    // 不同的 zone。基準值（zone_*）是窄版 300x250，桌機用的寬版放在
    // zone_*_desktop；彈窗(game_end)容器本來就窄,不需要寬版。
    // 沒有設定 _desktop 的版位就所有裝置共用同一個 zone,填了就自動生效。
    'exoclick' => [
        'zone_home_banner' => env('EXOCLICK_ZONE_HOME_BANNER'),
        'zone_home_banner_desktop' => env('EXOCLICK_ZONE_HOME_BANNER_DESKTOP'),
        'zone_home_mid' => env('EXOCLICK_ZONE_HOME_MID'),
        'zone_home_mid_desktop' => env('EXOCLICK_ZONE_HOME_MID_DESKTOP'),
        'zone_lobby_side' => env('EXOCLICK_ZONE_LOBBY_SIDE'),
        'zone_lobby_side_desktop' => env('EXOCLICK_ZONE_LOBBY_SIDE_DESKTOP'),
        'zone_game_end' => env('EXOCLICK_ZONE_GAME_END'),
        // 獎勵式影片(VAST)。填了就優先播影片,沒填或當次沒有填充就退回上面那顆
        // banner —— 「看廣告解鎖」放一張靜態圖本來就名不副實,但空白更糟。
        'zone_game_end_vast' => env('EXOCLICK_ZONE_GAME_END_VAST'),
        'zone_share' => env('EXOCLICK_ZONE_SHARE'),
        'zone_share_desktop' => env('EXOCLICK_ZONE_SHARE_DESKTOP'),
        // 尋找頁左右側的直式版位(建議 250x900 之類的摩天大樓尺寸)。只在寬螢幕
        // 顯示;沒設定就不出現、頁面自動改回單欄置中。在 ExoClick 建一個該尺寸的
        // zone,把 id 填進 EXOCLICK_ZONE_DISCOVER_SIDE 即生效。
        'zone_discover_side' => env('EXOCLICK_ZONE_DISCOVER_SIDE'),
        'zone_discover_side_desktop' => env('EXOCLICK_ZONE_DISCOVER_SIDE_DESKTOP'),
    ],
];
