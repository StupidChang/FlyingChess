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
