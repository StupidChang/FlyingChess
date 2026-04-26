<?php

namespace App\Http\Controllers;

use App\Services\WheelGameService;
use Illuminate\Http\Request;

class WheelGameController extends Controller
{
    public function show(Request $request)
    {
        $isPremium = $request->user()?->isPremium() ?? false;
        $segments = WheelGameService::getSegmentPools($isPremium);

        return view('wheel-game.show', compact('isPremium', 'segments'));
    }
}
