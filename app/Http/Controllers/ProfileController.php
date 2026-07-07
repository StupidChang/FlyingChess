<?php

namespace App\Http\Controllers;

use App\Models\GamePlayer;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $boards = $user->boards()->withCount('squares')->latest()->get();

        // Play history: rooms this user created or joined while logged in
        $playHistory = GamePlayer::with('game:id,code,game_type,status,created_at')
            ->where('user_id', $user->id)
            ->latest()
            ->limit(20)
            ->get()
            ->filter(fn ($p) => $p->game !== null);

        return view('profile.index', compact('user', 'boards', 'playHistory'));
    }
}
