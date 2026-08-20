@extends('layouts.app')
@section('title', __('profile.public_meta', ['name' => $user->name]) . ' — ' . __('ui.site_name'))
@section('meta_description', $user->bio ? \Illuminate\Support\Str::limit($user->bio, 150) : __('profile.public_meta', ['name' => $user->name]))
@section('robots', 'noindex,nofollow')

@section('content')
@php $theme = $user->themeMeta(); @endphp
<div class="pf-wrap pf-public container" style="--pf-accent:{{ $theme['accent'] }};--pf-from:{{ $theme['from'] }};--pf-to:{{ $theme['to'] }}">

    <div class="pf-banner" @if($user->bannerUrl()) style="background-image:url('{{ $user->bannerUrl() }}');background-position:{{ $user->bannerPosition() }}" @endif></div>
    <div class="pf-headline">
        <div class="pf-avatar">
            @if($user->avatarUrl())
                <img src="{{ $user->avatarUrl() }}" alt="{{ $user->name }}" style="object-position:{{ $user->avatarPosition() }}">
            @else
                <span class="pf-avatar-fallback">{{ $user->initial() }}</span>
            @endif
        </div>
        <div class="pf-headline-text">
            <h1>{{ $user->name }}</h1>
            <p>
                @if($user->city)<span class="pf-tag">{{ __('profile.lives_in', ['city' => $user->city]) }}</span>@endif
                <span class="pf-since">{{ __('profile.member_since', ['date' => $user->created_at->format('Y/m')]) }}</span>
            </p>
        </div>
        @if($isOwner)
        <div class="pf-headline-actions">
            <a href="{{ route('profile.edit') }}" class="btn btn-sm btn-theme">{{ __('profile.edit_mine') }}</a>
        </div>
        @endif
    </div>

    @if($isOwner)
    <div class="pf-owner-note">{{ __('profile.this_is_you') }}</div>
    @endif

    <div class="pf-public-body">
        @if($user->looking_for)
        <section class="pf-card pf-accent-card">
            <h2>{{ __('profile.looking_for_title') }}</h2>
            <p class="pf-looking">{{ $user->looking_for }}</p>
        </section>
        @endif

        <section class="pf-card">
            <h2>{{ __('profile.bio_label') }}</h2>
            <p class="pf-bio">{{ $user->bio ?: __('profile.no_bio') }}</p>
        </section>

        @if($topTrait)
        <section class="pf-card">
            <h2>{{ __('profile.top_trait_title') }}</h2>
            <a href="{{ route('trait-test.result', ['slug' => $topTrait['slug']]) }}" class="pf-trait-chip">
                {{ $topTrait['name'] }}
            </a>
            <p class="pf-hint" style="margin:12px 0 0">{{ $topTrait['line'] ?? '' }}</p>
            <a href="{{ route('trait-test.show') }}" class="pf-trait-cta">{{ __('profile.top_trait_take') }} →</a>
        </section>
        @endif

        @if($boards->isNotEmpty())
        <section class="pf-card">
            <h2>{{ __('profile.boards_title') }}</h2>
            <div class="pf-boards">
                @foreach($boards as $b)
                <a href="{{ route('play.board', $b) }}" class="pf-board-chip">
                    <span class="pf-board-name">{{ $b->name }}</span>
                    <span class="pf-board-meta">{{ __('ui.square_count', ['n' => $b->squares_count]) }} · {{ __('profile.boards_play') }} →</span>
                </a>
                @endforeach
            </div>
        </section>
        @endif
    </div>
</div>
@endsection
