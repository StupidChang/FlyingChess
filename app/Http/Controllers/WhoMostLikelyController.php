<?php

namespace App\Http\Controllers;

use App\Services\WhoMostLikelyService;
use Illuminate\Http\Request;

class WhoMostLikelyController extends Controller
{
    /**
     * Show the single-device "Who's most likely to…" party voting game.
     * All game logic runs client-side in JavaScript.
     */
    public function show(Request $request)
    {
        $isPremium = $request->user()?->isPremium() ?? false;
        $prompts = WhoMostLikelyService::getPromptPools($isPremium);

        return view('who-most-likely.show', compact('isPremium', 'prompts'));
    }
}
