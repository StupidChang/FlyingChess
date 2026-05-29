@extends('layouts.app')
@section('title', __('auth.register_title') . ' — ' . __('ui.site_name'))
@section('robots', 'noindex,nofollow')
@section('canonical', route('register'))
@section('content')
<div class="form-page">
    <div class="form-card">
        <h1 style="font-size:1.5rem;color:var(--gold);margin-bottom:24px;text-align:center">{{ __('auth.register_heading') }}</h1>

        @if($errors->any())
        <div class="toast toast-err" style="margin-bottom:16px">
            {{ $errors->first() }}
        </div>
        @endif

        <form action="{{ route('register') }}" method="POST">
            @csrf
            <div class="form-group">
                <label>{{ __('auth.name_label') }}</label>
                <input type="text" name="name" class="form-control"
                       value="{{ old('name') }}" required maxlength="50">
            </div>
            <div class="form-group">
                <label>{{ __('auth.email_label') }}</label>
                <input type="email" name="email" class="form-control"
                       value="{{ old('email') }}" required autocomplete="email">
            </div>
            <div class="form-group">
                <label>{{ __('auth.password_label') }}（{{ __('auth.password_min') }}）</label>
                <input type="password" name="password" class="form-control"
                       required minlength="8" autocomplete="new-password">
            </div>
            <div class="form-group">
                <label>{{ __('auth.password_confirm') }}</label>
                <input type="password" name="password_confirmation" class="form-control"
                       required autocomplete="new-password">
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-gold btn-full">{{ __('auth.register_button') }}</button>
            </div>
        </form>

        <p style="text-align:center;margin-top:20px;font-size:.88rem;color:var(--text-dim)">
            {{ __('auth.have_account') }}
            <a href="{{ route('login') }}" style="color:var(--gold)">{{ __('auth.sign_in_now') }}</a>
        </p>
    </div>
</div>
@endsection
