<?php

return [
    // Adapter: 'adsense' or 'trafficjunky'
    'adapter' => env('AD_ADAPTER', 'adsense'),

    'adsense' => [
        'publisher_id'     => env('ADSENSE_PUBLISHER_ID'),
        'slot_home_banner' => env('ADSENSE_SLOT_HOME_BANNER'),
        'slot_home_mid'    => env('ADSENSE_SLOT_HOME_MID'),
        'slot_lobby_side'  => env('ADSENSE_SLOT_LOBBY_SIDE'),
        'slot_game_end'    => env('ADSENSE_SLOT_GAME_END'),
        'slot_share'       => env('ADSENSE_SLOT_SHARE'),
    ],

    'trafficjunky' => [
        'site_id'          => env('TRAFFICJUNKY_SITE_ID'),
        'spot_home_banner' => env('TRAFFICJUNKY_SPOT_HOME_BANNER'),
        'spot_home_mid'    => env('TRAFFICJUNKY_SPOT_HOME_MID'),
        'spot_lobby_side'  => env('TRAFFICJUNKY_SPOT_LOBBY_SIDE'),
        'spot_game_end'    => env('TRAFFICJUNKY_SPOT_GAME_END'),
        'spot_share'       => env('TRAFFICJUNKY_SPOT_SHARE'),
    ],
];
