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
        body{font-family:'Segoe UI','微軟正黑體',sans-serif;background:#0f0f14;color:#e2e0f0;min-height:100vh;display:flex;align-items:center;justify-content:center}
        .age-gate{background:#1a1a2e;border:1px solid #3a3a55;border-radius:16px;padding:48px 36px;max-width:440px;width:90%;text-align:center}
        .age-gate h1{font-size:1.5rem;color:#d4a017;margin-bottom:16px;font-weight:800}
        .age-gate p{line-height:1.8;margin-bottom:12px;font-size:.95rem;color:#aaa}
        .age-gate .warning{font-size:1rem;color:#e2e0f0;margin-bottom:28px;line-height:1.7}
        .age-gate-btns{display:flex;gap:12px;justify-content:center;flex-wrap:wrap;margin-bottom:20px}
        .btn-enter{background:#d4a017;color:#1a1a2e;border:none;border-radius:8px;padding:14px 32px;font-size:1rem;font-weight:700;cursor:pointer;flex:1;min-width:140px;transition:.2s}
        .btn-enter:hover{background:#f0c040}
        .btn-leave{background:transparent;color:#888;border:1px solid #555;border-radius:8px;padding:14px 32px;font-size:1rem;cursor:pointer;flex:1;min-width:100px;transition:.2s}
        .btn-leave:hover{background:#222;color:#aaa}
        .age-gate-links{font-size:.82rem;color:#666;margin-top:16px}
        .age-gate-links a{color:#888;text-decoration:underline;margin:0 8px}
        .age-gate-links a:hover{color:#d4a017}
    </style>
</head>
<body>
    <div class="age-gate">
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
