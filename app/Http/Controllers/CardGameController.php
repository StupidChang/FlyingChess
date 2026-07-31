<?php

namespace App\Http\Controllers;

use App\Services\CardGameService;
use App\Support\PremiumAccess;
use Illuminate\Http\Request;

class CardGameController extends Controller
{
    /**
     * 撲克牌遊戲(合併頁):情侶撲克牌與國王遊戲共用同一個 view 與同一套
     * 發牌/翻牌引擎,只有揭曉那一步不同。$mode 決定進站時的預設玩法。
     * 兩個路由都保留,各自帶自己的 title / canonical,SEO 入口不變。
     */
    public function show(Request $request)
    {
        // 看廣告解鎖的時限內,訪客也算有內容權限 —— 見 App\Support\PremiumAccess。
        $isPremium = PremiumAccess::content($request->user());
        $activities = CardGameService::getActivityPools($isPremium);

        return view('cards.show', [
            'isPremium' => $isPremium,
            'activities' => $activities,
            'mode' => 'card',
        ]);
    }
}
