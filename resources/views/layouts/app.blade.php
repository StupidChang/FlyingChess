<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', '情侶飛行棋 — 成人趣味桌遊')</title>
    <meta name="description" content="@yield('meta_description', '情侶飛行棋 — 線上情趣小遊戲，免費開始玩')">
    <meta name="robots" content="@yield('robots', 'index,follow')">
    <link rel="canonical" href="@yield('canonical', url()->current())">
    <meta property="og:title" content="@yield('og_title', config('app.name'))">
    <meta property="og:description" content="@yield('og_description', '情侶飛行棋 — 線上情趣小遊戲')">
    <meta property="og:url" content="@yield('canonical', url()->current())">
    <meta property="og:type" content="website">
    <meta property="og:image" content="@yield('og_image', asset('images/174655ssvy4mu6pwyllysm.jpg'))">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <link rel="icon" href="{{ asset('images/favicon.svg') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    @yield('styles')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    {{-- AdSense 全域腳本：僅在未使用 TrafficJunky 且有設定 publisher_id 時載入 --}}
    @if(!config('services.trafficjunky.site_id') && config('services.adsense.publisher_id'))
    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client={{ config('services.adsense.publisher_id') }}" crossorigin="anonymous"></script>
    @endif
    <style>
      .ad-unit { width:100%; overflow:hidden; text-align:center; }
      .ad-unit--horizontal { padding:8px 0; background:var(--surface); border-top:1px solid var(--border); border-bottom:1px solid var(--border); }
      .ad-unit--rectangle  { padding:16px 0; }
      .ad-unit--auto       { padding:8px 0; }
    </style>
</head>
<body>
<header class="site-header">
    <div class="container">
        <a href="{{ route('home') }}" class="logo">情侶飛行棋</a>
        <nav>
            <a href="{{ route('home') }}" class="nav-link">首頁</a>
            <a href="{{ route('games.lobby') }}" class="nav-link nav-play">飛行棋大廳</a>
            <a href="{{ route('play') }}" class="nav-link">自訂棋盤</a>
            @auth
                <a href="{{ route('boards.index') }}" class="nav-link">我的棋盤</a>
                <span class="nav-user">{{ Auth::user()->name }}</span>
                <form action="{{ route('logout') }}" method="POST" style="display:inline">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline" style="margin-left:4px">登出</button>
                </form>
            @else
                <a href="{{ route('login') }}" class="nav-link">登入</a>
                <a href="{{ route('register') }}" class="btn btn-sm btn-outline-gold" style="margin-left:4px">註冊</a>
            @endauth
        </nav>
    </div>
</header>

<main>
    @if(session('success'))
        <div class="toast toast-ok">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="toast toast-err">{{ session('error') }}</div>
    @endif
    @yield('content')
</main>

<footer class="site-footer">
    <div class="container">
        <div class="footer-inner">
            <span class="footer-logo">情侶飛行棋</span>
            <span class="footer-copy">雙人同機 · 自訂格子 · 越玩越親密</span>
            <div class="footer-links">
                <a href="{{ route('home') }}">首頁</a>
                <a href="{{ route('play') }}">遊戲</a>
                @auth
                <a href="{{ route('boards.index') }}">我的棋盤</a>
                @else
                <a href="{{ route('register') }}">註冊</a>
                @endauth
                <a href="{{ route('legal.privacy') }}">隱私權政策</a>
                <a href="{{ route('legal.terms') }}">使用條款</a>
            </div>
        </div>
        <div style="text-align:center;margin-top:12px;font-size:.8rem;color:var(--text-dim)">
            &copy; {{ date('Y') }} 情侶飛行棋
        </div>
    </div>
</footer>

<script src="{{ asset('js/app.js') }}"></script>
@yield('scripts')
@stack('scripts')
@include('partials.age-gate')
</body>
</html>
