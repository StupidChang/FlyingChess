<?php

namespace App\Http\Controllers;

use App\Models\Board;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    public function index()
    {
        $presetBoards = Board::withCount('squares')
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
