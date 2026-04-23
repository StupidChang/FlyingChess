@extends('layouts.app')
@section('title', '棋盤模板 — 情侶飛行棋')
@section('meta_description', '精選棋盤模板，一鍵套用開始玩！免費模板與 Premium 專屬模板，情侶升溫、派對助興。')
@section('content')

<div class="boards-section container">
    <div class="section-head">
        <h1 style="color:var(--gold)">棋盤模板</h1>
    </div>
    <p style="color:var(--text-dim);margin-bottom:24px">選擇模板一鍵複製，開始自訂你的專屬棋盤。</p>

    <div class="boards-grid">
        @foreach($templates as $board)
        <article class="board-card {{ $board->is_premium_template ? 'template-lock' : '' }}">
            @if($board->is_premium_template)
                <span class="template-lock-badge">Premium 模板</span>
            @endif
            <div class="board-card-body">
                <h3>{{ $board->name }}</h3>
                @if($board->description)<p>{{ $board->description }}</p>@endif
                <span class="badge-squares">{{ $board->squares_count }} 格</span>
                @if($board->is_premium_template)
                    <span class="badge-premium">Premium</span>
                @else
                    <span class="badge-free">免費</span>
                @endif
            </div>
            <div class="board-card-foot">
                <a href="{{ route('boards.template.preview', $board) }}" class="btn btn-sm btn-outline">預覽</a>
                @if($board->is_premium_template)
                    @auth
                        @if(auth()->user()->isPremium())
                            <form action="{{ route('boards.template.clone', $board) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-gold">使用模板</button>
                            </form>
                        @else
                            <a href="{{ route('premium.index') }}" class="btn btn-sm btn-gold">升級會員解鎖</a>
                        @endif
                    @else
                        <a href="{{ route('premium.index') }}" class="btn btn-sm btn-gold">升級會員解鎖</a>
                    @endauth
                @else
                    @auth
                        <form action="{{ route('boards.template.clone', $board) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-gold">使用模板</button>
                        </form>
                    @else
                        <a href="{{ route('register') }}" class="btn btn-sm btn-outline-gold">註冊後使用</a>
                    @endauth
                @endif
            </div>
        </article>
        @endforeach
    </div>
</div>
@endsection
