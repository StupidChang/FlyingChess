{{--
    Self-contained base layout for HTTP error pages.

    Error pages must NOT extend layouts.app: when an error is thrown before
    the route-level `set.locale` middleware runs (404 = no route matched,
    419 = VerifyCsrfToken fires in the web group, 503 = maintenance mode),
    URL::defaults has no {locale} and every route('...') call in the shared
    layout throws UrlGenerationException (verified: "Missing parameter:
    locale"). Sessions are also unavailable, so @auth would crash too.

    Locale: resolved by errors/_locale.blade.php, which every error view
    includes at the top (and we include again here defensively — it is
    idempotent).
--}}
@include('errors._locale')
@php
    $errPrefixMap = ['tw' => 'zh_TW', 'cn' => 'zh_CN', 'jp' => 'ja', 'en' => 'en'];
    $errPrefix = array_search(app()->getLocale(), $errPrefixMap, true) ?: 'tw';
    $errHomeUrl = '/' . $errPrefix;
    $errLobbyUrl = '/' . $errPrefix . '/game-hall';
    $htmlLang = ['zh_TW' => 'zh-TW', 'zh_CN' => 'zh-CN', 'ja' => 'ja', 'en' => 'en'][app()->getLocale()] ?? 'zh-TW';
@endphp
<!DOCTYPE html>
<html lang="{{ $htmlLang }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex">
    <title>@yield('code') — @yield('title') | {{ __('errors.site_name') }}</title>
    {{-- Same theme init as layouts.app (prevent flash) --}}
    <script>
        (function(){
            var t = localStorage.getItem('theme') || 'pink';
            document.documentElement.setAttribute('data-theme', t);
        })();
    </script>
    <style>
        *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
        :root,[data-theme="pink"]{
            --bg:#1a0a0f;--surface:#2a1018;--border:#5a2535;
            --gold:#d4a017;--accent:#e8527a;--text:#f0dde4;--text-dim:#9a7080;
            --glow-rgb:180,60,100;
        }
        [data-theme="dark"]{
            --bg:#0f0f14;--surface:#1a1a24;--border:#3a3a55;
            --gold:#8b5cf6;--accent:#818cf8;--text:#e2e0f0;--text-dim:#8888aa;
            --glow-rgb:99,102,241;
        }
        body{
            font-family:'Noto Sans TC','Segoe UI','微軟正黑體',sans-serif;
            background:var(--bg);color:var(--text);line-height:1.6;
            min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px;
        }
        .err-card{
            text-align:center;max-width:480px;width:100%;
            background:var(--surface);border:1px solid var(--border);border-radius:12px;
            padding:48px 32px;box-shadow:0 0 60px rgba(var(--glow-rgb),.18);
        }
        .err-logo{display:inline-block;color:var(--gold);font-weight:700;font-size:1.1rem;text-decoration:none;margin-bottom:20px}
        .err-code{font-size:5.5rem;font-weight:900;line-height:1;color:var(--accent);letter-spacing:2px}
        .err-title{font-size:1.3rem;margin:12px 0 8px;color:var(--text)}
        .err-msg{color:var(--text-dim);font-size:.95rem;margin-bottom:28px}
        .err-actions{display:flex;gap:10px;justify-content:center;flex-wrap:wrap}
        .err-btn{
            display:inline-block;padding:10px 22px;border-radius:8px;font-size:.95rem;
            text-decoration:none;border:1px solid transparent;cursor:pointer;
            font-family:inherit;transition:opacity .15s;
        }
        .err-btn:hover{opacity:.85}
        .err-btn-primary{background:var(--accent);color:#fff}
        .err-btn-outline{background:transparent;color:var(--text);border-color:var(--border)}
    </style>
</head>
<body>
    <div class="err-card">
        <a class="err-logo" href="{{ $errHomeUrl }}">{{ __('errors.site_name') }}</a>
        <p class="err-code">@yield('code')</p>
        <h1 class="err-title">@yield('title')</h1>
        <p class="err-msg">@yield('message')</p>
        <div class="err-actions">
            @hasSection('show_back')
                <a class="err-btn err-btn-primary" href="javascript:history.back()">{{ __('errors.go_back') }}</a>
                <a class="err-btn err-btn-outline" href="{{ $errHomeUrl }}">{{ __('errors.go_home') }}</a>
            @else
                <a class="err-btn err-btn-primary" href="{{ $errHomeUrl }}">{{ __('errors.go_home') }}</a>
                @hasSection('hide_lobby')
                @else
                    <a class="err-btn err-btn-outline" href="{{ $errLobbyUrl }}">{{ __('errors.go_lobby') }}</a>
                @endif
            @endif
        </div>
    </div>
</body>
</html>
