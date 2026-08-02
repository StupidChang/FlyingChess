@php
    use App\Support\LocaleHelper;
    $currentLocale = app()->getLocale();
    $currentHreflang = LocaleHelper::hreflang($currentLocale) ?? 'zh-TW';
    $defaultLocale = LocaleHelper::defaultLocale();
@endphp
<!DOCTYPE html>
<html lang="{{ $currentHreflang }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', __('seo.home_title'))</title>
    <meta name="description" content="@yield('meta_description', __('seo.home_description'))">
    <meta name="robots" content="@yield('robots', 'index,follow')">
    {{-- 限制級自我標示。RTA 是家長控管軟體與過濾服務讀取的業界標準標籤,
         廣告聯播網審核成人站台時也會看。純標示,不影響一般瀏覽。 --}}
    <meta name="rating" content="RTA-5042-1996-1400-1577-RTA">
    <meta name="rating" content="adult">
    {{-- 站長工具的驗證。Search Console 是「查詢工具」不是廣告產品,成人站一樣
         用得到 —— 而且沒有它就完全看不到 Google 到底收錄了什麼、卡在哪裡。
         拿到驗證碼之後設 .env 的 GOOGLE_SITE_VERIFICATION / BING_SITE_VERIFICATION
         就會出現在這裡,不用改程式。 --}}
    @if(config('services.google.site_verification'))
    <meta name="google-site-verification" content="{{ config('services.google.site_verification') }}">
    @endif
    @if(config('services.bing.site_verification'))
    <meta name="msvalidate.01" content="{{ config('services.bing.site_verification') }}">
    @endif
    <link rel="canonical" href="@yield('canonical', LocaleHelper::localizedUrl($currentLocale, request()->path()))">
    @foreach (LocaleHelper::readyLocales() as $locale => $meta)
        <link rel="alternate" hreflang="{{ $meta['hreflang'] }}" href="{{ LocaleHelper::localizedUrl($locale, request()->path()) }}">
    @endforeach
    <link rel="alternate" hreflang="x-default" href="{{ LocaleHelper::localizedUrl($defaultLocale, request()->path()) }}">
    <meta property="og:title" content="@yield('og_title', config('app.name'))">
    <meta property="og:description" content="@yield('og_description', __('seo.home_description'))">
    <meta property="og:url" content="@yield('canonical', LocaleHelper::localizedUrl($currentLocale, request()->path()))">
    <meta property="og:locale" content="{{ str_replace('-', '_', $currentHreflang) }}">
    @foreach (LocaleHelper::readyLocales() as $locale => $meta)
        @if ($locale !== $currentLocale)
            <meta property="og:locale:alternate" content="{{ str_replace('-', '_', $meta['hreflang']) }}">
        @endif
    @endforeach
    <meta property="og:type" content="website">
    <meta property="og:image" content="@yield('og_image', asset('images/174655ssvy4mu6pwyllysm.jpg'))">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    {{-- 粉色愛心 icon:SVG 給現代瀏覽器,ico 作為舊版與 /favicon.ico 直接請求的後備 --}}
    <link rel="icon" type="image/svg+xml" href="{{ asset_v('images/favicon.svg') }}">
    <link rel="icon" type="image/x-icon" sizes="16x16 32x32 48x48" href="{{ asset_v('favicon.ico') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset_v('images/apple-touch-icon.png') }}">
    <meta name="theme-color" content="#f43f5e">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+TC:wght@400;500;600;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset_v('css/app.css') }}">
    {{-- 廣告版位的投放輔助。必須在版位的 inline script 之前就緒,所以不加 defer。
         檔案很小,而且沒有廣告的頁面也只是多一個空函式。 --}}
    <script src="{{ asset_v('js/ads.js') }}"></script>
    @yield('styles')
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- GA4: only load for non-premium users --}}
    @if(config('services.ga4.id'))
        @if(!auth()->check() || !auth()->user()->isPremium())
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ config('services.ga4.id') }}"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', '{{ config('services.ga4.id') }}');
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

    {{-- 結構化資料:Organization 每頁都出,頁面自己的 schema 走 @yield('schema') --}}
    @include('partials.schema-org')
    @yield('schema')
</head>
<body>
<header class="site-header">
    <div class="container">
        <a href="{{ route('home') }}" class="logo">@include('partials.heart-icon')<span>{{ __('ui.site_name') }}</span></a>

        {{-- Desktop nav — explicit .nav-desktop class; hidden on mobile via .nav-desktop{display:none} in media query --}}
        <nav class="nav-desktop">
            <a href="{{ route('home') }}" class="nav-link">{{ __('ui.home') }}</a>
            <div class="nav-dropdown">
                <a href="{{ route('game-hall.index') }}" class="nav-link nav-play nav-dropdown-toggle" aria-haspopup="true">{{ __('games.lobby') }}</a>
                <div class="nav-dropdown-menu">
                    <a href="{{ route('games.lobby') }}">{{ __('games.flying_chess') }}</a>
                    <a href="{{ route('truth-dare.lobby') }}">{{ __('games.truth_dare') }}</a>
                    <a href="{{ route('card-game.show') }}">{{ __('games.card_game') }}</a>
                    <a href="{{ route('dice-game.show') }}">{{ __('games.dice_game') }}</a>
                    <a href="{{ route('king-game.show') }}">{{ __('games.king_game') }}</a>
                    <a href="{{ route('wheel-game.show') }}">{{ __('games.wheel_game') }}</a>
                    <a href="{{ route('wheel.pure') }}">{{ __('games.pure_wheel') }}</a>
                    <a href="{{ route('who-most-likely.show') }}">{{ __('games.who_most_likely') }}</a>
                    <a href="{{ route('trait-test.show') }}">{{ __('traits.title') }}</a>
                <a href="{{ route('custom-wheel.page') }}">{{ __('minigame.cw_title') }}</a>
                    <a href="{{ route('boards.community') }}">{{ __('ui.community_boards') }}</a>
                </div>
            </div>
            @auth
                <div class="nav-dropdown nav-account">
                    <button type="button" class="nav-link nav-dropdown-toggle nav-account-toggle" aria-haspopup="true">
                        {{-- 名字的第一個字當頭像。純文字,不用等任何圖片載入,
                             CJK 與拉丁字母都吃得下。 --}}
                        <span class="nav-avatar" aria-hidden="true">{{ mb_substr(Auth::user()->name, 0, 1) }}</span>
                        <span class="nav-account-name">{{ Auth::user()->name }}</span>
                        @if(Auth::user()->isPremium())
                            <span class="nav-premium">Premium</span>
                        @endif
                    </button>
                    <div class="nav-dropdown-menu nav-account-menu">
                        <a href="{{ route('profile.index') }}">
                            <svg class="nav-ico" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M18.685 19.097A9.723 9.723 0 0021.75 12c0-5.385-4.365-9.75-9.75-9.75S2.25 6.615 2.25 12a9.723 9.723 0 003.065 7.097A9.716 9.716 0 0012 21.75a9.716 9.716 0 006.685-2.653zm-12.54-1.285A7.486 7.486 0 0112 15a7.486 7.486 0 015.855 2.812A8.224 8.224 0 0112 20.25a8.224 8.224 0 01-5.855-2.438zM15.75 9a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" clip-rule="evenodd"/></svg>
                            <span>{{ __('ui.profile') }}</span>
                        </a>
                        @if(Auth::user()->isAdmin())
                            <a href="{{ route('admin.dashboard') }}" style="color:var(--accent)">
                                <svg class="nav-ico" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M12.516 2.17a.75.75 0 00-1.032 0 11.209 11.209 0 01-7.877 3.08.75.75 0 00-.722.515A12.74 12.74 0 002.25 9.75c0 5.942 4.064 10.933 9.563 12.348a.749.749 0 00.374 0c5.499-1.415 9.563-6.406 9.563-12.348 0-1.39-.223-2.73-.635-3.985a.75.75 0 00-.722-.516l-.143.001c-2.996 0-5.717-1.17-7.734-3.08zm3.094 8.016a.75.75 0 10-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 00-1.06 1.06l2.25 2.25a.75.75 0 001.14-.094l3.75-5.25z" clip-rule="evenodd"/></svg>
                                <span>{{ __('ui.admin') }}</span>
                            </a>
                        @endif
                        <a href="{{ route('premium.index') }}">
                            <svg class="nav-ico" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M9 4.5a.75.75 0 01.721.544l.813 2.846a3.75 3.75 0 002.576 2.576l2.846.813a.75.75 0 010 1.442l-2.846.813a3.75 3.75 0 00-2.576 2.576l-.813 2.846a.75.75 0 01-1.442 0l-.813-2.846a3.75 3.75 0 00-2.576-2.576l-2.846-.813a.75.75 0 010-1.442l2.846-.813A3.75 3.75 0 007.466 7.89l.813-2.846A.75.75 0 019 4.5zM18 1.5a.75.75 0 01.728.568l.258 1.036c.236.94.97 1.674 1.91 1.91l1.036.258a.75.75 0 010 1.456l-1.036.258c-.94.236-1.674.97-1.91 1.91l-.258 1.036a.75.75 0 01-1.456 0l-.258-1.036a2.625 2.625 0 00-1.91-1.91l-1.036-.258a.75.75 0 010-1.456l1.036-.258a2.625 2.625 0 001.91-1.91l.258-1.036A.75.75 0 0118 1.5z" clip-rule="evenodd"/></svg>
                            <span>{{ __('premium.page_title') }}</span>
                        </a>
                        <form action="{{ route('logout') }}" method="POST" class="nav-account-logout">
                            @csrf
                            <button type="submit">
                                <svg class="nav-ico" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M7.5 3.75A1.5 1.5 0 006 5.25v13.5a1.5 1.5 0 001.5 1.5h6a1.5 1.5 0 001.5-1.5V15a.75.75 0 011.5 0v3.75a3 3 0 01-3 3h-6a3 3 0 01-3-3V5.25a3 3 0 013-3h6a3 3 0 013 3V9A.75.75 0 0115 9V5.25a1.5 1.5 0 00-1.5-1.5h-6zm10.72 4.72a.75.75 0 011.06 0l3 3a.75.75 0 010 1.06l-3 3a.75.75 0 11-1.06-1.06l1.72-1.72H9a.75.75 0 010-1.5h10.94l-1.72-1.72a.75.75 0 010-1.06z" clip-rule="evenodd"/></svg>
                                <span>{{ __('auth.logout') }}</span>
                            </button>
                        </form>
                    </div>
                </div>
            @else
                <a href="{{ route('login') }}" class="nav-link">{{ __('auth.login_title') }}</a>
                <a href="{{ route('register') }}" class="btn btn-sm btn-outline-gold" style="margin-left:4px">{{ __('auth.register_title') }}</a>
            @endauth
            @include('partials.lang-switcher')
        </nav>

        {{-- Mobile hamburger --}}
        <button class="hamburger" onclick="toggleMobileNav()" aria-label="{{ __('ui.menu') }}">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:22px;height:22px">
                <path fill-rule="evenodd" d="M3 6.75A.75.75 0 013.75 6h16.5a.75.75 0 010 1.5H3.75A.75.75 0 013 6.75ZM3 12a.75.75 0 01.75-.75h16.5a.75.75 0 010 1.5H3.75A.75.75 0 013 12Zm0 5.25a.75.75 0 01.75-.75h16.5a.75.75 0 010 1.5H3.75a.75.75 0 01-.75-.75Z" clip-rule="evenodd"/>
            </svg>
        </button>
    </div>

    {{-- Mobile nav — .nav-mobile is never targeted by the desktop hide rule --}}
    <nav class="nav-mobile" id="mobileNav">
        <a href="{{ route('home') }}" class="nav-link">{{ __('ui.home') }}</a>
        <button class="nav-link nav-mobile-games-toggle" onclick="toggleMobileGames(this)">
            {{ __('games.lobby') }} <span class="toggle-arrow">▾</span>
        </button>
        <div class="nav-mobile-games" id="mobileGamesMenu">
            <a href="{{ route('game-hall.index') }}" class="nav-link" style="color:var(--gold);font-weight:600">{{ __('ui.next') }} →</a>
            <a href="{{ route('games.lobby') }}" class="nav-link">{{ __('games.flying_chess') }}</a>
            <a href="{{ route('truth-dare.lobby') }}" class="nav-link">{{ __('games.truth_dare') }}</a>
            <a href="{{ route('card-game.show') }}" class="nav-link">{{ __('games.card_game') }}</a>
            <a href="{{ route('dice-game.show') }}" class="nav-link">{{ __('games.dice_game') }}</a>
            <a href="{{ route('king-game.show') }}" class="nav-link">{{ __('games.king_game') }}</a>
            <a href="{{ route('wheel-game.show') }}" class="nav-link">{{ __('games.wheel_game') }}</a>
            <a href="{{ route('wheel.pure') }}" class="nav-link">{{ __('games.pure_wheel') }}</a>
            <a href="{{ route('trait-test.show') }}" class="nav-link">{{ __('traits.title') }}</a>
            <a href="{{ route('who-most-likely.show') }}" class="nav-link">{{ __('games.who_most_likely') }}</a>
            <a href="{{ route('boards.community') }}" class="nav-link">{{ __('ui.community_boards') }}</a>
        </div>
        @auth
            <a href="{{ route('profile.index') }}" class="nav-link">{{ __('ui.profile') }}</a>
            @if(Auth::user()->isAdmin())
                <a href="{{ route('admin.dashboard') }}" class="nav-link" style="color:var(--gold)">{{ __('ui.admin') }}</a>
            @endif
            <a href="{{ route('premium.index') }}" class="nav-link">
                {{ __('premium.page_title') }}
                @if(Auth::user()->isPremium())
                    <span class="nav-premium">Premium</span>
                @endif
            </a>
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline btn-full">{{ __('auth.logout') }}</button>
            </form>
        @else
            <a href="{{ route('login') }}" class="nav-link">{{ __('auth.login_title') }}</a>
            <a href="{{ route('register') }}" class="btn btn-sm btn-outline-gold btn-full">{{ __('auth.register_title') }}</a>
        @endauth
        @include('partials.lang-switcher', ['mobile' => true])
        <button class="theme-toggle" onclick="toggleTheme()">
            <span id="theme-label-m">{{ __('ui.theme_rose') }}</span> {{ __('ui.theme_switch') }}
        </button>
    </nav>
</header>

<main>
    @if(session('success'))
        <div class="toast toast-ok" onclick="this.remove()">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="toast toast-err" onclick="this.remove()">{{ session('error') }}</div>
    @endif
    <script>document.querySelectorAll('.toast').forEach(function(t){setTimeout(function(){t.remove()},3400)})</script>
    @yield('content')
    {{-- 遊戲頁的 FAQ 區塊。放在這裡而不是各頁 content 尾端,是因為八個遊戲頁的
         content 結構各不相同,集中在版型收尾才不會每頁插的位置都不一樣。 --}}
    @yield('faq')
</main>

<footer class="site-footer">
    <div class="container">
        <div class="footer-inner">
            <div class="footer-brand">
                <span class="footer-logo">@include('partials.heart-icon')<span>{{ __('ui.site_name') }}</span></span>
                <span class="footer-tagline">{{ __('ui.tagline') }}</span>
            </div>
            <div class="footer-links">
                <a href="{{ route('home') }}">{{ __('ui.home') }}</a>
                <a href="{{ route('games.lobby') }}">{{ __('games.flying_chess') }}</a>
                <a href="{{ route('truth-dare.lobby') }}">{{ __('games.truth_dare') }}</a>
                <a href="{{ route('card-game.show') }}">{{ __('games.card_game') }}</a>
                <a href="{{ route('dice-game.show') }}">{{ __('games.dice_game') }}</a>
                <a href="{{ route('king-game.show') }}">{{ __('games.king_game') }}</a>
                <a href="{{ route('wheel-game.show') }}">{{ __('games.wheel_game') }}</a>
                <a href="{{ route('wheel.pure') }}">{{ __('games.pure_wheel') }}</a>
                <a href="{{ route('who-most-likely.show') }}">{{ __('games.who_most_likely') }}</a>
                {{-- 頁尾是站內連結最有效的地方:每一頁都連過去,爬蟲一定找得到 --}}
                <a href="{{ route('trait-test.show') }}">{{ __('traits.title') }}</a>
                <a href="{{ route('boards.community') }}">{{ __('ui.community_boards') }}</a>
                <a href="{{ route('play') }}">{{ __('play.create_board') }}</a>
                @auth
                <a href="{{ route('profile.index') }}">{{ __('ui.profile') }}</a>
                <a href="{{ route('premium.index') }}">{{ __('premium.page_title') }}</a>
                @else
                <a href="{{ route('register') }}">{{ __('auth.register_title') }}</a>
                @endauth
                <a href="{{ route('legal.privacy') }}" rel="nofollow">{{ __('legal.privacy_title') }}</a>
                <a href="{{ route('legal.terms') }}" rel="nofollow">{{ __('legal.terms_title') }}</a>
            </div>
        </div>
        <div class="footer-bottom">
            {{-- 版權聲明講清楚範圍。發 DMCA 的時候,對方主張「網站上沒有聲明」
                 是很常見的第一句話,寫明就少一輪來回。 --}}
            <p class="footer-copy">
                &copy; {{ date('Y') }} {{ __('ui.site_name') }} · {{ __('ui.rights_reserved') }}
            </p>
            <button class="theme-toggle footer-theme" onclick="toggleTheme()" title="{{ __('ui.theme_switch') }}">
                <span id="theme-label">{{ __('ui.theme_rose') }}</span>
            </button>
        </div>
        {{-- Social placeholders --}}
        <div class="footer-social" style="justify-content:center">
            {{-- <a href="#" target="_blank" rel="nofollow noopener">Instagram</a> --}}
            {{-- <a href="#" target="_blank" rel="nofollow noopener">Twitter</a> --}}
        </div>
    </div>
</footer>

<script src="{{ asset_v('js/app.js') }}"></script>
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
    var label = t === 'dark' ? @json(__('ui.theme_indigo')) : @json(__('ui.theme_rose'));
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
// Mobile games sub-menu toggle
function toggleMobileGames(btn) {
    btn.classList.toggle('open');
    document.getElementById('mobileGamesMenu').classList.toggle('open');
}
</script>
@yield('scripts')
@stack('scripts')
</body>
</html>
