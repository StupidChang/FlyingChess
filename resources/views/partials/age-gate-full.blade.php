@php
    use App\Support\LocaleHelper;
    $currentLocale = app()->getLocale();
    $currentHreflang = LocaleHelper::hreflang($currentLocale) ?? 'zh-TW';
@endphp
<!DOCTYPE html>
<html lang="{{ $currentHreflang }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('legal.age_gate_title') }} — {{ __('ui.site_name') }}</title>
    <meta name="robots" content="noindex,nofollow">
    <link rel="icon" href="{{ asset('images/favicon.svg') }}">
    <style>
        *{box-sizing:border-box;margin:0;padding:0}
        body{font-family:'Segoe UI','微軟正黑體',sans-serif;background:#0d0f16;color:#e9ebf2;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px}
        .age-gate{
            position:relative;overflow:hidden;
            background:#151823;border:1px solid #2a2f42;border-radius:14px;
            padding:44px 36px;max-width:440px;width:100%;text-align:center;
            box-shadow:0 20px 48px rgba(0,0,0,.5);
        }
        .age-gate::before{
            content:'';position:absolute;top:-80px;left:50%;transform:translateX(-50%);
            width:320px;height:220px;pointer-events:none;
            background:radial-gradient(ellipse at center, rgba(244,63,94,.14) 0%, transparent 70%);
        }
        .age-gate > *{position:relative}
        .age-badge{
            display:inline-block;font-size:.72rem;font-weight:700;letter-spacing:1.5px;
            text-transform:uppercase;color:#f43f5e;background:rgba(244,63,94,.1);
            border:1px solid rgba(244,63,94,.3);padding:4px 14px;border-radius:20px;margin-bottom:20px;
        }
        .age-gate h1{font-size:1.4rem;color:#e9ebf2;margin-bottom:16px;font-weight:800;letter-spacing:-.2px}
        .age-gate p{line-height:1.75;margin-bottom:12px;font-size:.92rem;color:#9aa1b5}
        .age-gate .warning{font-size:.98rem;color:#e9ebf2;margin-bottom:26px;line-height:1.7}
        .age-gate-btns{display:flex;gap:12px;justify-content:center;flex-wrap:wrap;margin-bottom:20px}
        .btn-enter{background:#f43f5e;color:#fff;border:none;border-radius:8px;padding:13px 32px;font-size:.95rem;font-weight:700;cursor:pointer;flex:1;min-width:140px;transition:background .15s ease,transform .1s ease}
        .btn-enter:hover{background:#fb7185}
        .btn-enter:active{transform:scale(.98)}
        .btn-leave{background:transparent;color:#9aa1b5;border:1px solid #2a2f42;border-radius:8px;padding:13px 32px;font-size:.95rem;cursor:pointer;flex:1;min-width:100px;transition:background .15s ease,color .15s ease,border-color .15s ease}
        .btn-leave:hover{background:#1d2130;color:#e9ebf2;border-color:#3a3f56}
        .age-gate-links{font-size:.8rem;color:#6b7186;margin-top:16px}
        .age-gate-links a{color:#9aa1b5;text-decoration:underline;margin:0 8px}
        .age-gate-links a:hover{color:#f43f5e}
    </style>
</head>
<body>
    <div class="age-gate">
        <span class="age-badge" aria-hidden="true">18+</span>
        <h1>{{ __('legal.age_gate_title') }}</h1>
        <p class="warning">{{ __('legal.age_gate_text') }}</p>
        <p>{{ __('legal.age_gate_consent') }}</p>
        <div class="age-gate-btns">
            <form action="{{ route('age.verify') }}" method="POST">
                @csrf
                <button type="submit" class="btn-enter">{{ __('legal.enter_18') }}</button>
            </form>
            <a href="https://www.google.com" class="btn-leave">{{ __('legal.leave') }}</a>
        </div>
        <div class="age-gate-links">
            <a href="{{ LocaleHelper::localizedUrl($currentLocale, 'privacy') }}">{{ __('legal.privacy_title') }}</a>
            <a href="{{ LocaleHelper::localizedUrl($currentLocale, 'terms') }}">{{ __('legal.terms_title') }}</a>
        </div>
        <div style="margin-top:18px;font-size:.78rem;color:#666">
            @foreach (LocaleHelper::supported() as $loc => $meta)
                @if ($loc !== $currentLocale)
                    <a href="{{ LocaleHelper::localizedUrl($loc, request()->path()) }}"
                       hreflang="{{ $meta['hreflang'] }}"
                       style="color:#888;margin:0 6px;text-decoration:underline">{{ $meta['native'] }}</a>
                @endif
            @endforeach
        </div>
    </div>
    @if(env('GOOGLE_GA4_ID'))
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ env('GOOGLE_GA4_ID') }}"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', '{{ env('GOOGLE_GA4_ID') }}');
        document.querySelector('.btn-enter').addEventListener('click', function() {
            gtag('event', 'age_gate_confirm');
        });
    </script>
    @endif
</body>
</html>
