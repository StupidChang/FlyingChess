<?php

namespace App\Http\Controllers;

use App\Models\Board;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    public function index()
    {
        // All system boards: preset + templates (user_id=null)
        // Load squares for mini-preview on homepage cards
        $presetBoards = Board::withCount('squares')
            ->with('squares:id,board_id,position,grid_row,grid_col')
            ->whereNull('user_id')
            ->latest()
            ->get();

        $default = $presetBoards->firstWhere('is_default', true);

        $myBoards = Auth::check()
            ? Board::withCount('squares')
                ->where('user_id', Auth::id())
                ->latest()
                ->get()
            : collect();

        return view('home', compact('presetBoards', 'myBoards', 'default'));
    }
}
