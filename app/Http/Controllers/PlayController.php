<?php

namespace App\Http\Controllers;

use App\Models\Board;
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

        // Premium templates require premium membership
        if ($board->is_premium_template) {
            if (! auth()->check() || ! auth()->user()->isPremium()) {
                return redirect()->route('premium.index')
                    ->with('error', __('play.err_premium_template_play'));
            }
        }

        $board->load('squares');
        $squares = $board->squaresArray();

        $playerCount = (int) $request->query('players', 2);
        $playerCount = max(1, min(2, $playerCount));

        // Resolve path data (fallback to sequential if not set)
        $pathData = $board->path_data;
        if (! $pathData || empty($pathData['all'])) {
            $positions = $board->squares->pluck('position')->sort()->values()->toArray();
            $pathData = ['all' => $positions, 'male' => null, 'female' => null];
        }

        return view('play.show', compact('board', 'squares', 'playerCount', 'pathData'));
    }

    public function showByCode(Request $request, string $code)
    {
        $board = Board::where('share_code', strtoupper($code))->firstOrFail();
        $request->attributes->set('via_share_code', true);

        return $this->show($request, $board);
    }
}
