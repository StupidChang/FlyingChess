@extends('layouts.app')
@section('title', __('play.edit_board') . ' — ' . $board->name)
@section('meta_description', __('seo.boards_description'))
@section('robots', 'noindex,follow')
@section('styles')
<link rel="stylesheet" href="{{ asset('css/board.css') }}">
@endsection

@section('content')
<div class="edit-page">
    <div class="edit-toolbar">
        <div class="edit-toolbar-left">
            <h1 id="board-name-display">{{ $board->name }}</h1>
            <button class="btn btn-sm btn-outline" onclick="openBoardMeta()">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5 inline-block">
                    <path d="M21.731 2.269a2.625 2.625 0 0 0-3.712 0l-1.157 1.157 3.712 3.712 1.157-1.157a2.625 2.625 0 0 0 0-3.712ZM19.513 8.199l-3.712-3.712-8.4 8.4a5.25 5.25 0 0 0-1.32 2.214l-.8 2.685a.75.75 0 0 0 .933.933l2.685-.8a5.25 5.25 0 0 0 2.214-1.32l8.4-8.4Z"/>
                </svg>
                {{ __('play.rename') }}
            </button>
        </div>
        <div class="edit-toolbar-right">
            <a href="{{ route('play.board', $board) }}" class="btn btn-gold">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5 inline-block">
                    <path fill-rule="evenodd" d="M4.5 5.653c0-1.427 1.529-2.33 2.779-1.643l11.54 6.347c1.295.712 1.295 2.573 0 3.286L7.28 19.99c-1.25.687-2.779-.217-2.779-1.643V5.653Z" clip-rule="evenodd"/>
                </svg>
                {{ __('play.start_game') }}
            </a>
            <a href="{{ route('home') }}" class="btn btn-outline">← {{ __('ui.back') }}</a>
        </div>
    </div>

    {{-- Tab navigation --}}
    <div class="tab-bar">
        <button class="tab-btn tab-active" data-tab="content" onclick="switchTab('content')">{{ __('play.tab_content') }}</button>
        <button class="tab-btn" data-tab="layout"  onclick="switchTab('layout')">{{ __('play.tab_layout') }}</button>
        <button class="tab-btn" data-tab="path"    onclick="switchTab('path')">{{ __('play.tab_path') }}</button>
    </div>

    {{-- Per-tab hint strips (toggled via tab-hidden) --}}
    <div id="tab-content" class="tab-panel">
        <p class="edit-hint">{{ __('play.hint_content') }}</p>
        <div class="color-legend">
            <span class="legend-title">{{ __('play.color_legend') }}</span>
            <span class="sq sq-action">{{ __('play.sq_action') }}</span>
            <span class="sq sq-drink">{{ __('play.sq_drink') }}</span>
            <span class="sq sq-dare">{{ __('play.sq_dare') }}</span>
            <span class="sq sq-truth">{{ __('play.sq_truth') }}</span>
            <span class="sq sq-strip">{{ __('play.sq_strip') }}</span>
            <span class="sq sq-move">{{ __('play.sq_move') }}</span>
            <span class="sq sq-male">{{ __('play.sq_male') }}</span>
            <span class="sq sq-female">{{ __('play.sq_female') }}</span>
            <span class="sq sq-normal">{{ __('play.sq_normal') }}</span>
        </div>
    </div>
    <div id="tab-layout" class="tab-panel tab-hidden">
        <p class="edit-hint">{{ __('play.hint_layout') }}</p>
    </div>
    <div id="tab-path" class="tab-panel tab-hidden">
        <p class="edit-hint">{{ __('play.hint_path') }}</p>
    </div>

    {{-- Layout controls bar (hidden until layout tab) --}}
    <div id="layout-controls" class="layout-controls hidden">
        <div class="layout-canvas-controls">
            <span class="layout-label">{{ __('play.canvas_size') }}</span>
            <label>{{ __('play.row_short') }} <input id="canvas-rows-input" type="number" min="3" max="30"
                              class="canvas-size-input" value="{{ $board->canvas_rows }}"></label>
            <span style="color:var(--text-dim)">×</span>
            <label>{{ __('play.col_short') }} <input id="canvas-cols-input" type="number" min="3" max="30"
                              class="canvas-size-input" value="{{ $board->canvas_cols }}"></label>
            <button onclick="applyCanvasSize()" class="btn btn-sm">{{ __('play.apply_size') }}</button>
        </div>
        <div class="layout-preset-controls">
            <span class="layout-label">{{ __('play.apply_preset') }}</span>
            <button onclick="applyPreset('cross')"  class="btn btn-sm">{{ __('play.preset_cross') }}</button>
            <button onclick="applyPreset('square')" class="btn btn-sm">{{ __('play.preset_square') }}</button>
        </div>
    </div>

    {{-- Main area: board + path side panel (always in DOM) --}}
    <div class="edit-main">
        <div class="board-wrap">
            <div id="game-board" class="game-board edit-mode">
                {{-- rendered by JS --}}
            </div>
        </div>

        {{-- Path side panel (hidden until path tab) --}}
        <div id="path-side-panel" class="path-side-panel hidden">
            <div class="path-group-tabs">
                <button class="path-group-tab active" data-group="all"    onclick="selectPathGroup('all')">{{ __('play.path_main') }}</button>
                <button class="path-group-tab"        data-group="male"   onclick="selectPathGroup('male')">{{ __('play.path_male') }}</button>
                <button class="path-group-tab"        data-group="female" onclick="selectPathGroup('female')">{{ __('play.path_female') }}</button>
            </div>
            <p id="path-group-hint" class="path-group-hint">{{ __('play.path_main_hint') }}</p>
            <ul id="path-list-ul" class="path-list-ul"></ul>
            <div class="path-panel-actions">
                <button onclick="clearCurrentPath()"   class="btn btn-sm btn-outline">{{ __('play.clear') }}</button>
                <button onclick="resetPathToDefault()" class="btn btn-sm btn-outline">{{ __('play.reset') }}</button>
                <button onclick="savePaths()"          class="btn btn-gold btn-sm">{{ __('play.save_path') }}</button>
            </div>
            <div id="path-save-status" class="save-status path-save-status"></div>
        </div>
    </div>
</div>

{{-- Edit Square Modal --}}
<div id="sq-modal" class="modal" role="dialog" aria-modal="true">
    <div class="modal-overlay" onclick="closeSqModal()"></div>
    <div class="modal-box">
        <button class="modal-close" onclick="closeSqModal()">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5 inline-block">
                <path fill-rule="evenodd" d="M5.47 5.47a.75.75 0 0 1 1.06 0L12 10.94l5.47-5.47a.75.75 0 1 1 1.06 1.06L13.06 12l5.47 5.47a.75.75 0 1 1-1.06 1.06L12 13.06l-5.47 5.47a.75.75 0 0 1-1.06-1.06L10.94 12 5.47 6.53a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd"/>
            </svg>
        </button>
        <h2>{{ __('play.edit_square') }} <span id="sq-pos-label" class="sq-pos-badge">#0</span></h2>

        <div class="form-group">
            <label>{{ __('play.square_text') }}</label>
            <textarea id="sq-text" class="form-control" rows="4" maxlength="200" placeholder="{{ __('play.square_text_placeholder') }}"></textarea>
            <div class="char-count"><span id="sq-char">0</span>/200</div>
        </div>

        <div class="form-group">
            <label>{{ __('play.square_color_type') }}</label>
            <div class="color-picker">
                <label class="cp-opt"><input type="radio" name="sq-color" value="action"> <span class="sq sq-action">{{ __('play.sq_action') }}</span></label>
                <label class="cp-opt"><input type="radio" name="sq-color" value="drink">  <span class="sq sq-drink">{{ __('play.sq_drink') }}</span></label>
                <label class="cp-opt"><input type="radio" name="sq-color" value="dare">   <span class="sq sq-dare">{{ __('play.sq_dare') }}</span></label>
                <label class="cp-opt"><input type="radio" name="sq-color" value="truth">  <span class="sq sq-truth">{{ __('play.sq_truth') }}</span></label>
                <label class="cp-opt"><input type="radio" name="sq-color" value="strip">  <span class="sq sq-strip">{{ __('play.sq_strip') }}</span></label>
                <label class="cp-opt"><input type="radio" name="sq-color" value="move">   <span class="sq sq-move">{{ __('play.sq_move') }}</span></label>
                <label class="cp-opt"><input type="radio" name="sq-color" value="male">   <span class="sq sq-male">{{ __('play.sq_male') }}</span></label>
                <label class="cp-opt"><input type="radio" name="sq-color" value="female"> <span class="sq sq-female">{{ __('play.sq_female') }}</span></label>
                <label class="cp-opt"><input type="radio" name="sq-color" value="normal"> <span class="sq sq-normal">{{ __('play.sq_normal') }}</span></label>
            </div>
        </div>

        <div class="form-group">
            <label>
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5 inline-block">
                    <path d="M3.478 2.405a.75.75 0 0 0-.926.94l2.432 7.905H13.5a.75.75 0 0 1 0 1.5H4.984l-2.432 7.905a.75.75 0 0 0 .926.94 60.519 60.519 0 0 0 18.445-8.986.75.75 0 0 0 0-1.218A60.517 60.517 0 0 0 3.478 2.405Z"/>
                </svg>
                {{ __('play.fly_to_label') }}
            </label>
            <div style="display:flex;align-items:center;gap:8px">
                <input type="number" id="sq-fly-to" class="form-control"
                       min="0" max="999" placeholder="{{ __('play.fly_to_placeholder') }}"
                       style="width:160px">
                <span style="font-size:.78rem;color:var(--text-dim)">{{ __('play.square_number') }}</span>
            </div>
            <div style="font-size:.75rem;color:var(--text-dim);margin-top:4px">
                {{ __('play.fly_to_help') }}
            </div>
        </div>

        <div class="modal-actions">
            <button class="btn btn-gold btn-full" onclick="saveSquare()">{{ __('ui.save') }}</button>
        </div>

        <div id="sq-save-status" class="save-status"></div>
    </div>
</div>

{{-- Board Meta Modal --}}
<div id="meta-modal" class="modal" role="dialog">
    <div class="modal-overlay" onclick="closeMetaModal()"></div>
    <div class="modal-box">
        <button class="modal-close" onclick="closeMetaModal()">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5 inline-block">
                <path fill-rule="evenodd" d="M5.47 5.47a.75.75 0 0 1 1.06 0L12 10.94l5.47-5.47a.75.75 0 1 1 1.06 1.06L13.06 12l5.47 5.47a.75.75 0 1 1-1.06 1.06L12 13.06l-5.47 5.47a.75.75 0 0 1-1.06-1.06L10.94 12 5.47 6.53a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd"/>
            </svg>
        </button>
        <h2>{{ __('play.board_settings') }}</h2>
        <div class="form-group">
            <label>{{ __('play.board_name') }}</label>
            <input type="text" id="meta-name" class="form-control" maxlength="100">
        </div>
        <div class="form-group">
            <label>{{ __('play.description') }}</label>
            <textarea id="meta-desc" class="form-control" rows="3" maxlength="500"></textarea>
        </div>
        <button class="btn btn-gold btn-full" onclick="saveMeta()">{{ __('ui.save') }}</button>
    </div>
</div>
@endsection

@section('scripts')
<script>
window.BOARD_ID      = {{ $board->id }};
window.BOARD_NAME    = {{ Js::from($board->name) }};
window.BOARD_DESC    = {{ Js::from($board->description ?? '') }};
window.SQUARES_DATA  = @json($squares);
window.PATH_DATA     = @json($pathData);
window.CANVAS_ROWS   = {{ $board->canvas_rows }};
window.CANVAS_COLS   = {{ $board->canvas_cols }};
window.CSRF_TOKEN    = document.querySelector('meta[name="csrf-token"]').content;
window.EDIT_MODE     = true;
</script>
<script src="{{ asset('js/board.js') }}"></script>
<script src="{{ asset('js/board-editor.js') }}"></script>
@endsection
