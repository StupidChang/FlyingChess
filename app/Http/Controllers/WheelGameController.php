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

    /**
     * 純轉盤(指人器):手機平放桌面,指針朝外隨機指向在座的某個人。
     * 盤面刻意不放任何文字或題目 —— 它只決定「指到誰」,不決定要做什麼,
     * 所以不需要 segment 題庫,也不需要 premium 判斷。
     */
    public function pure()
    {
        return view('wheel-game.pure');
    }
}
