<?php

namespace App\Http\Controllers;

use App\Models\Board;
use Illuminate\Http\Request;

class PlayController extends Controller
{
    public function show(Request $request, ?Board $board = null)
    {
        if (!$board) {
            $board = Board::where('is_default', true)->first()
                ?? Board::firstOrFail();
        }

        $board->load('squares');
        $squares = $board->squaresArray();

        $playerCount = (int) $request->query('players', 2);
        $playerCount = max(1, min(2, $playerCount));

        // Resolve path data (fallback to sequential if not set)
        $pathData = $board->path_data;
        if (!$pathData || empty($pathData['all'])) {
            $positions = $board->squares->pluck('position')->sort()->values()->toArray();
            $pathData  = ['all' => $positions, 'male' => null, 'female' => null];
        }

        return view('play.show', compact('board', 'squares', 'playerCount', 'pathData'));
    }

    public function showByCode(Request $request, string $code)
    {
        $board = Board::where('share_code', strtoupper($code))->firstOrFail();
        return $this->show($request, $board);
    }
}
