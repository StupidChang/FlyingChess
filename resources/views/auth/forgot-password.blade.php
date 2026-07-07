@extends('layouts.app')
@section('title', __('auth.forgot_heading') . ' — ' . __('ui.site_name'))
@section('meta_description', __('auth.forgot_meta_description'))
@section('og_description', __('auth.forgot_meta_description'))
@section('robots', 'noindex,follow')
@section('content')
<div class="form-page">
    <div class="form-card">
        <h1 style="font-size:1.5rem;color:var(--gold);margin-bottom:8px;text-align:center">{{ __('auth.forgot_heading') }}</h1>
        <p style="text-align:center;color:var(--text-dim);font-size:.9rem;margin-bottom:24px">
            {{ __('auth.forgot_intro_long') }}
        </p>

        @if(session('success'))
        <div class="toast toast-ok" style="margin-bottom:16px">
            {{ session('success') }}
        </div>
        @endif

        @if($errors->any())
        <div class="toast toast-err" style="margin-bottom:16px">
            {{ $errors->first() }}
        </div>
        @endif

        <form action="{{ route('password.email') }}" method="POST">
            @csrf
            <div class="form-group">
                <label>{{ __('auth.email_label') }}</label>
                <input type="email" name="email" class="form-control"
                       value="{{ old('email') }}" required maxlength="255" autocomplete="email"
                       placeholder="{{ __('auth.forgot_email_placeholder') }}">
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-gold btn-full">{{ __('auth.send_reset_link') }}</button>
            </div>
        </form>

        <p style="text-align:center;margin-top:20px;font-size:.88rem;color:var(--text-dim)">
            {{ __('auth.forgot_back') }}
            <a href="{{ route('login') }}" style="color:var(--gold)">{{ __('auth.forgot_back_link') }}</a>
        </p>
    </div>
</div>
@endsection
