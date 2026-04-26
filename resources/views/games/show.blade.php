@extends('layouts.app')

@section('title', '飛行棋房間 ' . $game->code . ' — 情侶飛行棋')
@section('meta_description', '飛行棋遊戲房間，房間代碼：' . $game->code . '。邀請朋友加入一起玩飛行棋！')
@section('robots', 'noindex,nofollow')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/game.css') }}">
@endsection

@section('content')
<div class="game-page">
    {{-- Sidebar --}}
    <aside class="game-sidebar" aria-label="遊戲資訊">
        <div class="room-info-box">
            <h2 class="room-title">房間代碼</h2>
            <div class="room-code-display">{{ $game->code }}</div>
            <button class="btn btn-sm btn-outline copy-btn" onclick="copyCode('{{ $game->code }}')" aria-label="複製房間代碼">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5 inline-block">
                    <path fill-rule="evenodd" d="M10.5 3A1.501 1.501 0 0 0 9 4.5h6A1.5 1.5 0 0 0 13.5 3h-3Zm-2.693.178A3 3 0 0 1 10.5 1.5h3a3 3 0 0 1 2.694 1.678c.497.042.992.092 1.486.15 1.497.173 2.57 1.46 2.57 2.929V19.5a3 3 0 0 1-3 3H6.75a3 3 0 0 1-3-3V6.257c0-1.47 1.073-2.756 2.57-2.93.493-.057.989-.107 1.487-.15Z" clip-rule="evenodd"/>
                </svg>
                複製代碼
            </button>
        </div>

        <div class="players-box">
            <h3>玩家列表</h3>
            <ul class="players-list" id="players-list" aria-live="polite">
                @foreach($game->players as $p)
                <li class="player-item player-{{ $p->color }}" data-color="{{ $p->color }}">
                    <span class="player-dot {{ $p->color }}" aria-hidden="true"></span>
                    <span class="player-name">{{ $p->player_name }}</span>
                    @if(str_starts_with($p->session_id, 'bot_'))<span class="bot-badge">AI</span>@endif
                    @if($p->is_host && !str_starts_with($p->session_id, 'bot_'))<span class="host-badge">房主</span>@endif
                    @if($myPlayer && $p->session_id === $myPlayer->session_id)<span class="me-badge">我</span>@endif
                </li>
                @endforeach
            </ul>
        </div>

        @if($game->isWaiting())
            @if(!$myPlayer)
            <div class="join-box">
                <h3>加入遊戲</h3>
                <p style="font-size:.85rem;color:var(--text-dim);margin-bottom:12px">
                    輸入名稱後加入此房間
                </p>
                <form action="{{ route('games.join', $game->code) }}" method="POST" id="join-form">
                    @csrf
                    <input type="hidden" name="tab_id" id="join-tab-id">
                    <div class="form-group">
                        <input type="text" name="player_name" class="form-control"
                            placeholder="你的名稱" maxlength="20" required
                            value="{{ session('player_name') }}">
                    </div>
                    <button type="submit" class="btn btn-primary btn-full">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5 inline-block">
                            <path fill-rule="evenodd" d="M4.5 5.653c0-1.427 1.529-2.33 2.779-1.643l11.54 6.347c1.295.712 1.295 2.573 0 3.286L7.28 19.99c-1.25.687-2.779-.217-2.779-1.643V5.653Z" clip-rule="evenodd"/>
                        </svg>
                        加入遊戲
                    </button>
                </form>
                <script>
                    // Set tab_id in join form before submission
                    (function() {
                        if (!sessionStorage.getItem('tab_id')) {
                            sessionStorage.setItem('tab_id', Math.random().toString(36).slice(2, 11));
                        }
                        var el = document.getElementById('join-tab-id');
                        if (el) el.value = sessionStorage.getItem('tab_id');
                    })();
                </script>
            </div>
            @elseif($myPlayer->is_host)
            <div class="host-controls">
                <p class="waiting-text" id="waiting-text">等待其他玩家加入中...</p>
                <p style="font-size:.82rem;color:var(--text-dim);margin-bottom:8px">
                    分享房間代碼 <strong>{{ $game->code }}</strong> 給朋友，或請朋友直接搜尋加入
                </p>
                <button id="start-btn" class="btn btn-primary btn-full" onclick="startGame()" disabled>
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5 inline-block">
                        <path fill-rule="evenodd" d="M4.5 5.653c0-1.427 1.529-2.33 2.779-1.643l11.54 6.347c1.295.712 1.295 2.573 0 3.286L7.28 19.99c-1.25.687-2.779-.217-2.779-1.643V5.653Z" clip-rule="evenodd"/>
                    </svg>
                    開始遊戲
                </button>
                <p class="hint-text">至少需要 2 位玩家才能開始</p>
                <a href="{{ route('games.lobby') }}" class="btn btn-sm btn-outline" style="margin-top:8px;width:100%;text-align:center">返回大廳</a>
            </div>
            @else
            <div class="waiting-box">
                <p class="waiting-text">等待房主開始遊戲...</p>
                <p style="font-size:.82rem;color:var(--text-dim);margin-bottom:12px">
                    房主準備好後遊戲將自動開始
                </p>
                <div class="spinner" aria-label="等待中"></div>
                <a href="{{ route('games.lobby') }}" class="btn btn-sm btn-outline" style="margin-top:16px;width:100%;text-align:center">返回大廳</a>
            </div>
            @endif
        @endif

        @if($game->isPlaying() || $game->isFinished())
        <div class="turn-info-box">
            <h3>目前回合</h3>
            <div id="turn-display" class="turn-display">
                <span class="turn-dot" id="turn-dot"></span>
                <span id="turn-name">--</span>
            </div>
        </div>

        <div class="dice-box">
            <h3>骰子</h3>
            <div id="dice-display" class="dice-display" aria-live="polite">
                <div class="dice" id="dice">?</div>
            </div>
            @if($myPlayer)
            <button id="roll-btn" class="btn btn-primary btn-full" onclick="rollDice()" disabled>
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5 inline-block">
                    <path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25ZM6.262 6.072a8.25 8.25 0 1 0 10.562-.766 4.5 4.5 0 0 1-1.318 1.357L14.25 7.5l.165.33a.809.809 0 0 1-1.086 1.085l-.604-.302a1.125 1.125 0 0 0-1.298.21l-.132.131c-.439.44-.439 1.152 0 1.591l.296.296c.256.257.622.374.98.314l1.17-.195c.323-.054.654.036.905.245l1.33 1.108c.32.267.46.694.358 1.1a8.7 8.7 0 0 1-2.288 4.04l-.723.724a1.125 1.125 0 0 1-1.298.21l-.153-.076a1.125 1.125 0 0 1-.622-1.006v-1.089c0-.298-.119-.585-.33-.796l-1.347-1.347a1.125 1.125 0 0 1 0-1.59l.296-.297a1.125 1.125 0 0 0 0-1.59l-.296-.296a1.125 1.125 0 0 1 0-1.591l.296-.296c.256-.256.622-.374.98-.313l1.17.195c.323.054.654-.036.905-.244l1.33-1.108c.32-.267.46-.694.358-1.1a8.7 8.7 0 0 1-2.288-4.04Z" clip-rule="evenodd"/>
                </svg>
                擲骰子
            </button>
            <p id="roll-hint" class="hint-text" style="margin-top:6px;text-align:center;font-size:.8rem">等待輪到你的回合...</p>
            @endif
        </div>

        <div class="pieces-box">
            <h3>我的棋子</h3>
            <div id="my-pieces" class="my-pieces" aria-live="polite"></div>
        </div>
        @endif

        <div class="log-box">
            <h3>遊戲紀錄</h3>
            <ul id="game-log" class="game-log" aria-live="polite" aria-label="遊戲紀錄"></ul>
        </div>
    </aside>

    {{-- Board --}}
    <main class="game-main" aria-label="飛行棋棋盤">
        @if($game->isWaiting())
        <div class="waiting-overlay">
            <div class="waiting-card">
                <div class="waiting-icon" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:48px;height:48px;opacity:0.6">
                <path fill-rule="evenodd" d="M4.755 10.059a7.5 7.5 0 0 1 12.548-3.364l1.903 1.903h-3.183a.75.75 0 1 0 0 1.5h4.992a.75.75 0 0 0 .75-.75V4.356a.75.75 0 0 0-1.5 0v3.18l-1.9-1.9A9 9 0 0 0 3.306 9.67a.75.75 0 1 0 1.45.388Zm15.408 3.352a.75.75 0 0 0-.919.53 7.5 7.5 0 0 1-12.548 3.364l-1.902-1.903h3.183a.75.75 0 0 0 0-1.5H2.984a.75.75 0 0 0-.75.75v4.992a.75.75 0 0 0 1.5 0v-3.18l1.9 1.9a9 9 0 0 0 15.059-4.035.75.75 0 0 0-.53-.918Z" clip-rule="evenodd"/>
            </svg>
        </div>
                <h2>等待玩家中</h2>
                <p>目前 <strong id="player-count">{{ $game->players_count }}</strong> / {{ $game->max_players }} 人</p>
                <p>分享代碼 <strong class="code-highlight">{{ $game->code }}</strong> 給朋友加入</p>
            </div>
        </div>
        @endif

        @if($game->isFinished())
        <div id="winner-overlay" class="winner-overlay">
            <div class="winner-card">
                <div class="trophy-icon" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:48px;height:48px;color:#FFD700">
                    <path fill-rule="evenodd" d="M5.166 2.621v.858c-1.035.148-2.059.33-3.071.543a.75.75 0 0 0-.584.859 6.753 6.753 0 0 0 6.138 5.6 6.73 6.73 0 0 0 2.743 1.35A6.98 6.98 0 0 1 9.25 15v.25H9a.75.75 0 0 0 0 1.5h1.5v2.128a2.251 2.251 0 0 1-1.679 2.17l-.196.047a.75.75 0 0 0 .353 1.46l.196-.047a3.75 3.75 0 0 0 2.826-3.63V16.75h1.5a.75.75 0 0 0 0-1.5h-.25V15a6.98 6.98 0 0 1-.293-1.342 6.73 6.73 0 0 0 2.743-1.35 6.753 6.753 0 0 0 6.139-5.6.75.75 0 0 0-.585-.858 47.077 47.077 0 0 0-3.07-.543V2.62a.75.75 0 0 0-.658-.744 49.798 49.798 0 0 0-6.093-.377c-2.063 0-4.096.128-6.093.377a.75.75 0 0 0-.657.744Zm0 2.629c0 1.196.312 2.32.857 3.294A5.266 5.266 0 0 1 3.16 5.337a45.6 45.6 0 0 1 2.006-.343v.256Zm13.5 0v-.256c.674.1 1.343.214 2.006.343a5.265 5.265 0 0 1-2.863 3.207 6.72 6.72 0 0 0 .857-3.294Z" clip-rule="evenodd"/>
                </svg>
            </div>
                <h2>遊戲結束！</h2>
                <p id="winner-text">--</p>
                <div style="display:flex;gap:8px;justify-content:center;flex-wrap:wrap;margin-top:12px">
                    <a href="{{ route('games.lobby') }}" class="btn btn-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5 inline-block">
                            <path fill-rule="evenodd" d="M4.755 10.059a7.5 7.5 0 0 1 12.548-3.364l1.903 1.903h-3.183a.75.75 0 1 0 0 1.5h4.992a.75.75 0 0 0 .75-.75V4.356a.75.75 0 0 0-1.5 0v3.18l-1.9-1.9A9 9 0 0 0 3.306 9.67a.75.75 0 1 0 1.45.388Zm15.408 3.352a.75.75 0 0 0-.919.53 7.5 7.5 0 0 1-12.548 3.364l-1.902-1.903h3.183a.75.75 0 0 0 0-1.5H2.984a.75.75 0 0 0-.75.75v4.992a.75.75 0 0 0 1.5 0v-3.18l1.9 1.9a9 9 0 0 0 15.059-4.035.75.75 0 0 0-.53-.918Z" clip-rule="evenodd"/>
                        </svg>
                        回大廳
                    </a>
                    <a href="{{ route('home') }}" class="btn btn-outline">回首頁</a>
                </div>
            </div>
        </div>
        @endif

        <div class="board-container">
            <div id="game-board" class="game-board" role="grid" aria-label="飛行棋棋盤">
                {{-- Board rendered by JavaScript --}}
            </div>
        </div>
    </main>
</div>
@endsection

@section('scripts')
<script>
    // Per-tab unique ID — allows two tabs in the same browser to be different players
    if (!sessionStorage.getItem('tab_id')) {
        sessionStorage.setItem('tab_id', Math.random().toString(36).slice(2, 11));
    }
    window.TAB_ID       = sessionStorage.getItem('tab_id');
    window.GAME_CODE    = '{{ $game->code }}';
    window.MY_COLOR     = '{{ $myPlayer?->color ?? "" }}';
    window.GAME_STATUS  = '{{ $game->status }}';
    window.IS_HOST      = {{ $myPlayer?->is_host ? 'true' : 'false' }};
    window.IS_SOLO      = {{ !empty($game->game_state['bots'] ?? []) ? 'true' : 'false' }};
    window.BOARD_DATA   = @json($boardData);
    window.CSRF_TOKEN   = document.querySelector('meta[name="csrf-token"]').content;
</script>
<script src="{{ asset('js/game.js') }}"></script>
@endsection
