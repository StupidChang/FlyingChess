<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $boards = $user->boards()->withCount('squares')->latest()->get();

        return view('profile.index', compact('user', 'boards'));
    }
}
