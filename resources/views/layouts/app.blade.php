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
    <link rel="icon" href="{{ asset('images/favicon.svg') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+TC:wght@400;500;600;700;900&display=swap" rel="stylesheet">
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
        <a href="{{ route('home') }}" class="logo">{{ __('ui.site_name') }}</a>

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
                </div>
            </div>
            @auth
                <a href="{{ route('profile.index') }}" class="nav-link">{{ __('ui.profile') }}</a>
                @if(Auth::user()->isAdmin())
                    <a href="{{ route('admin.dashboard') }}" class="nav-link" style="color:var(--gold)">{{ __('ui.admin') }}</a>
                @endif
                <span class="nav-user">
                    {{ Auth::user()->name }}
                    @if(Auth::user()->isPremium())
                        <span class="nav-premium">Premium</span>
                    @endif
                </span>
                <form action="{{ route('logout') }}" method="POST" style="display:inline">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline" style="margin-left:4px">{{ __('auth.logout') }}</button>
                </form>
            @else
                <a href="{{ route('login') }}" class="nav-link">{{ __('auth.login_title') }}</a>
                <a href="{{ route('register') }}" class="btn btn-sm btn-outline-gold" style="margin-left:4px">{{ __('auth.register_title') }}</a>
            @endauth
            @include('partials.lang-switcher')
            <button class="theme-toggle" onclick="toggleTheme()" title="{{ __('ui.switch_language') }}">
                <span id="theme-label">粉色</span>
            </button>
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
            <span id="theme-label-m">粉色</span> 切換配色
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
</main>

<footer class="site-footer">
    <div class="container">
        <div class="footer-inner">
            <div class="footer-brand">
                <span class="footer-logo">{{ __('ui.site_name') }}</span>
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
        <p class="footer-copy">&copy; {{ date('Y') }} {{ __('ui.site_name') }}</p>
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
