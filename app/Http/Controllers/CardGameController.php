<?php

namespace App\Http\Controllers;

use App\Services\CardGameService;
use Illuminate\Http\Request;

class CardGameController extends Controller
{
    /**
     * Show the single-device card game page.
     * All game logic runs client-side in JavaScript.
     */
    public function show(Request $request)
    {
        $isPremium = $request->user()?->isPremium() ?? false;

        $activities = CardGameService::getActivityPools($isPremium);

        return view('card-game.show', compact('isPremium', 'activities'));
    }
}
