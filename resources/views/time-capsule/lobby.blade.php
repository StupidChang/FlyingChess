@extends('layouts.app')
@section('title', __('games.tc_title') . ' | ' . __('ui.site_name'))
@section('meta_description', __('games.tc_meta'))
@section('og_title', __('games.tc_title'))
@section('og_description', __('games.tc_og_desc'))
@section('canonical', route('time-capsule.lobby'))

@section('styles')
<style>
.tc-page{max-width:560px;margin:0 auto;padding:48px 16px}
.tc-hero{text-align:center;margin-bottom:36px}
.tc-hero h1{font-size:1.7rem;color:var(--gold);margin-bottom:10px}
.tc-hero p{color:var(--text-dim);font-size:.95rem;line-height:1.7}
.tc-form{background:var(--surface);border:1px solid var(--border);border-radius:14px;padding:28px}
.tc-form h2{color:var(--gold);font-size:1.05rem;margin-bottom:16px;text-align:center;font-weight:600}
.tc-form .form-group{margin-bottom:16px}
.tc-form label{display:block;color:var(--text-dim);font-size:.85rem;margin-bottom:6px}
.tc-form input{width:100%;padding:12px 14px;background:var(--bg);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:1rem}
.tc-form input:focus{outline:none;border-color:var(--gold)}
.tc-form .btn-submit{width:100%;font-size:1.05rem;padding:13px}
.tc-tip{font-size:.85rem;color:var(--text-dim);margin-top:18px;text-align:center;line-height:1.6}
.tc-features{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:24px}
.tc-feature{background:var(--bg);border:1px solid var(--border);border-radius:8px;padding:12px 8px;text-align:center}
.tc-feature .icon{font-size:1.4rem;margin-bottom:4px}
.tc-feature .label{font-size:.78rem;color:var(--text);line-height:1.3}
.tc-error{color:#ef4444;font-size:.85rem;margin-bottom:12px}
</style>
@endsection

@section('content')
<div class="tc-page">
    <div class="tc-hero">
        <h1>📦 {{ __('games.tc_h1') }}</h1>
        <p>{{ __('games.tc_hero_sub') }}</p>
    </div>

    <div class="tc-features">
        <div class="tc-feature"><div class="icon">📝</div><div class="label">{{ __('games.tc_feature_1') }}</div></div>
        <div class="tc-feature"><div class="icon">🔒</div><div class="label">{{ __('games.tc_feature_2') }}</div></div>
        <div class="tc-feature"><div class="icon">💌</div><div class="label">{{ __('games.tc_feature_3') }}</div></div>
    </div>

    <div class="tc-form">
        <h2>{{ __('games.tc_create_h2') }}</h2>
        <form method="POST" action="{{ route('time-capsule.create') }}">
            @csrf
            <div class="form-group">
                <label for="tc-title">{{ __('games.tc_title_label') }}</label>
                <input type="text" id="tc-title" name="title" placeholder="{{ __('games.tc_title_placeholder') }}" maxlength="100" required value="{{ old('title') }}">
                @error('title') <div class="tc-error">{{ $message }}</div> @enderror
            </div>
            <div class="form-group">
                <label for="tc-open-at">{{ __('games.tc_date_label') }}</label>
                <input type="date" id="tc-open-at" name="open_at" required value="{{ old('open_at', \Carbon\Carbon::today()->addYear()->toDateString()) }}" min="{{ \Carbon\Carbon::tomorrow()->toDateString() }}">
                @error('open_at') <div class="tc-error">{{ $message }}</div> @enderror
            </div>
            <div class="form-group">
                <label for="tc-notify-email">{{ __('games.tc_email_label') }}</label>
                <input type="email" id="tc-notify-email" name="notify_email" placeholder="your@email.com" maxlength="100" value="{{ old('notify_email') }}">
                @error('notify_email') <div class="tc-error">{{ $message }}</div> @enderror
            </div>
            <button type="submit" class="btn btn-gold btn-submit">{{ __('games.tc_create_btn') }}</button>
        </form>
        <div class="tc-tip">
            {{ __('games.tc_create_tip') }}
        </div>
    </div>
</div>
@endsection
