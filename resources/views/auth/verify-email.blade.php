@extends('layouts.app')
@section('title', __('auth.verify_email_heading') . ' — ' . __('ui.site_name'))
@section('robots', 'noindex,nofollow')
@section('content')
<div class="form-page">
    <div class="form-card">
        <h1 style="font-size:1.5rem;color:var(--gold);margin-bottom:16px;text-align:center">{{ __('auth.verify_email_heading') }}</h1>

        @if(session('success'))
        <div class="toast toast-ok" style="margin-bottom:16px">
            {{ session('success') }}
        </div>
        @endif

        <p style="text-align:center;color:var(--text-dim);margin-bottom:24px;line-height:1.7">
            {{ __('auth.verify_email_thanks') }}<br>
            {{ __('auth.verify_email_sent') }}<br>
            {{ __('auth.verify_email_spam') }}
        </p>

        {{-- 為什麼要驗證:這些功能驗證前都會被帶回這一頁(routes 的 verified 中介層) --}}
        <div style="margin-bottom:24px;padding:14px 18px;border:1px solid var(--border);border-radius:12px;background:var(--surface2)">
            <p style="font-size:.85rem;font-weight:600;color:var(--text);margin-bottom:8px">{{ __('auth.verify_email_unlocks_title') }}</p>
            <ul style="margin:0;padding-left:1.2em;font-size:.85rem;color:var(--text-dim);line-height:1.9">
                @foreach (__('auth.verify_email_unlocks') as $item)
                    <li>{{ $item }}</li>
                @endforeach
            </ul>
        </div>

        <form action="{{ route('verification.send') }}" method="POST">
            @csrf
            <div class="form-actions">
                <button type="submit" class="btn btn-gold btn-full">{{ __('auth.verify_email_resend') }}</button>
            </div>
        </form>

        <div style="text-align:center;margin-top:16px">
            <a href="{{ route('home') }}" class="btn btn-outline btn-full" style="margin-bottom:8px">{{ __('auth.verify_email_play_first') }}</a>
        </div>

        <div style="text-align:center;margin-top:12px;font-size:.88rem;color:var(--text-dim)">
            <form action="{{ route('logout') }}" method="POST" style="display:inline">
                @csrf
                <button type="submit" style="background:none;border:none;color:var(--gold);cursor:pointer;font-size:.88rem">{{ __('auth.logout') }}</button>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
@if(session('success') && str_contains(session('success'), '註冊成功'))
<script>
if (typeof gtag !== 'undefined') {
    gtag('event', 'signup_completed');
}
</script>
@endif
@endsection
