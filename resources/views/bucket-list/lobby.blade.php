@extends('layouts.app')
@section('title', __('games.bl_title') . ' | ' . __('ui.site_name'))
@section('meta_description', __('games.bl_meta'))
@section('og_title', __('games.bl_title'))
@section('og_description', __('games.bl_og_desc'))
@section('canonical', route('bucket-list.lobby'))

@section('styles')
<link rel="stylesheet" href="{{ asset('css/minigames.css') }}">
@endsection

@section('content')
<div class="mg-tool-page">
    <div class="mg-tool-hero">
        <h1>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25Z" /></svg>
            {{ __('games.bl_h1') }}
        </h1>
        <p>{{ __('games.bl_hero_line1') }}<br>{{ __('games.bl_hero_line2') }}</p>
    </div>

    <div class="mg-tool-features">
        <div class="mg-tool-feature">
            <svg class="mg-feature-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" /></svg>
            <div class="label">{{ __('games.bl_feature_1') }}</div>
        </div>
        <div class="mg-tool-feature">
            <svg class="mg-feature-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6.633 10.5c.806 0 1.533-.446 2.031-1.08a9.041 9.041 0 0 1 2.861-2.4c.723-.384 1.35-.956 1.653-1.715a4.498 4.498 0 0 0 .322-1.672V3a.75.75 0 0 1 .75-.75A2.25 2.25 0 0 1 16.5 4.5c0 1.152-.26 2.243-.723 3.218-.266.558.107 1.282.725 1.282h3.126c1.026 0 1.945.694 2.054 1.715.045.422.068.85.068 1.285a11.95 11.95 0 0 1-2.649 7.521c-.388.482-.987.729-1.605.729H13.48c-.483 0-.964-.078-1.423-.23l-3.114-1.04a4.501 4.501 0 0 0-1.423-.23H5.904M14.25 9h2.25M5.904 18.75c.083.205.173.405.27.602.197.4-.078.898-.523.898h-.908c-.889 0-1.713-.518-1.972-1.368a12 12 0 0 1-.521-3.507c0-1.553.295-3.036.831-4.398C3.387 10.203 4.167 9.75 5 9.75h1.053c.472 0 .745.556.5.96a8.958 8.958 0 0 0-1.302 4.665c0 1.194.232 2.333.654 3.375Z" /></svg>
            <div class="label">{{ __('games.bl_feature_2') }}</div>
        </div>
        <div class="mg-tool-feature">
            <svg class="mg-feature-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" /></svg>
            <div class="label">{{ __('games.bl_feature_3') }}</div>
        </div>
    </div>

    <div class="mg-tool-form">
        <h2>{{ __('games.bl_create_h2') }}</h2>
        <form method="POST" action="{{ route('bucket-list.create') }}">
            @csrf
            <div class="form-group">
                <input type="text" class="form-control" name="title" aria-label="{{ __('games.bl_create_h2') }}" placeholder="{{ __('games.bl_title_placeholder') }}" maxlength="100" required value="{{ old('title') }}">
            </div>
            @error('title') <div class="mg-error">{{ $message }}</div> @enderror
            <button type="submit" class="btn btn-gold btn-submit">{{ __('games.bl_create_btn') }}</button>
        </form>
        <div class="mg-tool-tip">
            {{ __('games.bl_create_tip') }}
        </div>
    </div>
</div>
@endsection
