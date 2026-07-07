@extends('layouts.app')
@section('title', __('seo.community_title') . ' — ' . __('ui.site_name'))
@section('meta_description', __('seo.community_description'))
@section('content')
<div class="container" style="padding-top:40px">
    <div class="section-head">
        <h1>{{ __('play.community') }}</h1>
    </div>
    <p class="section-intro">{{ __('play.community_intro') }}</p>

    <div class="boards-grid">
        @forelse($boards as $board)
        <article class="board-card">
            <div class="board-card-body">
                <h3>{{ $board->name }}</h3>
                @if($board->description)<p>{{ $board->description }}</p>@endif
                <span class="badge-squares">{{ __('ui.square_count', ['n' => $board->squares_count]) }}</span>
                @if($board->user)
                <span class="badge-author">{{ __('play.community_by', ['name' => $board->user->name]) }}</span>
                @endif
            </div>
            <div class="board-card-foot">
                <a href="{{ route('play.board', $board) }}" class="btn btn-sm btn-gold">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5 inline-block">
                        <path fill-rule="evenodd" d="M4.5 5.653c0-1.427 1.529-2.33 2.779-1.643l11.54 6.347c1.295.712 1.295 2.573 0 3.286L7.28 19.99c-1.25.687-2.779-.217-2.779-1.643V5.653Z" clip-rule="evenodd"/>
                    </svg>
                    {{ __('ui.play') }}
                </a>
            </div>
        </article>
        @empty
        <div class="empty-notice">{{ __('play.community_empty') }}</div>
        @endforelse
    </div>

    <div style="margin-top:24px">
        {{ $boards->links() }}
    </div>
</div>
@endsection
