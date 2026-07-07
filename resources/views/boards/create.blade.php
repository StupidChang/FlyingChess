@extends('layouts.app')
@section('title', __('play.create_board') . ' — ' . __('ui.site_name'))
@section('meta_description', __('seo.boards_description'))
@section('robots', 'noindex,follow')
@section('content')
<div class="container form-page">
    <h1>{{ __('play.create_board') }}</h1>
    <p style="color:var(--text-dim);margin-bottom:24px">{{ __('play.create_intro') }}</p>

    @if($errors->any())
    <div class="toast toast-err" style="margin-bottom:16px">
        {{ $errors->first() }}
    </div>
    @endif

    <form action="{{ route('boards.store') }}" method="POST" class="form-card">
        @csrf
        <div class="form-group">
            <label for="board-name">{{ __('play.board_name') }}</label>
            <input type="text" id="board-name" name="name" class="form-control"
                   value="{{ old('name') }}"
                   placeholder="{{ __('play.board_name_placeholder') }}" maxlength="100" required>
        </div>
        <div class="form-group">
            <label for="board-description">{{ __('play.description_label') }}</label>
            <textarea id="board-description" name="description" class="form-control" rows="3" maxlength="500"
                      placeholder="{{ __('play.description_placeholder') }}">{{ old('description') }}</textarea>
        </div>
        <div class="form-actions">
            <button class="btn btn-gold">{{ __('play.create_and_edit') }}</button>
            <a href="{{ route('home') }}" class="btn btn-outline">{{ __('ui.cancel') }}</a>
        </div>
    </form>
</div>
@endsection
