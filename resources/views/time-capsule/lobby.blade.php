@extends('layouts.app')
@section('title', __('games.tc_title') . ' | ' . __('ui.site_name'))
@section('meta_description', __('games.tc_meta'))
@section('og_title', __('games.tc_title'))
@section('og_description', __('games.tc_og_desc'))
@section('canonical', route('time-capsule.lobby'))

@section('styles')
<link rel="stylesheet" href="{{ asset('css/minigames.css') }}">
@endsection

@section('content')
<div class="mg-tool-page">
    <div class="mg-tool-hero">
        <h1>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" /></svg>
            {{ __('games.tc_h1') }}
        </h1>
        <p>{{ __('games.tc_hero_sub') }}</p>
    </div>

    <div class="mg-tool-features">
        <div class="mg-tool-feature">
            <svg class="mg-feature-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" /></svg>
            <div class="label">{{ __('games.tc_feature_1') }}</div>
        </div>
        <div class="mg-tool-feature">
            <svg class="mg-feature-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" /></svg>
            <div class="label">{{ __('games.tc_feature_2') }}</div>
        </div>
        <div class="mg-tool-feature">
            <svg class="mg-feature-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" /></svg>
            <div class="label">{{ __('games.tc_feature_3') }}</div>
        </div>
    </div>

    <div class="mg-tool-form">
        <h2>{{ __('games.tc_create_h2') }}</h2>
        <form method="POST" action="{{ route('time-capsule.create') }}">
            @csrf
            <div class="form-group">
                <label for="tc-title">{{ __('games.tc_title_label') }}</label>
                <input type="text" class="form-control" id="tc-title" name="title" placeholder="{{ __('games.tc_title_placeholder') }}" maxlength="100" required value="{{ old('title') }}">
                @error('title') <div class="mg-error">{{ $message }}</div> @enderror
            </div>
            <div class="form-group">
                <label for="tc-open-at">{{ __('games.tc_date_label') }}</label>
                <input type="date" class="form-control" id="tc-open-at" name="open_at" required value="{{ old('open_at', \Carbon\Carbon::today()->addYear()->toDateString()) }}" min="{{ \Carbon\Carbon::tomorrow()->toDateString() }}">
                @error('open_at') <div class="mg-error">{{ $message }}</div> @enderror
            </div>
            <div class="form-group">
                <label for="tc-notify-email">{{ __('games.tc_email_label') }}</label>
                <input type="email" class="form-control" id="tc-notify-email" name="notify_email" placeholder="your@email.com" maxlength="100" value="{{ old('notify_email') }}">
                @error('notify_email') <div class="mg-error">{{ $message }}</div> @enderror
            </div>
            <button type="submit" class="btn btn-gold btn-submit">{{ __('games.tc_create_btn') }}</button>
        </form>
        <div class="mg-tool-tip">
            {{ __('games.tc_create_tip') }}
        </div>
    </div>
</div>
@endsection
