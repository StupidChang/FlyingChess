<?php

namespace App\Http\Controllers;

use App\Services\WhoMostLikelyService;
use App\Support\PremiumAccess;
use Illuminate\Http\Request;

class WhoMostLikelyController extends Controller
{
    /**
     * Show the single-device "Who's most likely to…" party voting game.
     * All game logic runs client-side in JavaScript.
     */
    public function show(Request $request)
    {
        // 看廣告解鎖的時限內,訪客也算有內容權限 —— 見 App\Support\PremiumAccess。
        $isPremium = PremiumAccess::content($request->user());
        $prompts = WhoMostLikelyService::getPromptPools($isPremium);

        return view('who-most-likely.show', compact('isPremium', 'prompts'));
    }
}
