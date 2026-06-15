@extends('layouts.app')
@section('title', __('auth.reset_heading') . ' — ' . __('ui.site_name'))
@section('meta_description', __('auth.reset_meta_description'))
@section('og_description', __('auth.reset_meta_description'))
@section('robots', 'noindex,nofollow')
@section('content')
<div class="form-page">
    <div class="form-card">
        <h1 style="font-size:1.5rem;color:var(--gold);margin-bottom:8px;text-align:center">{{ __('auth.reset_heading') }}</h1>
        <p style="text-align:center;color:var(--text-dim);font-size:.9rem;margin-bottom:24px">
            {{ __('auth.reset_intro') }}
        </p>

        @if($errors->any())
        <div class="toast toast-err" style="margin-bottom:16px">
            {{ $errors->first() }}
        </div>
        @endif

        <form action="{{ route('password.update') }}" method="POST">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <div class="form-group">
                <label>{{ __('auth.email_label') }}</label>
                <input type="email" name="email" class="form-control"
                       value="{{ old('email', $email) }}" required maxlength="255" autocomplete="email">
            </div>
            <div class="form-group">
                <label>{{ __('auth.new_password') }}（{{ __('auth.password_min') }}）</label>
                <input type="password" name="password" class="form-control"
                       required minlength="8" autocomplete="new-password">
            </div>
            <div class="form-group">
                <label>{{ __('auth.new_password_confirm') }}</label>
                <input type="password" name="password_confirmation" class="form-control"
                       required autocomplete="new-password">
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-gold btn-full">{{ __('auth.reset_button') }}</button>
            </div>
        </form>

        <p style="text-align:center;margin-top:20px;font-size:.88rem;color:var(--text-dim)">
            <a href="{{ route('login') }}" style="color:var(--gold)">{{ __('auth.forgot_back_link') }}</a>
        </p>
    </div>
</div>
@endsection
