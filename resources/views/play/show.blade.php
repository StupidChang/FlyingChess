@extends('layouts.app')
@section('title', __('seo.play_meta_title', ['board' => $board->name]) . ' — ' . __('ui.site_name'))
@section('meta_description', __('seo.play_meta_description', ['board' => $board->name]))
@section('og_title', __('seo.play_meta_title', ['board' => $board->name]) . ' — ' . __('ui.site_name'))
@section('og_description', __('seo.play_meta_description', ['board' => $board->name]))
@section('canonical', url()->current())
@section('robots', $board->is_default ? 'index,follow' : 'noindex,follow')
@section('styles')
<link rel="stylesheet" href="{{ asset_v('css/board.css') }}">
@endsection

@section('content')
<div class="play-page">
    {{-- Player Bar --}}
    <div class="player-bar">
        <div id="p1-panel" class="player-panel p1 active">
            <div class="pawn pawn-1" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5 inline-block">
                    <path fill-rule="evenodd" d="M7.5 6a4.5 4.5 0 1 1 9 0 4.5 4.5 0 0 1-9 0ZM3.751 20.105a8.25 8.25 0 0 1 16.498 0 .75.75 0 0 1-.437.695A18.683 18.683 0 0 1 12 22.5c-2.786 0-5.433-.608-7.812-1.7a.75.75 0 0 1-.437-.695Z" clip-rule="evenodd"/>
                </svg>
            </div>
            <div class="player-info">
                <span id="p1-name" class="pname">{{ __('play.player_1') }}</span>
                <span id="p1-pos" class="ppos">{{ __('play.start_point') }}</span>
            </div>
        </div>

        <div class="turn-center">
            <div id="turn-label" class="turn-label">{{ __('play.turn_of', ['name' => __('play.player_1')]) }}</div>
            <div id="dice-display" class="dice-display">
                <div id="dice" class="dice" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <rect x="3.75" y="3.75" width="16.5" height="16.5" rx="4"/>
                        <circle cx="8.25" cy="8.25" r="1.15" fill="currentColor" stroke="none"/>
                        <circle cx="15.75" cy="8.25" r="1.15" fill="currentColor" stroke="none"/>
                        <circle cx="12" cy="12" r="1.15" fill="currentColor" stroke="none"/>
                        <circle cx="8.25" cy="15.75" r="1.15" fill="currentColor" stroke="none"/>
                        <circle cx="15.75" cy="15.75" r="1.15" fill="currentColor" stroke="none"/>
                    </svg>
                </div>
            </div>
            <button id="roll-btn" class="btn btn-gold btn-roll" onclick="rollDice()">{{ __('play.roll_dice') }}</button>
        </div>

        @if($playerCount >= 2)
        <div id="p2-panel" class="player-panel p2">
            <div class="pawn pawn-2" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5 inline-block">
                    <path fill-rule="evenodd" d="M7.5 6a4.5 4.5 0 1 1 9 0 4.5 4.5 0 0 1-9 0ZM3.751 20.105a8.25 8.25 0 0 1 16.498 0 .75.75 0 0 1-.437.695A18.683 18.683 0 0 1 12 22.5c-2.786 0-5.433-.608-7.812-1.7a.75.75 0 0 1-.437-.695Z" clip-rule="evenodd"/>
                </svg>
            </div>
            <div class="player-info">
                <span id="p2-name" class="pname">{{ __('play.player_2') }}</span>
                <span id="p2-pos" class="ppos">{{ __('play.start_point') }}</span>
            </div>
        </div>
        @endif
    </div>

    {{-- 廣告版位：分享頁底部 --}}
    @include('partials.ad-unit', ['zone' => 'share'])

    {{-- Board --}}
    <div class="board-wrap">
        <div id="game-board" class="game-board play-mode">
            {{-- rendered by JS --}}
        </div>
    </div>
</div>

{{-- Action Modal --}}
<div id="action-modal" class="modal action-modal" role="dialog" aria-modal="true">
    <div class="modal-overlay"></div>
    <div class="modal-box action-box">
        <div class="action-dice-result">
            <span id="action-dice-face"></span>
            {{ __('play.rolled_prefix') }} <strong id="action-dice">?</strong> {{ __('play.rolled_suffix') }}
        </div>
        <div id="action-color-bar" class="action-color-bar"></div>
        <div id="action-text" class="action-text">--</div>
        <div id="skip-notice" class="skip-notice hidden">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5 inline-block">
                <path fill-rule="evenodd" d="M9.401 3.003c1.155-2 4.043-2 5.197 0l7.355 12.748c1.154 1.999-.29 4.5-2.599 4.5H4.645c-2.309 0-3.752-2.5-2.598-4.5L9.4 3.003ZM12 8.25a.75.75 0 0 1 .75.75v3.75a.75.75 0 0 1-1.5 0V9a.75.75 0 0 1 .75-.75Zm0 8.25a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Z" clip-rule="evenodd"/>
            </svg>
            {{ __('play.skip_turn') }}
        </div>
        <div id="gender-notice" class="gender-notice hidden"></div>
        {{-- Normal complete button (no fly) --}}
        <button id="btn-complete" class="btn btn-gold btn-xl" onclick="confirmAction('complete')">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5 inline-block">
                <path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12Zm13.36-1.814a.75.75 0 1 0-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 0 0-1.06 1.06l2.25 2.25a.75.75 0 0 0 1.14-.094l3.75-5.25Z" clip-rule="evenodd"/>
            </svg>
            {{ __('play.complete_next') }}
        </button>
        {{-- Fly choice buttons (shown when square has fly_to) --}}
        <div id="fly-btn-group" class="fly-btn-group hidden">
            <button class="btn btn-fly btn-xl" onclick="confirmAction('fly')">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5 inline-block">
                    <path d="M3.478 2.405a.75.75 0 0 0-.926.94l2.432 7.905H13.5a.75.75 0 0 1 0 1.5H4.984l-2.432 7.905a.75.75 0 0 0 .926.94 60.519 60.519 0 0 0 18.445-8.986.75.75 0 0 0 0-1.218A60.517 60.517 0 0 0 3.478 2.405Z"/>
                </svg>
                {{ __('play.fly_prefix') }} <strong id="fly-dest-label">?</strong> {{ __('play.fly_suffix') }}
            </button>
            <button class="btn btn-punish btn-xl" onclick="confirmAction('punish')">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5 inline-block">
                    <path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25Zm-1.72 6.97a.75.75 0 1 0-1.06 1.06L10.94 12l-1.72 1.72a.75.75 0 1 0 1.06 1.06L12 13.06l1.72 1.72a.75.75 0 1 0 1.06-1.06L13.06 12l1.72-1.72a.75.75 0 1 0-1.06-1.06L12 10.94l-1.72-1.72Z" clip-rule="evenodd"/>
                </svg>
                {{ __('play.punish_stay') }}
            </button>
        </div>
    </div>
</div>

{{-- Win Modal --}}
<div id="win-modal" class="modal win-modal" role="dialog" aria-modal="true">
    <div class="modal-overlay"></div>
    <div class="modal-box win-box">
        <div class="win-trophy">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:48px;height:48px;color:var(--gold, #d9a441)">
            <path fill-rule="evenodd" d="M5.166 2.621v.858c-1.035.148-2.059.33-3.071.543a.75.75 0 0 0-.584.859 6.753 6.753 0 0 0 6.138 5.6 6.73 6.73 0 0 0 2.743 1.35A6.98 6.98 0 0 1 9.25 15v.25H9a.75.75 0 0 0 0 1.5h1.5v2.128a2.251 2.251 0 0 1-1.679 2.17l-.196.047a.75.75 0 0 0 .353 1.46l.196-.047a3.75 3.75 0 0 0 2.826-3.63V16.75h1.5a.75.75 0 0 0 0-1.5h-.25V15a6.98 6.98 0 0 1-.293-1.342 6.73 6.73 0 0 0 2.743-1.35 6.753 6.753 0 0 0 6.139-5.6.75.75 0 0 0-.585-.858 47.077 47.077 0 0 0-3.07-.543V2.62a.75.75 0 0 0-.658-.744 49.798 49.798 0 0 0-6.093-.377c-2.063 0-4.096.128-6.093.377a.75.75 0 0 0-.657.744Zm0 2.629c0 1.196.312 2.32.857 3.294A5.266 5.266 0 0 1 3.16 5.337a45.6 45.6 0 0 1 2.006-.343v.256Zm13.5 0v-.256c.674.1 1.343.214 2.006.343a5.265 5.265 0 0 1-2.863 3.207 6.72 6.72 0 0 0 .857-3.294Z" clip-rule="evenodd"/>
        </svg>
    </div>
        <h2 id="win-title">{{ __('play.game_over') }}</h2>
        <p id="win-text"></p>
        <div class="win-actions">
            <button class="btn btn-gold btn-xl" onclick="resetGame()">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5 inline-block">
                    <path fill-rule="evenodd" d="M4.755 10.059a7.5 7.5 0 0 1 12.548-3.364l1.903 1.903h-3.183a.75.75 0 1 0 0 1.5h4.992a.75.75 0 0 0 .75-.75V4.356a.75.75 0 0 0-1.5 0v3.18l-1.9-1.9A9 9 0 0 0 3.306 9.67a.75.75 0 1 0 1.45.388Zm15.408 3.352a.75.75 0 0 0-.919.53 7.5 7.5 0 0 1-12.548 3.364l-1.902-1.903h3.183a.75.75 0 0 0 0-1.5H2.984a.75.75 0 0 0-.75.75v4.992a.75.75 0 0 0 1.5 0v-3.18l1.9 1.9a9 9 0 0 0 15.059-4.035.75.75 0 0 0-.53-.918Z" clip-rule="evenodd"/>
                </svg>
                {{ __('play.play_again') }}
            </button>
            <a href="{{ route('home') }}" class="btn btn-outline btn-xl">← {{ __('play.back_home') }}</a>
        </div>
    </div>
</div>

{{-- 3D Dice Overlay --}}
<div id="dice-overlay" class="dice-overlay">
    <div class="dice-scene">
        <div id="dice-cube" class="dice-cube"></div>
    </div>
</div>

{{-- Setup Modal --}}
<div id="setup-modal" class="modal setup-modal open" role="dialog" aria-modal="true">
    <div class="modal-overlay"></div>
    <div class="modal-box setup-box">
        <h2>{{ $board->name }}</h2>
        @if($board->description)<p style="color:var(--text-dim);margin-bottom:16px">{{ $board->description }}</p>@endif
        <div class="form-group">
            <label for="setup-p1">{{ __('play.player_name', ['n' => 1]) }}</label>
            <input type="text" id="setup-p1" class="form-control" value="{{ __('play.player_1') }}" maxlength="12">
            <div class="gender-radio-group" style="margin-top:8px">
                <label><input type="radio" name="p1-gender" value="male" checked> {{ __('play.male') }}</label>
                <label><input type="radio" name="p1-gender" value="female"> {{ __('play.female') }}</label>
            </div>
        </div>
        @if($playerCount >= 2)
        <div class="form-group">
            <label for="setup-p2">{{ __('play.player_name', ['n' => 2]) }}</label>
            <input type="text" id="setup-p2" class="form-control" value="{{ __('play.player_2') }}" maxlength="12">
            <div class="gender-radio-group" style="margin-top:8px">
                <label><input type="radio" name="p2-gender" value="male"> {{ __('play.male') }}</label>
                <label><input type="radio" name="p2-gender" value="female" checked> {{ __('play.female') }}</label>
            </div>
        </div>
        @endif
        <button class="btn btn-gold btn-full" onclick="startSetup()">
            {{ __('play.start_game') }}
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5 inline-block">
                <path fill-rule="evenodd" d="M4.5 5.653c0-1.427 1.529-2.33 2.779-1.643l11.54 6.347c1.295.712 1.295 2.573 0 3.286L7.28 19.99c-1.25.687-2.779-.217-2.779-1.643V5.653Z" clip-rule="evenodd"/>
            </svg>
        </button>
        <div style="text-align:center;margin-top:12px">
            @auth
            <a href="{{ route('boards.edit', $board) }}" class="btn btn-sm btn-outline">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5 inline-block">
                    <path d="M21.731 2.269a2.625 2.625 0 0 0-3.712 0l-1.157 1.157 3.712 3.712 1.157-1.157a2.625 2.625 0 0 0 0-3.712ZM19.513 8.199l-3.712-3.712-8.4 8.4a5.25 5.25 0 0 0-1.32 2.214l-.8 2.685a.75.75 0 0 0 .933.933l2.685-.8a5.25 5.25 0 0 0 2.214-1.32l8.4-8.4Z"/>
                </svg>
                {{ __('play.edit_board_first') }}
            </a>
            @else
            <p style="font-size:.85rem;color:var(--text-dim)">
                <a href="{{ route('login') }}" style="color:var(--gold)">{{ __('auth.login_title') }}</a>{{ __('play.after_login_customize') }}
            </p>
            @endauth
        </div>
    </div>
</div>
@endsection

@php
    // Runtime strings consumed by board.js's tp() helper (camelCase keys
    // matching the JS side, mapped to the existing play.* translations).
    $playI18n = [
        'centerTitle'  => __('play.js_center_title'),
        'centerRules'  => __('play.js_center_rules'),
        'corner1'      => __('play.js_corner_1'),
        'corner2'      => __('play.js_corner_2'),
        'corner3'      => __('play.js_corner_3'),
        'corner4'      => __('play.js_corner_4'),
        'saving'       => __('play.js_saving'),
        'saved'        => __('play.js_saved'),
        'saveFailed'   => __('play.js_save_failed'),
        'player1'      => __('play.player_1'),
        'player2'      => __('play.player_2'),
        'startPoint'   => __('play.start_point'),
        'endPoint'     => __('play.js_end_point'),
        'stepN'        => __('play.js_step_n'),
        'turnOf'       => __('play.turn_of'),
        'skipTurnName' => __('play.js_skip_turn_name'),
        'male'         => __('play.male'),
        'female'       => __('play.female'),
        'genderSkip'   => __('play.js_gender_skip'),
        'normalSquare' => __('play.js_normal_square'),
        'winTitle'     => __('play.js_win_title'),
        'winText'      => __('play.js_win_text'),
    ];
@endphp

@section('scripts')
<script>
window.BOARD_ID     = {{ $board->id }};
window.SQUARES_DATA = @json($squares);
window.PATH_DATA    = @json($pathData);
window.CANVAS_ROWS  = {{ $board->canvas_rows ?? 11 }};
window.CANVAS_COLS  = {{ $board->canvas_cols ?? 13 }};
window.PLAYER_COUNT = {{ $playerCount }};
window.EDIT_MODE    = false;
window.PLAY_I18N    = @json($playI18n);
</script>
<script src="{{ asset_v('js/board.js') }}"></script>
@endsection
