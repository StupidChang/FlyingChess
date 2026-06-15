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

        <p style="text-align:center;margin-top:20px;font-size:.88rem;color:var(--text-dim)">
            {{ __('auth.no_account') }}
            <a href="{{ route('register') }}" style="color:var(--gold)">{{ __('auth.sign_up_now') }}</a>
        </p>
    </div>
</div>
@endsection
