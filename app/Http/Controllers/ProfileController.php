<?php

namespace App\Http\Controllers;

use App\Models\GamePlayer;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    /**
     * 付費會員的紀錄上限。
     *
     * 說是「完整紀錄」,實際仍有上限 —— 一個玩很久的帳號可能累積上萬列,一次全撈
     * 會把整個 profile 頁拖垮。200 場遠超過任何人會往下捲的量,同時保證這個查詢
     * 的成本是有界的。真的有人撞到這個數字時,該做的是分頁而不是把上限調高。
     */
    private const PREMIUM_HISTORY_CAP = 200;

    public function index(Request $request)
    {
        $user = $request->user();
        $boards = $user->boards()->withCount('squares')->latest()->get();

        $isPremium = $user->isPremium();
        $freeLimit = max(1, (int) config('premium.free_history_limit', 5));

        // Play history: rooms this user created or joined while logged in.
        //
        // 用 has('game') 在查詢層過濾,而不是撈回來再 filter:房間被刪掉的殘列
        // 不該算進總數,否則免費會員會看到「共 30 場」但升級後只有 12 場。
        $historyQuery = GamePlayer::has('game')
            /* 連參與者一起載入。少了這行,歷史清單每一列都會為了列出同場玩家
               各發一次查詢(N+1);紀錄一多,個人頁就會明顯變慢。 */
            ->with([
                'game:id,code,game_type,status,created_at,finished_at',
                'game.players:id,game_id,player_name,user_id',
            ])
            ->where('user_id', $user->id)
            ->latest();

        $totalPlays = (clone $historyQuery)->count();

        $playHistory = $historyQuery
            ->limit($isPremium ? self::PREMIUM_HISTORY_CAP : $freeLimit)
            ->get();

        // 時間軸只給付費會員。依「當地日期」分組而不是 UTC ——
        // 深夜玩的那幾場應該落在同一天,不該被時區切成兩天。
        $timeline = $isPremium
            ? $playHistory->groupBy(fn ($p) => $p->created_at->timezone(config('app.timezone'))->format('Y-m-d'))
            : null;

        // 還有幾場被鎖住。0 的話不顯示升級提示 —— 只玩過三場的人看到
        // 「升級解鎖更多」只會覺得莫名其妙。
        $hiddenPlays = $isPremium ? 0 : max(0, $totalPlays - $playHistory->count());

        return view('profile.index', compact(
            'user', 'boards', 'playHistory', 'timeline',
            'isPremium', 'freeLimit', 'totalPlays', 'hiddenPlays'
        ));
    }
}
