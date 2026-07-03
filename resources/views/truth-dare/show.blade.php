@extends('layouts.app')
@section('title', __('games.truth_dare') . ' — ' . __('games.td_room_title', ['code' => $game->code]))
@section('meta_description', __('games.td_room_meta', ['code' => $game->code]))
@section('robots', 'noindex,nofollow')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/minigames.css') }}">
<style>
/* Card reveal animation */
@keyframes tdCardReveal{
    0%{opacity:0;transform:scale(.85) translateY(20px);filter:blur(4px)}
    60%{opacity:1;transform:scale(1.03) translateY(-4px);filter:blur(0)}
    100%{opacity:1;transform:scale(1) translateY(0);filter:blur(0)}
}
.mg-content-card{animation:tdCardReveal .5s cubic-bezier(.34,1.56,.64,1) both}

/* Glow ring behind card */
.mg-content-card::before{
    content:'';position:absolute;inset:-8px;border-radius:16px;z-index:-1;
    background:conic-gradient(from 0deg,rgba(217,164,65,.3),rgba(244,63,94,.2),rgba(168,85,247,.3),rgba(56,189,248,.2),rgba(217,164,65,.3));
    filter:blur(12px);opacity:0;animation:tdGlowIn .8s .2s ease-out forwards;
}
@keyframes tdGlowIn{to{opacity:1}}

/* Card content text shimmer */
.mg-content-card-text{
    position:relative;overflow:hidden;display:block;
    padding:12px 16px;border-radius:10px;
}
.mg-content-card-text::after{
    content:'';position:absolute;top:-4px;bottom:-4px;left:-100%;width:60%;
    background:linear-gradient(90deg,transparent,rgba(255,255,255,.1),transparent);
    border-radius:inherit;filter:blur(6px);
    animation:tdShimmer 2.5s 1s ease-in-out infinite;
}
@keyframes tdShimmer{0%{left:-100%}100%{left:200%}}
</style>
@endsection

@section('content')

<div class="mg-page mg-page--lg">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:8px">
        <h1 class="mg-title mg-title--inline">
            {{ __('games.truth_dare') }}
        </h1>
        <div style="display:flex;gap:8px;align-items:center">
            @if($isAdult)
                <span class="mg-badge mg-badge-adult">{{ __('games.td_adult_badge') }}</span>
            @elseif($hostIsPremium)
                <span class="badge-premium">{{ __('games.td_premium_active') }}</span>
            @endif
            <form action="{{ route('truth-dare.leave', $game->code) }}" method="POST" style="display:inline" id="td-leave-form">
                @csrf
                <input type="hidden" name="tab_id" id="td-leave-tab-id">
                <button type="submit" class="btn btn-sm btn-outline">{{ __('games.td_back') }}</button>
            </form>
            <script>
            (function(){
                if (!sessionStorage.getItem('tab_id')) {
                    sessionStorage.setItem('tab_id', Math.random().toString(36).slice(2, 11));
                }
                var el = document.getElementById('td-leave-tab-id');
                if (el) el.value = sessionStorage.getItem('tab_id');
            })();
            </script>
        </div>
    </div>

    {{-- Players --}}
    <div id="players-area" class="mg-players">
        @foreach($game->players()->orderBy('id')->get() as $i => $p)
        <div class="mg-player-chip" data-session="{{ $p->session_id }}">
            {{ $p->player_name }}
        </div>
        @endforeach
    </div>

    {{-- Game controls --}}
    <div id="game-controls">

        {{-- Category selection (shown during play) --}}
        <div id="category-area" style="display:none">
            <p id="current-turn-text" class="mg-round-badge"></p>
            <div class="mg-cat-grid">
                @php $catAdult = $isAdult ? ' is-adult' : ''; @endphp
                <button class="mg-cat-btn{{ $catAdult }}" onclick="drawCard('truth')">
                    <svg class="mg-cat-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3-3c-1.354 0-2.694-.055-4.02-.163a2.115 2.115 0 0 1-.825-.242m9.345-8.334a2.126 2.126 0 0 0-.476-.095 48.64 48.64 0 0 0-8.048 0c-1.131.094-1.976 1.057-1.976 2.192v4.286c0 .837.46 1.58 1.155 1.951m9.345-8.334V6.637c0-1.621-1.152-3.026-2.76-3.235A48.455 48.455 0 0 0 11.25 3c-2.115 0-4.198.137-6.24.402-1.608.209-2.76 1.614-2.76 3.235v6.226c0 1.621 1.152 3.024 2.76 3.234.577.075 1.157.14 1.74.194V21l4.155-4.155" /></svg>
                    {{ $isAdult ? __('games.td_cat_truth_adult') : __('games.td_cat_truth') }}
                </button>
                <button class="mg-cat-btn{{ $catAdult }}" onclick="drawCard('dare')">
                    <svg class="mg-cat-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.362 5.214A8.252 8.252 0 0 1 12 21 8.25 8.25 0 0 1 6.038 7.047 8.287 8.287 0 0 0 9 9.601a8.983 8.983 0 0 1 3.361-6.867 8.21 8.21 0 0 0 3 2.48Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M12 18a3.75 3.75 0 0 0 .495-7.467 5.99 5.99 0 0 0-1.925 3.546 5.974 5.974 0 0 1-2.133-1.001A3.75 3.75 0 0 0 12 18Z" /></svg>
                    {{ $isAdult ? __('games.td_cat_dare_adult') : __('games.td_cat_dare') }}
                </button>
                <button class="mg-cat-btn{{ $catAdult }}" onclick="drawCard('couple')">
                    <svg class="mg-cat-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" /></svg>
                    {{ $isAdult ? __('games.td_cat_couple_adult') : __('games.td_cat_couple') }}
                </button>
                <button class="mg-cat-btn{{ $catAdult }}" onclick="drawCard('party')">
                    <svg class="mg-cat-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.456-2.456L14.25 6l1.035-.259a3.375 3.375 0 0 0 2.456-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456ZM16.894 20.567 16.5 21.75l-.394-1.183a2.25 2.25 0 0 0-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 0 0 1.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 0 0 1.423 1.423l1.183.394-1.183.394a2.25 2.25 0 0 0-1.423 1.423Z" /></svg>
                    {{ $isAdult ? __('games.td_cat_party_adult') : __('games.td_cat_party') }}
                </button>
            </div>
        </div>

        {{-- Card display --}}
        <div id="card-area" style="display:none">
            <div class="mg-content-card">
                <div class="mg-content-card-category" id="card-category"></div>
                <div class="mg-content-card-text" id="card-content"></div>
                <div class="mg-content-card-tier" id="card-tier"></div>
            </div>
            <div style="text-align:center">
                <button class="btn btn-gold btn-xl" onclick="nextPlayer()">{{ __('games.td_next_player') }}</button>
            </div>
        </div>

        {{-- No card message --}}
        <div id="no-card-area" style="display:none;text-align:center;padding:20px">
            <p style="color:var(--rose);margin-bottom:16px" id="no-card-message"></p>
            <button class="btn btn-outline-gold" onclick="showCategories()">{{ __('games.td_pick_again') }}</button>
        </div>
    </div>
</div>

@include('partials.ad-unit', ['zone' => 'lobby_side'])

@endsection

@section('scripts')
<script>
var GAME_CODE = '{{ $game->code }}';
var DRAW_URL  = @json(route('truth-dare.draw', $game->code));
var NEXT_URL  = @json(route('truth-dare.next', $game->code));
var STATE_URL = @json(route('truth-dare.state', $game->code));
var IS_PLAYING = {{ $game->isPlaying() ? 'true' : 'false' }};
if (!sessionStorage.getItem('tab_id')) {
    sessionStorage.setItem('tab_id', Math.random().toString(36).slice(2, 11));
}
var TAB_ID = sessionStorage.getItem('tab_id');
var MY_SESSION = '{{ session()->getId() }}' + (TAB_ID ? '|' + TAB_ID : '');
var pollTimer;

@if($myPlayer && env('GOOGLE_GA4_ID'))
if (typeof gtag !== 'undefined') {
    gtag('event', 'game_joined', {game_type: 'truth_or_dare', game_code: GAME_CODE});
}
@endif

function showCategories() {
    document.getElementById('category-area').style.display = 'block';
    document.getElementById('card-area').style.display = 'none';
    document.getElementById('no-card-area').style.display = 'none';
}

var drawing = false;
function setCatButtonsDisabled(disabled) {
    document.querySelectorAll('.mg-cat-btn').forEach(function (b) { b.disabled = disabled; });
}

function drawCard(category) {
    if (drawing) return;
    drawing = true;
    setCatButtonsDisabled(true);
    fetch(DRAW_URL, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-Tab-Id': TAB_ID || ''
        },
        body: JSON.stringify({category: category, tab_id: TAB_ID})
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            var catNames = {
                truth:  @json(__('games.td_cat_truth')),
                dare:   @json(__('games.td_cat_dare')),
                couple: @json(__('games.td_cat_couple')),
                party:  @json(__('games.td_cat_party'))
            };
            document.getElementById('card-category').textContent = catNames[data.card.category] || data.card.category;
            document.getElementById('card-content').textContent = data.card.content;
            document.getElementById('card-tier').textContent = data.card.tier === 'premium' ? @json($isAdult ? __('games.td_card_adult_label') : __('games.td_card_premium_label')) : '';
            document.getElementById('category-area').style.display = 'none';
            document.getElementById('card-area').style.display = 'block';
            @if(env('GOOGLE_GA4_ID'))
            gtag('event', 'truth_dare_card_drawn', {category: category, tier: data.card.tier});
            @endif
        } else {
            document.getElementById('no-card-message').textContent = data.message;
            document.getElementById('category-area').style.display = 'none';
            document.getElementById('no-card-area').style.display = 'block';
        }
    })
    .finally(function () {
        drawing = false;
        setCatButtonsDisabled(false);
    });
}

function nextPlayer() {
    fetch(NEXT_URL + (TAB_ID ? '?tab_id=' + TAB_ID : ''), {
        method: 'POST',
        headers: {'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json', 'X-Tab-Id': TAB_ID || ''}
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            pollState();
            showCategories();
        }
    });
}

function pollState() {
    fetch(STATE_URL + (TAB_ID ? '?tab_id=' + TAB_ID : ''), {headers: {'X-Tab-Id': TAB_ID || ''}})
    .then(r => r.json())
    .then(data => {
        // Update players
        var pa = document.getElementById('players-area');
        pa.innerHTML = '';
        data.players.forEach(function(p, i) {
            var div = document.createElement('div');
            div.className = 'mg-player-chip' + (data.game_state.current_player_index === i ? ' is-active' : '');
            div.textContent = p.player_name;
            pa.appendChild(div);
        });

        // Update turn text
        var cp = data.current_player;
        if (cp && data.status === 'playing') {
            var turnText = document.getElementById('current-turn-text');
            if (cp.session_id === MY_SESSION) {
                turnText.textContent = @json(__('games.td_your_turn_pick'));
            } else {
                turnText.textContent = @json(__('games.td_player_turn', ['name' => '__NAME__'])).replace('__NAME__', cp.player_name);
            }
        }

        if (data.status === 'playing' && !IS_PLAYING) {
            IS_PLAYING = true;
            showCategories();
        }
    });
}

// Poll every 3 seconds
pollTimer = setInterval(pollState, 3000);
if (IS_PLAYING) {
    showCategories();
    pollState();
}
</script>
@endsection
