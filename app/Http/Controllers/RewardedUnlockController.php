<?php

namespace App\Http\Controllers;

use App\Support\PremiumAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 看廣告換一段時間的付費內容。
 *
 * 兩段式:start 發一張憑證並記下伺服器時間,claim 拿憑證回來兌換。分兩段的目的
 * 是讓「最短觀看秒數」由伺服器判斷 —— 前端自己算的秒數沒有任何意義。
 *
 * 這條路徑目前仍然信任前端說「我看完了」,實際上線前要換成聯播網的
 * server-to-server reward callback,見 PremiumAccess::issueAdToken() 的說明。
 */
class RewardedUnlockController extends Controller
{
    public function start(Request $request): JsonResponse
    {
        // 已經有權限的人不用再看一次 —— 付費會員按到這裡是 UI 出錯,
        // 但也沒必要報錯,直接回目前狀態就好。
        if ($request->user()?->isPremium()) {
            return response()->json(['already' => true]);
        }

        return response()->json([
            'token' => PremiumAccess::issueAdToken(),
            'minWatchSeconds' => (int) config('premium.rewarded.min_watch_seconds', 15),
        ]);
    }

    public function claim(Request $request): JsonResponse
    {
        $secondsLeft = PremiumAccess::redeem($request->input('token'));

        if ($secondsLeft <= 0) {
            // 沒說是憑證錯還是看太快 —— 對正常使用者這兩者的處置一樣(重看一次),
            // 對想繞的人則少給一點線索。
            return response()->json(['ok' => false], 422);
        }

        return response()->json([
            'ok' => true,
            'secondsLeft' => $secondsLeft,
            'minutes' => PremiumAccess::rewardedMinutes(),
        ]);
    }
}
