@extends('layouts.app')
@section('title', __('seo.play_meta_title', ['board' => $board->name]) . ' — ' . __('ui.site_name'))
@section('meta_description', __('seo.play_meta_description', ['board' => $board->name]))
@section('og_title', __('seo.play_meta_title', ['board' => $board->name]) . ' — ' . __('ui.site_name'))
@section('og_description', __('seo.play_meta_description', ['board' => $board->name]))
{{-- 同一張棋盤有 /play、/play/{id}、/play/share/{code} 好幾個網址,內容一樣。
     canonical 一律指向 Board::canonicalPlayUrl() 選出的那一個,不要用
     url()->current() —— 那會讓每個網址都自稱正本,就是重複內容。 --}}
@section('canonical', $board->canonicalPlayUrl())
{{-- 內容沒翻完的語系會退回繁中顯示:那一頁不收錄(見 Board::translatedLocales) --}}
@section('robots', $board->isPubliclyIndexable() && $board->isTranslatedFor() ? 'index,follow' : 'noindex,follow')
@section('styles')
<link rel="stylesheet" href="{{ asset_v('css/board.css') }}">
@endsection

@section('content')
<div class="play-page">
    {{-- 這頁整版都是棋盤,版面上沒有放標題的位置,但沒有 H1 的頁面搜尋引擎只能
         從 <title> 猜主題。用 sr-only 補一個,畫面不變。 --}}
    <h1 class="sr-only">{{ $board->name }}</h1>

    {{-- Player Bar --}}
    {{-- 3 人以上:左右兩側各一欄(1、2 號在左,3、4 號在右),骰子在中間。不分組 ——
         每個人各自一顆棋子,先到終點的人贏(見 board.js 的 teamOf)。 --}}
    @php $hasSides = $playerCount >= 3; @endphp
    <div class="player-bar{{ $hasSides ? ' has-sides' : '' }}">
        @if($hasSides)
            <div class="player-side side-left">
                @include('play._player-panel', ['n' => 1])
                @include('play._player-panel', ['n' => 2])
            </div>
        @else
            @include('play._player-panel', ['n' => 1])
        @endif

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
            <div class="turn-tools">
                <button type="button" id="rules-toggle" class="btn btn-sm btn-outline btn-rules"
                        onclick="toggleRules()" aria-expanded="false" aria-controls="rules-panel">
                    {{ __('play.rules_title') }}
                </button>
                {{-- 棋盤大小:放大的格子讀長文字舒服,但欄數多的盤面在筆電上就得捲動。
                     兩者各有適用場景,所以留成可切換的。文字由 board.js 依目前狀態寫入。 --}}
                <button type="button" id="board-size-toggle" class="btn btn-sm btn-outline btn-size"
                        onclick="toggleBoardSize()" aria-pressed="true">
                    {{ __('play.js_board_smaller') }}
                </button>
            </div>
        </div>

        @if($hasSides)
            <div class="player-side side-right">
                @foreach (range(3, $playerCount) as $n)
                    @include('play._player-panel', ['n' => $n])
                @endforeach
            </div>
        @elseif($playerCount >= 2)
            @include('play._player-panel', ['n' => 2])
        @endif
    </div>

    {{-- 進場轉盤畫在棋盤格線裡(見 board.js 的 findWheelSlot),不再獨立成一張
         橫跨版面的卡片 —— 它是棋盤的一部分,棋子從它決定的位置進場。 --}}

    {{-- 棋盤 + 玩法側欄 --}}
    <div class="play-body">
        {{-- Board --}}
        <div class="board-wrap">
            <div id="game-board" class="game-board play-mode">
                {{-- rendered by JS --}}
            </div>
        </div>

        {{-- 玩法說明:桌機為右側欄,窄螢幕為滑出抽屜。可收合,收合後棋盤回到滿版居中。 --}}
        <aside class="rules-panel" id="rules-panel" aria-labelledby="rules-heading">
            <div class="rules-head">
                <h2 class="rules-title" id="rules-heading">{{ __('play.rules_title') }}</h2>
                <button type="button" class="rules-close" onclick="toggleRules(false)"
                        aria-label="{{ __('play.rules_hide') }}">&times;</button>
            </div>
            <ol class="rules-list">
                @foreach (__('play.rules') as $r)
                    <li>{{ $r }}</li>
                @endforeach
            </ol>

            {{-- 格子說明:只列這張棋盤實際用到的類型。圖示由 board.js 依 data-sq-type 填入,
                 跟棋盤格上的是同一份(SQ_TYPE_ICONS),不會對不上。 --}}
            @php
                $usedTypes = collect($squares)->pluck('color')->unique();
                $legendOrder = ['action', 'dare', 'truth', 'strip', 'drink', 'move', 'male', 'female', 'p1', 'p2', 'p3', 'p4'];
            @endphp
            @if($usedTypes->intersect($legendOrder)->isNotEmpty())
            <h3 class="sq-legend-title">{{ __('play.legend_title') }}</h3>
            <ul class="sq-legend">
                @foreach ($legendOrder as $type)
                    @continue(! $usedTypes->contains($type))
                    @php
                        $seat = str_starts_with($type, 'p') ? (int) substr($type, 1) : null;
                        [$name, $desc] = match (true) {
                            $seat !== null => [__('play.legend_seat', ['n' => $seat]), __('play.legend_seat_desc', ['n' => $seat])],
                            $type === 'move' => [__('play.sq_move'), __('play.legend_move_desc')],
                            $type === 'male' => [__('play.legend_male'), __('play.legend_male_desc')],
                            $type === 'female' => [__('play.legend_female'), __('play.legend_female_desc')],
                            default => [__('play.sq_'.$type), null],
                        };
                    @endphp
                    <li class="sq-legend-item legend-{{ $type }}">
                        <span class="sq-type-icon" data-sq-type="{{ $type }}" aria-hidden="true"></span>
                        <span class="sq-legend-text">
                            <span class="sq-legend-name">{{ $name }}</span>
                            @if($desc)<span class="sq-legend-desc">{{ $desc }}</span>@endif
                        </span>
                    </li>
                @endforeach
            </ul>
            @endif
        </aside>
        <div class="rules-scrim" id="rules-scrim" onclick="toggleRules(false)"></div>
    </div>

</div>

{{-- 廣告放在 .play-page 之外。那個容器是固定一個視窗高、而且 overflow:hidden,
     擺在裡面等於跟棋盤搶高度(棋盤是照剩餘空間算出來的),擺在外面則是落在
     第一屏下方,棋盤維持滿版,想看廣告的往下捲。 --}}
@include('partials.ad-unit', ['zone' => 'share'])

{{-- Action Modal --}}
{{-- 進場轉盤沒有彈窗:擲完點數之後棋子直接在棋盤上那顆轉盤裡移到對應的扇形,
     結果寫在轉盤下方的標籤。見 board.js 的 spinEntryWheel。 --}}

{{-- 格子詳情(唯讀)。方形格子放不下長文字是幾何限制,不是可以調掉的樣式問題 ——
     所以要有一個看得到全文的地方。玩的時候點任一格都會開,不影響回合。 --}}
<div id="sq-info-modal" class="modal sq-info-modal" role="dialog" aria-modal="true" aria-labelledby="sq-info-title">
    <div class="modal-overlay" onclick="closeModal('sq-info-modal')"></div>
    <div class="modal-box sq-info-box">
        <button class="modal-close" onclick="closeModal('sq-info-modal')" aria-label="{{ __('play.sq_info_close') }}">✕</button>
        <div id="sq-info-bar" class="sq-info-bar"></div>
        <div class="sq-info-head">
            <span id="sq-info-title" class="sq-info-num"></span>
            <span id="sq-info-cat" class="sq-info-cat"></span>
        </div>
        <p id="sq-info-text" class="sq-info-text"></p>
        <ul id="sq-info-notes" class="sq-info-notes"></ul>
    </div>
</div>

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
{{-- V8.0 規則 8:全場第一位抵達終點者,可讓自己的夥伴前進 1–6 格 --}}
<div id="bonus-modal" class="modal bonus-modal" role="dialog" aria-modal="true">
    <div class="modal-overlay"></div>
    <div class="modal-box">
        <h2>{{ __('play.bonus_title') }}</h2>
        <p id="bonus-text" class="bonus-text"></p>
        <div id="bonus-btns" class="bonus-btns"></div>
    </div>
</div>

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
    <div class="modal-box setup-box{{ $hasSides ? ' is-multi' : '' }}">
        <h2>{{ $board->name }}</h2>
        @if($board->description)<p class="setup-desc">{{ $board->description }}</p>@endif
        {{-- 人數切換:重新載入同一頁帶 ?players=N(canonical 不含它,不會多出索引頁)。
             多男多女的棋盤預設開 4 人(目前引擎上限),1男1女的開 2 人。 --}}
        <div class="setup-players">
            <span class="setup-players-label">
                {{ __('play.audience_setup') }}:{{ $board->isGroupPlay() ? __('play.audience_group') : __('play.audience_couple') }}
            </span>
            <div class="setup-players-pick seg-toggle" role="group" aria-label="{{ __('play.player_count') }}">
                @foreach (range(1, 4) as $n)
                    <a href="{{ request()->fullUrlWithQuery(['players' => $n]) }}" rel="nofollow"
                       @class(['is-on' => $n === $playerCount])
                       @if($n === $playerCount) aria-current="true" @endif><span>{{ __('play.players_n', ['n' => $n]) }}</span></a>
                @endforeach
            </div>
        </div>
        {{-- 追上別人時對方回起點(board.js 的 captureAt)。預設照棋盤作者的設定,開局前可以改 --}}
        <div class="setup-rule">
            <span class="setup-rule-label">{{ __('play.capture_label') }}</span>
            <div class="seg-toggle" role="radiogroup" aria-label="{{ __('play.capture_label') }}">
                <label><input type="radio" name="capture-rule" value="on" @checked($captureEnabled ?? true)><span>{{ __('play.capture_on') }}</span></label>
                <label><input type="radio" name="capture-rule" value="off" @checked(! ($captureEnabled ?? true))><span>{{ __('play.capture_off') }}</span></label>
            </div>
        </div>

        {{-- 棋子樣式:記在這台裝置(board.js 的 pieceStylePref),開局時套用 --}}
        <div class="setup-rule">
            <span class="setup-rule-label">{{ __('play.piece_style') }}</span>
            <div class="seg-toggle" role="radiogroup" aria-label="{{ __('play.piece_style') }}">
                @foreach (['disc', 'pawn', 'heart'] as $style)
                    <label><input type="radio" name="piece-style" value="{{ $style }}" @checked($style === 'disc')><span>{{ __('play.piece_style_'.$style) }}</span></label>
                @endforeach
            </div>
        </div>

        {{-- 每位玩家一行:棋子顏色 + 名字 + 性別切換。不分組;3 人以上在桌機排成兩欄。 --}}
        <div class="setup-list">
        @foreach (range(1, $playerCount) as $n)
            @php $defaultGender = $n % 2 === 1 ? 'male' : 'female'; @endphp
            <div class="form-group setup-player">
                <label for="setup-p{{ $n }}" class="setup-player-label">{{ __('play.player_name', ['n' => $n]) }}</label>
                <div class="setup-player-row">
                {{-- 這一位在棋盤上的棋子顏色(跟 .piece-N 同一組),設定時就對得上是哪一顆 --}}
                <span class="setup-seat seat-{{ $n }}" aria-hidden="true"></span>
                <input type="text" id="setup-p{{ $n }}" class="form-control"
                       value="{{ __('play.player_name', ['n' => $n]) }}" maxlength="12">
                {{-- 骰一個隨機名字。頁面載入時 board.js 會先幫每位骰一次當預設值 --}}
                <button type="button" class="setup-name-dice" data-for="setup-p{{ $n }}"
                        aria-label="{{ __('play.name_dice') }}" title="{{ __('play.name_dice') }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                        <rect x="3.75" y="3.75" width="16.5" height="16.5" rx="4"/>
                        <circle cx="8.25" cy="8.25" r="1.15" fill="currentColor" stroke="none"/>
                        <circle cx="15.75" cy="8.25" r="1.15" fill="currentColor" stroke="none"/>
                        <circle cx="12" cy="12" r="1.15" fill="currentColor" stroke="none"/>
                        <circle cx="8.25" cy="15.75" r="1.15" fill="currentColor" stroke="none"/>
                        <circle cx="15.75" cy="15.75" r="1.15" fill="currentColor" stroke="none"/>
                    </svg>
                </button>
                {{-- 分段切換:原生 radio 只是視覺上藏起來(鍵盤、讀屏照常),board.js 仍讀 :checked --}}
                <div class="gender-radio-group seg-toggle" role="radiogroup" aria-label="{{ __('play.player_name', ['n' => $n]) }}">
                    <label class="is-male"><input type="radio" name="p{{ $n }}-gender" value="male"
                        @if($defaultGender === 'male') checked @endif><span>{{ __('play.male') }}</span></label>
                    <label class="is-female"><input type="radio" name="p{{ $n }}-gender" value="female"
                        @if($defaultGender === 'female') checked @endif><span>{{ __('play.female') }}</span></label>
                </div>
                </div>
            </div>
        @endforeach
        </div>
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


@section('scripts')
<script>
window.BOARD_ID     = {{ $board->id }};
window.SQUARES_DATA = @json($squares);
window.PATH_DATA    = @json($pathData);
window.CANVAS_ROWS  = {{ $board->canvas_rows ?? 11 }};
window.CANVAS_COLS  = {{ $board->canvas_cols ?? 13 }};
window.PLAYER_COUNT = {{ $playerCount }};
window.START_WHEEL  = @json($startWheel ?? null);
window.CAPTURE_ON   = @json($captureEnabled ?? true);
window.EDIT_MODE    = false;
window.PLAY_I18N    = @json(play_i18n());
</script>
<script src="{{ asset_v('js/sq-icons.js') }}"></script>
<script src="{{ asset_v('js/board.js') }}"></script>
@endsection
