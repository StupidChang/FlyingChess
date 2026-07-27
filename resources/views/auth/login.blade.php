@extends('layouts.app')
@section('title', __('auth.login_title') . ' — ' . __('ui.site_name'))
@section('meta_description', __('auth.login_meta_description'))
@section('og_description', __('auth.login_meta_description'))
@section('robots', 'noindex,follow')
@section('canonical', route('login'))
@section('content')
<div class="form-page">
    <div class="form-card">
        <h1 style="font-size:1.5rem;color:var(--gold);margin-bottom:24px;text-align:center">{{ __('auth.login_heading') }}</h1>

        @if($errors->any())
        <div class="toast toast-err" style="margin-bottom:16px">
            {{ $errors->first() }}
        </div>
        @endif

        <form action="{{ route('login') }}" method="POST">
            @csrf
            <div class="form-group">
                <label>{{ __('auth.email_label') }}</label>
                <input type="email" name="email" class="form-control"
                       value="{{ old('email') }}" required maxlength="255" autocomplete="email">
            </div>
            <div class="form-group">
                <label>{{ __('auth.password_label') }}</label>
                <input type="password" name="password" class="form-control"
                       required autocomplete="current-password">
            </div>
            <div class="form-group" style="display:flex;align-items:center;gap:8px">
                <input type="checkbox" name="remember" id="remember" value="1">
                <label for="remember" style="margin:0;font-size:.9rem;color:var(--text-dim)">{{ __('auth.remember_me') }}</label>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-gold btn-full">{{ __('auth.login_button') }}</button>
            </div>
            <div style="text-align:right;margin-top:8px">
                <a href="{{ route('password.request') }}" style="color:var(--text-dim);font-size:.85rem">{{ __('auth.forgot_password') }}</a>
            </div>
        </form>

        {{-- Google 登入:三個憑證都設定齊全才顯示,未接時登入頁完全不變 --}}
        @if(config('services.google.client_id')
            && config('services.google.client_secret')
            && config('services.google.redirect'))
            <div class="oauth-sep"><span>{{ __('auth.or') }}</span></div>

            <a href="{{ route('google.redirect') }}" class="btn-google" rel="nofollow">
                <svg class="btn-google-icon" viewBox="0 0 18 18" aria-hidden="true" focusable="false">
                    <path fill="#4285F4" d="M17.64 9.2c0-.64-.06-1.25-.16-1.84H9v3.48h4.84a4.14 4.14 0 0 1-1.8 2.72v2.26h2.92c1.71-1.57 2.68-3.88 2.68-6.62z"/>
                    <path fill="#34A853" d="M9 18c2.43 0 4.47-.8 5.96-2.18l-2.92-2.26c-.81.54-1.84.86-3.04.86-2.34 0-4.32-1.58-5.03-3.7H.96v2.34A8.99 8.99 0 0 0 9 18z"/>
                    <path fill="#FBBC05" d="M3.97 10.72a5.4 5.4 0 0 1 0-3.44V4.94H.96a8.99 8.99 0 0 0 0 8.12l3.01-2.34z"/>
                    <path fill="#EA4335" d="M9 3.58c1.32 0 2.5.45 3.44 1.35l2.58-2.58A8.99 8.99 0 0 0 .96 4.94l3.01 2.34C4.68 5.16 6.66 3.58 9 3.58z"/>
                </svg>
                <span>{{ __('auth.google_login') }}</span>
            </a>
        @endif

        <p style="text-align:center;margin-top:20px;font-size:.88rem;color:var(--text-dim)">
            {{ __('auth.no_account') }}
            <a href="{{ route('register') }}" style="color:var(--gold)">{{ __('auth.sign_up_now') }}</a>
        </p>
    </div>
</div>
@endsection
