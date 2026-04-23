<?php

namespace App\Http\Controllers;

use App\Models\Board;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function index(): Response
    {
        $boards = Board::whereNotNull('share_code')->get();

        return response()
            ->view('sitemap.index', compact('boards'))
            ->header('Content-Type', 'text/xml; charset=utf-8');
    }
}
