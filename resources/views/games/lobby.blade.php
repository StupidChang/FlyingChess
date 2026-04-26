@extends('layouts.app')

@section('title', '飛行棋大廳 — 情侶飛行棋')
@section('meta_description', '瀏覽各種飛行棋棋盤，選擇喜歡的棋盤開始遊戲！經典 Ludo 飛行棋，情侶必玩。')
@section('og_title', '飛行棋大廳 — 情侶飛行棋')
@section('og_description', '瀏覽各種飛行棋棋盤，選擇喜歡的棋盤開始遊戲！')
@section('canonical', route('games.lobby'))

@section('content')
<div class="container" style="padding-top:32px;padding-bottom:32px;min-height:calc(100vh - 56px)">
    <div style="text-align:center;margin-bottom:32px">
        <span class="section-label">飛行棋大廳</span>
        <h1 class="section-title">選擇棋盤開始遊戲</h1>
        <p class="section-desc" style="max-width:480px;margin:0 auto">瀏覽各種棋盤模板，找到你喜歡的開始遊玩</p>
    </div>

    {{-- Board Grid --}}
    @if($boards->isEmpty())
        <div class="empty-notice" style="text-align:center;padding:40px">
            <p>目前沒有可用的棋盤</p>
        </div>
    @else
        <div class="boards-grid">
            @foreach($boards as $board)
            <article class="board-card">
                <div class="board-card-body">
                    @php
                        $shape = 'cross';
                        if ($board->canvas_rows == 7 && $board->canvas_cols == 7) $shape = 'square';
                        elseif ($board->canvas_rows == 5 && $board->canvas_cols == 9) $shape = 'rect';
                    @endphp
                    <div class="board-mini-preview shape-{{ $shape }}">
                        @foreach($board->squares as $sq)
                            <div class="board-dot{{ $sq->position === 0 ? ' dot-start' : '' }}{{ $sq->position === $board->squares->count() - 1 ? ' dot-end' : '' }}"
                                 style="grid-row:{{ $sq->grid_row }};grid-column:{{ $sq->grid_col }}"></div>
                        @endforeach
                    </div>
                    <h3>{{ $board->name }}</h3>
                    @if($board->description)<p>{{ $board->description }}</p>@endif
                    <div style="display:flex;gap:4px;flex-wrap:wrap;margin-top:4px">
                        <span class="badge-squares">{{ $board->squares_count }} 格</span>
                        @if($board->is_default)<span class="badge-default">預設</span>@endif
                        @if($board->is_premium_template)<span class="badge-premium">Premium</span>@endif
                        @if($board->is_template && !$board->is_premium_template)<span class="badge-free">免費模板</span>@endif
                    </div>
                </div>
                <div class="board-card-foot">
                    @if($board->is_premium_template && (!auth()->check() || !auth()->user()->isPremium()))
                        <a href="{{ route('premium.index') }}" class="btn btn-sm btn-outline" title="Premium 專屬">升級解鎖</a>
                    @else
                        <a href="{{ route('play.board', $board) }}" class="btn btn-sm btn-gold">開始遊戲</a>
                    @endif
                </div>
            </article>
            @endforeach
        </div>

        <div style="margin-top:24px">
            {{ $boards->links() }}
        </div>
    @endif

    {{-- Other Games --}}
    <hr class="section-divider">
    <div style="text-align:center;margin-bottom:24px">
        <h2 class="section-title" style="font-size:1.2rem">更多遊戲</h2>
    </div>
    <div class="game-cards-grid" style="max-width:720px;margin:0 auto">
        <article class="game-card">
            <div class="game-card-icon" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:40px;height:40px">
                    <path d="M4 3a2 2 0 00-2 2v14a2 2 0 002 2h16a2 2 0 002-2V5a2 2 0 00-2-2H4zm1 2h2v2H5V5zm12 0h2v2h-2V5zM9.5 7.5a4.5 4.5 0 110 9 4.5 4.5 0 010-9zm5 0a4.5 4.5 0 110 9 4.5 4.5 0 010-9zM5 17h2v2H5v-2zm12 0h2v2h-2v-2z"/>
                </svg>
            </div>
            <h3>情侶撲克牌</h3>
            <p>2-6 人抽牌配對，牌大的指揮、牌小的服從</p>
            <a href="{{ route('card-game.show') }}" class="btn btn-gold btn-full">開始玩</a>
        </article>

        <article class="game-card">
            <div class="game-card-icon" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:40px;height:40px">
                    <path d="M11.645 20.91l-.007-.003-.022-.012a15.247 15.247 0 01-.383-.218 25.18 25.18 0 01-4.244-3.17C4.688 15.36 2.25 12.174 2.25 8.25 2.25 5.322 4.714 3 7.688 3A5.5 5.5 0 0112 5.052 5.5 5.5 0 0116.313 3c2.973 0 5.437 2.322 5.437 5.25 0 3.925-2.438 7.111-4.739 9.256a25.175 25.175 0 01-4.244 3.17 15.247 15.247 0 01-.383.219l-.022.012-.007.004-.003.001a.752.752 0 01-.704 0l-.003-.001Z"/>
                </svg>
            </div>
            <h3>真心話大冒險</h3>
            <p>經典派對遊戲，輪流抽取挑戰</p>
            <a href="{{ route('truth-dare.lobby') }}" class="btn btn-gold btn-full">開始玩</a>
        </article>

        @auth
        <article class="game-card">
            <div class="game-card-icon" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:40px;height:40px">
                    <path d="M21.731 2.269a2.625 2.625 0 00-3.712 0l-1.157 1.157 3.712 3.712 1.157-1.157a2.625 2.625 0 000-3.712ZM19.513 8.199l-3.712-3.712-8.4 8.4a5.25 5.25 0 00-1.32 2.214l-.8 2.685a.75.75 0 00.933.933l2.685-.8a5.25 5.25 0 002.214-1.32l8.4-8.4Z"/>
                </svg>
            </div>
            <h3>我的棋盤</h3>
            <p>建立和編輯專屬自訂棋盤</p>
            <a href="{{ route('boards.index') }}" class="btn btn-gold btn-full">管理棋盤</a>
        </article>
        @endauth
    </div>
</div>

{{-- Lobby sidebar ad --}}
<div class="ad-sidebar-wrap" style="margin-top:24px">
    @include('partials.ad-unit', ['zone' => 'lobby_side'])
</div>
@endsection
