<?php

namespace App\Http\Controllers;

use App\Services\KingGameService;
use Illuminate\Http\Request;

class KingGameController extends Controller
{
    public function show(Request $request)
    {
        $isPremium = $request->user()?->isPremium() ?? false;
        $commands = KingGameService::getCommandPools($isPremium);

        return view('king-game.show', compact('isPremium', 'commands'));
    }
}
