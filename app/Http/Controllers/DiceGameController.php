<?php

namespace App\Http\Controllers;

use App\Services\DiceGameService;
use Illuminate\Http\Request;

class DiceGameController extends Controller
{
    public function show(Request $request)
    {
        $isPremium = $request->user()?->isPremium() ?? false;
        $dicePools = DiceGameService::getDicePools($isPremium);

        return view('dice-game.show', compact('isPremium', 'dicePools'));
    }
}
