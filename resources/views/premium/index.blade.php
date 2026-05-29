@extends('layouts.app')
@section('title', __('seo.premium_title') . ' — ' . __('ui.site_name'))
@section('meta_description', __('seo.premium_description', ['price' => config('premium.price', 99)]))
@section('robots', 'noindex,nofollow')
@section('content')

<div class="premium-section">
    <h1 style="text-align:center;color:var(--gold);margin-bottom:32px">{{ __('seo.premium_title') }}</h1>

    @if($isPremium)
    <div class="premium-status">
        <strong style="color:var(--gold)">{{ __('premium.you_are_premium') }}</strong><br>
        {{ __('premium.expires_at', ['date' => $expiresAt->format('Y/m/d')]) }}
        <br><br>
        <small>{{ __('premium.auto_revert_note') }}</small>
    </div>
    @endif

    <div class="premium-card">
        <h2 style="color:var(--text);font-size:1.2rem">{{ __('premium.plan_monthly') }}</h2>
        <div class="premium-price">NT${{ $price }}</div>
        <div class="premium-period">{{ __('premium.period_no_renew') }}</div>

        <ul class="premium-features">
            <li>{{ __('premium.feat_no_ads') }}</li>
            <li>{{ __('premium.feat_premium_deck') }}</li>
            <li>{{ __('premium.feat_private_room') }}</li>
            <li>{{ __('premium.feat_premium_board') }}</li>
            <li>{{ __('premium.feat_adult_content') }}</li>
        </ul>

        @auth
            <form action="{{ route('premium.checkout') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-gold btn-xl btn-full"
                    onclick="typeof gtag!=='undefined'&&gtag('event','checkout_started',{value:{{ $price }}})">
                    {{ $isPremium ? __('premium.cta_renew') : __('premium.cta_upgrade_now') }}
                </button>
            </form>
        @else
            <a href="{{ route('register') }}" class="btn btn-gold btn-xl btn-full">
                {{ __('premium.cta_register_then') }}
            </a>
            <p style="text-align:center;margin-top:12px;font-size:.85rem;color:var(--text-dim)">
                {{ __('premium.have_account_q') }}<a href="{{ route('login') }}" style="color:var(--gold)">{{ __('premium.sign_in_link') }}</a>
            </p>
        @endauth
    </div>

    <div style="text-align:center;margin-top:24px;font-size:.82rem;color:var(--text-dim)">
        {{ __('premium.payment_note') }}<br>
        {{ __('premium.renew_note') }}
    </div>

    {{-- 客服聯絡 --}}
    <div style="margin-top:40px;background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:24px;max-width:480px;margin-left:auto;margin-right:auto">
        <h2 style="font-size:1.1rem;color:var(--text);margin-bottom:16px;text-align:center">{{ __('premium.need_help') }}</h2>
        <div style="display:flex;flex-direction:column;gap:12px">
            <div style="display:flex;align-items:center;gap:10px">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:20px;height:20px;color:var(--gold);flex-shrink:0">
                    <path d="M1.5 8.67v8.58a3 3 0 0 0 3 3h15a3 3 0 0 0 3-3V8.67l-8.928 5.493a3 3 0 0 1-3.144 0L1.5 8.67Z"/>
                    <path d="M22.5 6.908V6.75a3 3 0 0 0-3-3h-15a3 3 0 0 0-3 3v.158l9.714 5.978a1.5 1.5 0 0 0 1.572 0L22.5 6.908Z"/>
                </svg>
                <div>
                    <div style="font-size:.8rem;color:var(--text-dim)">{{ __('premium.support_email_label') }}</div>
                    <a href="mailto:support@couplefly.com" style="color:var(--gold);font-size:.9rem">support@couplefly.com</a>
                </div>
            </div>
            <div style="display:flex;align-items:center;gap:10px">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:20px;height:20px;color:var(--gold);flex-shrink:0">
                    <path fill-rule="evenodd" d="M4.848 2.771A49.144 49.144 0 0 1 12 2.25c2.43 0 4.817.178 7.152.52 1.978.29 3.348 2.024 3.348 3.97v6.02c0 1.946-1.37 3.68-3.348 3.97a48.901 48.901 0 0 1-3.476.383.39.39 0 0 0-.297.17l-2.755 4.133a.75.75 0 0 1-1.248 0l-2.755-4.133a.39.39 0 0 0-.297-.17 48.9 48.9 0 0 1-3.476-.384c-1.978-.29-3.348-2.024-3.348-3.97V6.741c0-1.946 1.37-3.68 3.348-3.97ZM6.75 8.25a.75.75 0 0 1 .75-.75h9a.75.75 0 0 1 0 1.5h-9a.75.75 0 0 1-.75-.75Zm.75 2.25a.75.75 0 0 0 0 1.5H12a.75.75 0 0 0 0-1.5H7.5Z" clip-rule="evenodd"/>
                </svg>
                <div>
                    <div style="font-size:.8rem;color:var(--text-dim)">{{ __('premium.support_line_label') }}</div>
                    <span style="color:var(--text);font-size:.9rem">@couplefly</span>
                </div>
            </div>
            <div style="display:flex;align-items:center;gap:10px">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:20px;height:20px;color:var(--gold);flex-shrink:0">
                    <path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25ZM12.75 6a.75.75 0 0 0-1.5 0v6c0 .414.336.75.75.75h4.5a.75.75 0 0 0 0-1.5h-3.75V6Z" clip-rule="evenodd"/>
                </svg>
                <div>
                    <div style="font-size:.8rem;color:var(--text-dim)">{{ __('premium.support_hours_label') }}</div>
                    <span style="color:var(--text);font-size:.9rem">{{ __('premium.support_hours_value') }}</span>
                </div>
            </div>
        </div>
        <p style="text-align:center;margin-top:14px;font-size:.78rem;color:var(--text-dim)">
            {{ __('premium.support_footer') }}
        </p>
    </div>
</div>
@endsection
