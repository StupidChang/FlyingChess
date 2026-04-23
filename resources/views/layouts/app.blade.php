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

    {{-- GA4: only load for non-premium users --}}
    @if(env('GOOGLE_GA4_ID'))
        @if(!auth()->check() || !auth()->user()->isPremium())
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ env('GOOGLE_GA4_ID') }}"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', '{{ env('GOOGLE_GA4_ID') }}');
        </script>
        @endif
    @endif

    {{-- AdSense global script: only for non-premium and when configured --}}
    @php
        $showAds = !auth()->check() || !auth()->user()->isPremium();
        $adAdapter = config('ads.adapter', 'adsense');
    @endphp
    @if($showAds && $adAdapter === 'adsense' && config('ads.adsense.publisher_id'))
    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client={{ config('ads.adsense.publisher_id') }}" crossorigin="anonymous"></script>
    @endif

    {{-- Theme init (prevent flash) --}}
    <script>
        (function(){
            var t = localStorage.getItem('theme') || 'pink';
            document.documentElement.setAttribute('data-theme', t);
        })();
    </script>
</head>
<body>
<header class="site-header">
    <div class="container">
        <a href="{{ route('home') }}" class="logo">情侶飛行棋</a>

        {{-- Desktop nav — explicit .nav-desktop class; hidden on mobile via .nav-desktop{display:none} in media query --}}
        <nav class="nav-desktop">
            <a href="{{ route('home') }}" class="nav-link">首頁</a>
            <a href="{{ route('games.lobby') }}" class="nav-link nav-play">飛行棋</a>
            <a href="{{ route('truth-dare.lobby') }}" class="nav-link">真心話大冒險</a>
            <a href="{{ route('card-game.show') }}" class="nav-link">撲克牌</a>
            <a href="{{ route('play') }}" class="nav-link">自訂棋盤</a>
            @auth
                <a href="{{ route('boards.index') }}" class="nav-link">我的棋盤</a>
                <span class="nav-user">
                    {{ Auth::user()->name }}
                    @if(Auth::user()->isPremium())
                        <span class="nav-premium">Premium</span>
                    @endif
                </span>
                <form action="{{ route('logout') }}" method="POST" style="display:inline">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline" style="margin-left:4px">登出</button>
                </form>
            @else
                <a href="{{ route('login') }}" class="nav-link">登入</a>
                <a href="{{ route('register') }}" class="btn btn-sm btn-outline-gold" style="margin-left:4px">註冊</a>
            @endauth
            <button class="theme-toggle" onclick="toggleTheme()" title="切換配色">
                <span id="theme-label">粉色</span>
            </button>
        </nav>

        {{-- Mobile hamburger --}}
        <button class="hamburger" onclick="toggleMobileNav()" aria-label="選單">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:22px;height:22px">
                <path fill-rule="evenodd" d="M3 6.75A.75.75 0 013.75 6h16.5a.75.75 0 010 1.5H3.75A.75.75 0 013 6.75ZM3 12a.75.75 0 01.75-.75h16.5a.75.75 0 010 1.5H3.75A.75.75 0 013 12Zm0 5.25a.75.75 0 01.75-.75h16.5a.75.75 0 010 1.5H3.75a.75.75 0 01-.75-.75Z" clip-rule="evenodd"/>
            </svg>
        </button>
    </div>

    {{-- Mobile nav — .nav-mobile is never targeted by the desktop hide rule --}}
    <nav class="nav-mobile" id="mobileNav">
        <a href="{{ route('home') }}" class="nav-link">首頁</a>
        <a href="{{ route('games.lobby') }}" class="nav-link">飛行棋大廳</a>
        <a href="{{ route('truth-dare.lobby') }}" class="nav-link">真心話大冒險</a>
        <a href="{{ route('card-game.show') }}" class="nav-link">撲克牌</a>
        <a href="{{ route('play') }}" class="nav-link">自訂棋盤</a>
        @auth
            <a href="{{ route('boards.index') }}" class="nav-link">我的棋盤</a>
            <a href="{{ route('premium.index') }}" class="nav-link">
                會員中心
                @if(Auth::user()->isPremium())
                    <span class="nav-premium">Premium</span>
                @endif
            </a>
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline btn-full">登出</button>
            </form>
        @else
            <a href="{{ route('login') }}" class="nav-link">登入</a>
            <a href="{{ route('register') }}" class="btn btn-sm btn-outline-gold btn-full">免費註冊</a>
        @endauth
        <button class="theme-toggle" onclick="toggleTheme()">
            <span id="theme-label-m">粉色</span> 切換配色
        </button>
    </nav>
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
            <div class="footer-brand">
                <span class="footer-logo">情侶飛行棋</span>
                <span class="footer-tagline">情侶升溫 · 派對助興 · 越玩越親密</span>
            </div>
            <div class="footer-links">
                <a href="{{ route('home') }}">首頁</a>
                <a href="{{ route('games.lobby') }}">飛行棋</a>
                <a href="{{ route('truth-dare.lobby') }}">真心話大冒險</a>
                <a href="{{ route('card-game.show') }}">撲克牌</a>
                <a href="{{ route('play') }}">自訂棋盤</a>
                @auth
                <a href="{{ route('boards.index') }}">我的棋盤</a>
                <a href="{{ route('premium.index') }}">會員中心</a>
                @else
                <a href="{{ route('register') }}">免費註冊</a>
                @endauth
                <a href="{{ route('legal.privacy') }}" rel="nofollow">隱私權政策</a>
                <a href="{{ route('legal.terms') }}" rel="nofollow">使用條款</a>
            </div>
        </div>
        <p class="footer-copy">&copy; {{ date('Y') }} 情侶飛行棋 — 成人趣味互動遊戲平台</p>
        {{-- Social placeholders --}}
        <div class="footer-social" style="justify-content:center">
            {{-- <a href="#" target="_blank" rel="nofollow noopener">Instagram</a> --}}
            {{-- <a href="#" target="_blank" rel="nofollow noopener">Twitter</a> --}}
        </div>
    </div>
</footer>

<script src="{{ asset('js/app.js') }}"></script>
<script>
// Theme toggle
function toggleTheme() {
    var current = document.documentElement.getAttribute('data-theme') || 'pink';
    var next = current === 'pink' ? 'dark' : 'pink';
    document.documentElement.setAttribute('data-theme', next);
    localStorage.setItem('theme', next);
    updateThemeLabels(next);
}
function updateThemeLabels(t) {
    var label = t === 'dark' ? '暗色' : '粉色';
    var el1 = document.getElementById('theme-label');
    var el2 = document.getElementById('theme-label-m');
    if (el1) el1.textContent = label;
    if (el2) el2.textContent = label;
}
updateThemeLabels(localStorage.getItem('theme') || 'pink');

// Mobile nav toggle
function toggleMobileNav() {
    document.getElementById('mobileNav').classList.toggle('open');
}
</script>
@yield('scripts')
@stack('scripts')
</body>
</html>
