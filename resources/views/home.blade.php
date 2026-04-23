@extends('layouts.app')
@section('title', '情侶飛行棋 — 免費線上情趣小遊戲')
@section('meta_description', '情侶專屬飛行棋遊戲，免費在線玩！自訂棋盤、飛行棋對戰，讓愛情更有趣。雙人同機，無需下載，立即開始。')
@section('og_title', '情侶飛行棋 — 免費線上情趣小遊戲')
@section('og_description', '情侶專屬飛行棋遊戲，免費在線玩！自訂棋盤、飛行棋對戰，讓愛情更有趣。')
@section('canonical', route('home'))
@section('content')

{{-- Hero 區 --}}
<section class="hero-section">
    <div class="hero-inner">
        <h1 class="hero-title">情侶飛行棋 — 讓感情更有趣</h1>
        <p class="hero-sub">線上雙人棋盤遊戲，免費開始，格子任務自己設計，一起玩才夠親密</p>
        <div class="hero-btns">
            <a href="{{ route('games.lobby') }}" class="btn btn-gold btn-xl">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5 inline-block">
                    <path fill-rule="evenodd" d="M4.5 5.653c0-1.427 1.529-2.33 2.779-1.643l11.54 6.347c1.295.712 1.295 2.573 0 3.286L7.28 19.99c-1.25.687-2.779-.217-2.779-1.643V5.653Z" clip-rule="evenodd"/>
                </svg>
                立即玩飛行棋
            </a>
            @if($default)
            <a href="{{ route('play.board', $default) }}" class="btn btn-outline-gold btn-xl">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5 inline-block">
                    <path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25ZM6.262 6.072a8.25 8.25 0 1 0 10.562-.766 4.5 4.5 0 0 1-1.318 1.357L14.25 7.5l.165.33a.809.809 0 0 1-1.086 1.085l-.604-.302a1.125 1.125 0 0 0-1.298.21l-.132.131c-.439.44-.439 1.152 0 1.591l.296.296c.256.257.622.374.98.314l1.17-.195c.323-.054.654.036.905.245l1.33 1.108c.32.267.46.694.358 1.1a8.7 8.7 0 0 1-2.288 4.04l-.723.724a1.125 1.125 0 0 1-1.298.21l-.153-.076a1.125 1.125 0 0 1-.622-1.006v-1.089c0-.298-.119-.585-.33-.796l-1.347-1.347a1.125 1.125 0 0 1 0-1.59l.296-.297a1.125 1.125 0 0 0 0-1.59l-.296-.296a1.125 1.125 0 0 1 0-1.591l.296-.296c.256-.256.622-.374.98-.313l1.17.195c.323.054.654-.036.905-.244l1.33-1.108c.32-.267.46-.694.358-1.1a8.7 8.7 0 0 1-2.288-4.04Z" clip-rule="evenodd"/>
                </svg>
                玩自訂棋盤
            </a>
            @else
            <a href="{{ route('play') }}" class="btn btn-outline-gold btn-xl">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5 inline-block">
                    <path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25ZM6.262 6.072a8.25 8.25 0 1 0 10.562-.766 4.5 4.5 0 0 1-1.318 1.357L14.25 7.5l.165.33a.809.809 0 0 1-1.086 1.085l-.604-.302a1.125 1.125 0 0 0-1.298.21l-.132.131c-.439.44-.439 1.152 0 1.591l.296.296c.256.257.622.374.98.314l1.17-.195c.323-.054.654.036.905.245l1.33 1.108c.32.267.46.694.358 1.1a8.7 8.7 0 0 1-2.288 4.04l-.723.724a1.125 1.125 0 0 1-1.298.21l-.153-.076a1.125 1.125 0 0 1-.622-1.006v-1.089c0-.298-.119-.585-.33-.796l-1.347-1.347a1.125 1.125 0 0 1 0-1.59l.296-.297a1.125 1.125 0 0 0 0-1.59l-.296-.296a1.125 1.125 0 0 1 0-1.591l.296-.296c.256-.256.622-.374.98-.313l1.17.195c.323.054.654-.036.905-.244l1.33-1.108c.32-.267.46-.694.358-1.1a8.7 8.7 0 0 1-2.288-4.04Z" clip-rule="evenodd"/>
                </svg>
                玩自訂棋盤
            </a>
            @endif
        </div>
    </div>
</section>

{{-- 快速遊戲卡片區 --}}
<section class="game-cards-section container">
    <h2 class="section-title">選擇遊戲模式</h2>
    <div class="game-cards-grid">
        {{-- 飛行棋 --}}
        <article class="game-card">
            <div class="game-card-icon" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:40px;height:40px">
                    <path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25ZM6.262 6.072a8.25 8.25 0 1 0 10.562-.766 4.5 4.5 0 0 1-1.318 1.357L14.25 7.5l.165.33a.809.809 0 0 1-1.086 1.085l-.604-.302a1.125 1.125 0 0 0-1.298.21l-.132.131c-.439.44-.439 1.152 0 1.591l.296.296c.256.257.622.374.98.314l1.17-.195c.323-.054.654.036.905.245l1.33 1.108c.32.267.46.694.358 1.1a8.7 8.7 0 0 1-2.288 4.04l-.723.724a1.125 1.125 0 0 1-1.298.21l-.153-.076a1.125 1.125 0 0 1-.622-1.006v-1.089c0-.298-.119-.585-.33-.796l-1.347-1.347a1.125 1.125 0 0 1 0-1.59l.296-.297a1.125 1.125 0 0 0 0-1.59l-.296-.296a1.125 1.125 0 0 1 0-1.591l.296-.296c.256-.256.622-.374.98-.313l1.17.195c.323.054.654-.036.905-.244l1.33-1.108c.32-.267.46-.694.358-1.1a8.7 8.7 0 0 1-2.288-4.04Z" clip-rule="evenodd"/>
                </svg>
            </div>
            <h3>飛行棋</h3>
            <p>經典飛行棋對戰，每局 2–4 人，支援 AI 電腦對手，不用等朋友也能玩</p>
            <a href="{{ route('games.lobby') }}" class="btn btn-gold btn-full">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5 inline-block">
                    <path fill-rule="evenodd" d="M4.5 5.653c0-1.427 1.529-2.33 2.779-1.643l11.54 6.347c1.295.712 1.295 2.573 0 3.286L7.28 19.99c-1.25.687-2.779-.217-2.779-1.643V5.653Z" clip-rule="evenodd"/>
                </svg>
                進入大廳
            </a>
        </article>

        {{-- 自訂棋盤 --}}
        <article class="game-card">
            <div class="game-card-icon" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:40px;height:40px">
                    <path fill-rule="evenodd" d="M3 6a3 3 0 0 1 3-3h2.25a3 3 0 0 1 3 3v2.25a3 3 0 0 1-3 3H6a3 3 0 0 1-3-3V6Zm9.75 0a3 3 0 0 1 3-3H18a3 3 0 0 1 3 3v2.25a3 3 0 0 1-3 3h-2.25a3 3 0 0 1-3-3V6ZM3 15.75a3 3 0 0 1 3-3h2.25a3 3 0 0 1 3 3V18a3 3 0 0 1-3 3H6a3 3 0 0 1-3-3v-2.25Zm9.75 0a3 3 0 0 1 3-3H18a3 3 0 0 1 3 3V18a3 3 0 0 1-3 3h-2.25a3 3 0 0 1-3-3v-2.25Z" clip-rule="evenodd"/>
                </svg>
            </div>
            <h3>自訂棋盤遊玩</h3>
            <p>雙人同機在自訂棋盤上對玩，格子任務自己設計，情侶最愛的互動模式</p>
            @if($default)
            <a href="{{ route('play.board', $default) }}" class="btn btn-gold btn-full">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5 inline-block">
                    <path fill-rule="evenodd" d="M4.5 5.653c0-1.427 1.529-2.33 2.779-1.643l11.54 6.347c1.295.712 1.295 2.573 0 3.286L7.28 19.99c-1.25.687-2.779-.217-2.779-1.643V5.653Z" clip-rule="evenodd"/>
                </svg>
                開始遊玩
            </a>
            @else
            <a href="{{ route('play') }}" class="btn btn-gold btn-full">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5 inline-block">
                    <path fill-rule="evenodd" d="M4.5 5.653c0-1.427 1.529-2.33 2.779-1.643l11.54 6.347c1.295.712 1.295 2.573 0 3.286L7.28 19.99c-1.25.687-2.779-.217-2.779-1.643V5.653Z" clip-rule="evenodd"/>
                </svg>
                開始遊玩
            </a>
            @endif
        </article>

        {{-- 棋盤編輯器 --}}
        <article class="game-card">
            <div class="game-card-icon" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:40px;height:40px">
                    <path d="M21.731 2.269a2.625 2.625 0 0 0-3.712 0l-1.157 1.157 3.712 3.712 1.157-1.157a2.625 2.625 0 0 0 0-3.712ZM19.513 8.199l-3.712-3.712-8.4 8.4a5.25 5.25 0 0 0-1.32 2.214l-.8 2.685a.75.75 0 0 0 .933.933l2.685-.8a5.25 5.25 0 0 0 2.214-1.32l8.4-8.4Z"/>
                </svg>
            </div>
            <h3>棋盤編輯器</h3>
            <p>登入後免費建立棋盤，自訂每個格子的文字與類型，設計屬於你們的專屬版本</p>
            @auth
            <a href="{{ route('boards.index') }}" class="btn btn-gold btn-full">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5 inline-block">
                    <path d="M21.731 2.269a2.625 2.625 0 0 0-3.712 0l-1.157 1.157 3.712 3.712 1.157-1.157a2.625 2.625 0 0 0 0-3.712ZM19.513 8.199l-3.712-3.712-8.4 8.4a5.25 5.25 0 0 0-1.32 2.214l-.8 2.685a.75.75 0 0 0 .933.933l2.685-.8a5.25 5.25 0 0 0 2.214-1.32l8.4-8.4Z"/>
                </svg>
                我的棋盤
            </a>
            @else
            <a href="{{ route('register') }}" class="btn btn-outline-gold btn-full">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5 inline-block">
                    <path fill-rule="evenodd" d="M12 3.75a.75.75 0 0 1 .75.75v6.75h6.75a.75.75 0 0 1 0 1.5h-6.75v6.75a.75.75 0 0 1-1.5 0v-6.75H4.5a.75.75 0 0 1 0-1.5h6.75V4.5a.75.75 0 0 1 .75-.75Z" clip-rule="evenodd"/>
                </svg>
                免費註冊開始建立
            </a>
            @endauth
        </article>
    </div>
</section>

{{-- 廣告版位：遊戲卡片下方 --}}
@include('partials.ad-unit', ['zone' => 'home_mid'])

{{-- 分享碼快速加入 --}}
<section class="share-join-section">
    <div class="container">
        <div class="share-join-inner">
            <span class="share-join-label">有分享碼？</span>
            <form action="" method="GET" id="share-join-form" style="display:flex;gap:8px;flex-wrap:wrap">
                <input type="text" id="share-code-input" name="code" class="form-control"
                       placeholder="輸入 8 碼分享碼" maxlength="10"
                       style="width:180px;text-transform:uppercase">
                <button type="submit" class="btn btn-gold">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5 inline-block">
                        <path fill-rule="evenodd" d="M4.5 5.653c0-1.427 1.529-2.33 2.779-1.643l11.54 6.347c1.295.712 1.295 2.573 0 3.286L7.28 19.99c-1.25.687-2.779-.217-2.779-1.643V5.653Z" clip-rule="evenodd"/>
                    </svg>
                    進入棋盤
                </button>
            </form>
        </div>
    </div>
</section>

{{-- 預設棋盤庫 --}}
<section class="boards-section container">
    <div class="section-head">
        <h2>預設棋盤</h2>
    </div>
    <div class="boards-grid">
        @forelse($presetBoards as $board)
        <article class="board-card">
            <div class="board-card-body">
                <h3>{{ $board->name }}</h3>
                @if($board->description)<p>{{ $board->description }}</p>@endif
                <span class="badge-squares">{{ $board->squares_count }} 格</span>
                @if($board->is_default)<span class="badge-default">預設</span>@endif
            </div>
            <div class="board-card-foot">
                <a href="{{ route('play.board', $board) }}" class="btn btn-sm btn-gold">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5 inline-block">
                        <path fill-rule="evenodd" d="M4.5 5.653c0-1.427 1.529-2.33 2.779-1.643l11.54 6.347c1.295.712 1.295 2.573 0 3.286L7.28 19.99c-1.25.687-2.779-.217-2.779-1.643V5.653Z" clip-rule="evenodd"/>
                    </svg>
                    玩
                </a>
            </div>
        </article>
        @empty
        <div class="empty-notice">尚無預設棋盤</div>
        @endforelse
    </div>
</section>

{{-- 廣告版位 2：棋盤列表與遊戲說明之間 --}}
@include('partials.ad-unit', ['zone' => 'home_mid'])

{{-- 我的棋盤（登入用戶） --}}
@auth
<section class="boards-section container" style="padding-top:0">
    <div class="section-head">
        <h2>我的棋盤</h2>
        <a href="{{ route('boards.create') }}" class="btn btn-sm btn-outline-gold">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5 inline-block">
                <path fill-rule="evenodd" d="M12 3.75a.75.75 0 0 1 .75.75v6.75h6.75a.75.75 0 0 1 0 1.5h-6.75v6.75a.75.75 0 0 1-1.5 0v-6.75H4.5a.75.75 0 0 1 0-1.5h6.75V4.5a.75.75 0 0 1 .75-.75Z" clip-rule="evenodd"/>
            </svg>
            新建棋盤
        </a>
    </div>
    <div class="boards-grid">
        @forelse($myBoards as $board)
        <article class="board-card">
            <div class="board-card-body">
                <h3>{{ $board->name }}</h3>
                @if($board->description)<p>{{ $board->description }}</p>@endif
                <span class="badge-squares">{{ $board->squares_count }} 格</span>
                <span class="share-code-badge" title="分享碼" data-code="{{ $board->share_code }}"
                      onclick="copyShareCode(this)" style="cursor:pointer">
                    {{ $board->share_code }}
                </span>
            </div>
            <div class="board-card-foot">
                <a href="{{ route('play.board', $board) }}" class="btn btn-sm btn-gold">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5 inline-block">
                        <path fill-rule="evenodd" d="M4.5 5.653c0-1.427 1.529-2.33 2.779-1.643l11.54 6.347c1.295.712 1.295 2.573 0 3.286L7.28 19.99c-1.25.687-2.779-.217-2.779-1.643V5.653Z" clip-rule="evenodd"/>
                    </svg>
                    玩
                </a>
                <a href="{{ route('boards.edit', $board) }}" class="btn btn-sm btn-outline">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5 inline-block">
                        <path d="M21.731 2.269a2.625 2.625 0 0 0-3.712 0l-1.157 1.157 3.712 3.712 1.157-1.157a2.625 2.625 0 0 0 0-3.712ZM19.513 8.199l-3.712-3.712-8.4 8.4a5.25 5.25 0 0 0-1.32 2.214l-.8 2.685a.75.75 0 0 0 .933.933l2.685-.8a5.25 5.25 0 0 0 2.214-1.32l8.4-8.4Z"/>
                    </svg>
                    編輯
                </a>
                <form action="{{ route('boards.destroy', $board) }}" method="POST"
                      onsubmit="return confirm('確定刪除？')">
                    @csrf @method('DELETE')
                    <button class="btn btn-sm btn-danger">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5 inline-block">
                            <path fill-rule="evenodd" d="M5.47 5.47a.75.75 0 0 1 1.06 0L12 10.94l5.47-5.47a.75.75 0 1 1 1.06 1.06L13.06 12l5.47 5.47a.75.75 0 1 1-1.06 1.06L12 13.06l-5.47 5.47a.75.75 0 0 1-1.06-1.06L10.94 12 5.47 6.53a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd"/>
                        </svg>
                    </button>
                </form>
            </div>
        </article>
        @empty
        <div class="empty-notice">
            還沒有棋盤，
            <a href="{{ route('boards.create') }}" style="color:var(--gold)">立即建立一個</a>
        </div>
        @endforelse
    </div>
</section>
@endauth

<section class="features-section container">
    <h2 class="text-center">為什麼選擇情侶飛行棋</h2>
    <div class="features-grid">
        <div class="feature">
            <div class="f-icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:36px;height:36px;color:var(--gold)">
                    <path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12Zm13.36-1.814a.75.75 0 1 0-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 0 0-1.06 1.06l2.25 2.25a.75.75 0 0 0 1.14-.094l3.75-5.25Z" clip-rule="evenodd"/>
                </svg>
            </div>
            <h3>免費開始</h3>
            <p>無需下載、無需付費，開啟瀏覽器即可立即遊玩，飛行棋與自訂棋盤全程免費</p>
        </div>
        <div class="feature">
            <div class="f-icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:36px;height:36px;color:var(--gold)">
                    <path d="m11.645 20.91-.007-.003-.022-.012a15.247 15.247 0 0 1-.383-.218 25.18 25.18 0 0 1-4.244-3.17C4.688 15.36 2.25 12.174 2.25 8.25 2.25 5.322 4.714 3 7.688 3A5.5 5.5 0 0 1 12 5.052 5.5 5.5 0 0 1 16.313 3c2.973 0 5.437 2.322 5.437 5.25 0 3.925-2.438 7.111-4.739 9.256a25.175 25.175 0 0 1-4.244 3.17 15.247 15.247 0 0 1-.383.219l-.022.012-.007.004-.003.001a.752.752 0 0 1-.704 0l-.003-.001Z"/>
                </svg>
            </div>
            <h3>情侶專屬</h3>
            <p>為情侶設計的棋盤任務系統，獨家支援男女生差異路徑，讓互動更有趣、更親密</p>
        </div>
        <div class="feature">
            <div class="f-icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:36px;height:36px;color:var(--gold)">
                    <path d="M21.731 2.269a2.625 2.625 0 0 0-3.712 0l-1.157 1.157 3.712 3.712 1.157-1.157a2.625 2.625 0 0 0 0-3.712ZM19.513 8.199l-3.712-3.712-8.4 8.4a5.25 5.25 0 0 0-1.32 2.214l-.8 2.685a.75.75 0 0 0 .933.933l2.685-.8a5.25 5.25 0 0 0 2.214-1.32l8.4-8.4Z"/>
                </svg>
            </div>
            <h3>自訂玩法</h3>
            <p>棋盤格子文字、顏色、飛行目標全部可自訂，打造只屬於你們兩人的專屬版本</p>
        </div>
    </div>
</section>

{{-- FAQ 區塊（SEO 結構化內容） --}}
<section class="faq-section container" style="max-width:800px;padding:48px 16px">
    <h2 class="text-center" style="margin-bottom:32px">常見問題</h2>
    <div class="faq-list">
        <details class="faq-item" open>
            <summary class="faq-question">情侶飛行棋是什麼遊戲？</summary>
            <div class="faq-answer">
                <p>情侶飛行棋是一款專為情侶設計的線上棋盤遊戲，結合了經典飛行棋的擲骰對戰玩法與可自訂格子任務的互動棋盤模式。玩家可以在格子上設計真心話、挑戰、喝酒等各種任務，讓每一局都充滿驚喜與互動，幫助情侶增進感情。</p>
            </div>
        </details>
        <details class="faq-item">
            <summary class="faq-question">需要付費才能玩嗎？</summary>
            <div class="faq-answer">
                <p>完全免費。飛行棋對戰、自訂棋盤遊玩、棋盤編輯器全部免費使用，不需下載任何應用程式，開啟瀏覽器即可立即開始。部分進階功能可能未來推出付費方案，但基本遊玩體驗永久免費。</p>
            </div>
        </details>
        <details class="faq-item">
            <summary class="faq-question">如何自訂棋盤？</summary>
            <div class="faq-answer">
                <p>免費註冊帳號後，即可進入棋盤編輯器，自由設計每個格子的文字內容、類型（如真心話、挑戰、移動、特殊格等）與顏色。編輯完成後可透過分享碼與對方共用棋盤，或直接開始遊玩。</p>
            </div>
        </details>
        <details class="faq-item">
            <summary class="faq-question">遊戲支援幾人遊玩？</summary>
            <div class="faq-answer">
                <p>飛行棋模式支援 2 至 4 位玩家，可邀請朋友或加入 AI 電腦對手填補空位，不需等人也能開始。自訂棋盤模式目前為雙人同機對玩，適合面對面的情侶互動。</p>
            </div>
        </details>
    </div>
</section>
@endsection

@section('scripts')
<script>
function copyShareCode(el) {
    const code = el.dataset.code;
    navigator.clipboard.writeText(code).then(() => {
        const orig = el.textContent;
        el.textContent = '已複製！';
        setTimeout(() => { el.textContent = orig; }, 1500);
    });
}

document.getElementById('share-join-form').addEventListener('submit', function(e) {
    e.preventDefault();
    const code = document.getElementById('share-code-input').value.trim().toUpperCase();
    if (code.length < 4) return;
    window.location.href = '/play/share/' + code;
});
</script>
<script type="application/ld+json">
{
  "@@context": "https://schema.org",
  "@@type": "WebSite",
  "name": "情侶飛行棋",
  "url": "{{ url('/') }}",
  "description": "情侶專屬飛行棋線上遊戲，支援自訂棋盤與多種遊戲模式，免費開始，雙人互動",
  "sameAs": []
}
</script>
@endsection
