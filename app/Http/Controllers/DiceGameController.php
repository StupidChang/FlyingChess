<?php

namespace App\Http\Controllers;

use App\Services\DiceGameService;
use App\Support\PremiumAccess;
use Illuminate\Http\Request;

class DiceGameController extends Controller
{
    public function show(Request $request)
    {
        // 看廣告解鎖的時限內,訪客也算有內容權限 —— 見 App\Support\PremiumAccess。
        $isPremium = PremiumAccess::content($request->user());
        $dice = DiceGameService::getBuiltInDice($isPremium);

        // Phase 2 fills this with the logged-in user's saved custom dice.
        $customDice = [];
        if ($user = $request->user()) {
            $customDice = $user->dice()
                ->orderBy('category')
                ->get()
                ->map(fn ($d) => [
                    'id' => 'custom_'.$d->id,
                    'cat' => $d->category,
                    'intensity' => null,
                    'premium' => false,
                    'locked' => false,
                    'custom' => true,
                    'name' => $d->name,
                    'faces' => $d->faces,
                ])->all();
        }

        return view('dice-game.show', compact('isPremium', 'dice', 'customDice'));
    }
}
