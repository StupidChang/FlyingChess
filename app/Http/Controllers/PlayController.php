<?php

namespace App\Http\Controllers;

use App\Models\Board;
use App\Support\PremiumAccess;
use Illuminate\Http\Request;

class PlayController extends Controller
{
    public function show(Request $request, ?Board $board = null)
    {
        if (! $board) {
            $board = Board::where('is_default', true)->first()
                ?? Board::firstOrFail();
        }

        // Private boards are reachable only via their share_code URL (or by
        // their owner / an admin) — numeric IDs must not be enumerable.
        if (! $board->isPubliclyPlayable() && ! $request->attributes->get('via_share_code')) {
            $user = auth()->user();
            if (! $user || ($board->user_id !== $user->id && ! $user->isAdmin())) {
                abort(404);
            }
        }

        // 付費範本:會員或「看廣告解鎖」時效內都可以玩。
        // 用 PremiumAccess::content() 而不是 isPremium() —— 這裡談的是「現在能不能
        // 玩到這張棋盤」,不是「這張棋盤屬不屬於你」。存一份到自己的收藏
        // (BoardController::cloneTemplate)仍然只認會員資格。
        if ($board->is_premium_template) {
            if (! PremiumAccess::content(auth()->user())) {
                return redirect()->route('premium.index')
                    ->with('error', __('play.err_premium_template_play'));
            }
        }

        $board->load('squares');
        $squares = $board->squaresArray();

        // V8.0 四人版:最多 4 人,兩人一組(index 0-1 為第一組、2-3 為第二組)。
        // 同組兩人都抵達終點才算贏 —— 見 board.js 的 teamOf() / checkTeamWin()。
        $playerCount = (int) $request->query('players', 2);
        $playerCount = max(1, min(4, $playerCount));

        // Resolve path data (fallback to sequential if not set)
        $pathData = $board->path_data;
        if (! $pathData || empty($pathData['all'])) {
            $positions = $board->squares->pluck('position')->sort()->values()->toArray();
            $pathData = ['all' => $positions, 'male' => null, 'female' => null];
        }

        $startWheel = $board->startWheel();
        $captureEnabled = $board->capture_enabled ?? true;

        return view('play.show', compact('board', 'squares', 'playerCount', 'pathData', 'startWheel', 'captureEnabled'));
    }

    public function showByCode(Request $request, string $code)
    {
        $board = Board::where('share_code', strtoupper($code))->firstOrFail();
        $request->attributes->set('via_share_code', true);

        return $this->show($request, $board);
    }
}
