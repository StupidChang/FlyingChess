@extends('layouts.app')
@section('title', '真心話大冒險 — 房間 ' . $game->code)
@section('robots', 'noindex,nofollow')

@section('styles')
<style>
/* Card reveal animation */
@keyframes tdCardReveal{
    0%{opacity:0;transform:scale(.85) translateY(20px);filter:blur(4px)}
    60%{opacity:1;transform:scale(1.03) translateY(-4px);filter:blur(0)}
    100%{opacity:1;transform:scale(1) translateY(0);filter:blur(0)}
}
.td-card{animation:tdCardReveal .5s cubic-bezier(.34,1.56,.64,1) both;position:relative}

/* Glow ring behind card */
.td-card::before{
    content:'';position:absolute;inset:-8px;border-radius:16px;z-index:-1;
    background:conic-gradient(from 0deg,rgba(212,160,23,.3),rgba(239,68,68,.2),rgba(168,85,247,.3),rgba(59,130,246,.2),rgba(212,160,23,.3));
    filter:blur(12px);opacity:0;animation:tdGlowIn .8s .2s ease-out forwards;
}
@keyframes tdGlowIn{to{opacity:1}}

/* Card content text shimmer */
.td-card-content{
    position:relative;overflow:hidden;
    padding:12px 16px;border-radius:10px;
}
.td-card-content::after{
    content:'';position:absolute;top:-4px;bottom:-4px;left:-100%;width:60%;
    background:linear-gradient(90deg,transparent,rgba(255,255,255,.1),transparent);
    border-radius:inherit;filter:blur(6px);
    animation:tdShimmer 2.5s 1s ease-in-out infinite;
}
@keyframes tdShimmer{0%{left:-100%}100%{left:200%}}

/* Category button hover pulse */
.td-cat-btn{position:relative;overflow:hidden}
.td-cat-btn::after{
    content:'';position:absolute;inset:0;border-radius:inherit;
    background:radial-gradient(circle at 50% 50%,rgba(212,160,23,.15),transparent 70%);
    opacity:0;transition:opacity .3s;
}
.td-cat-btn:hover::after{opacity:1}
</style>
@endsection

@section('content')

<div class="td-game-area">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:8px">
        <h1 style="font-size:1.3rem;color:var(--gold)">
            真心話大冒險
        </h1>
        <div style="display:flex;gap:8px;align-items:center">
            @if($isAdult)
                <span style="font-size:.7rem;padding:2px 8px;border-radius:8px;background:#dc2626;color:#fff;font-weight:700">🔞 18禁</span>
            @elseif($hostIsPremium)
                <span class="badge-premium">Premium 題庫已啟用</span>
            @endif
            <form action="{{ route('truth-dare.leave', $game->code) }}" method="POST" style="display:inline" id="td-leave-form">
                @csrf
                <input type="hidden" name="tab_id" id="td-leave-tab-id">
                <button type="submit" class="btn btn-sm btn-outline">返回</button>
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
    <div id="players-area" class="td-players">
        @foreach($game->players()->orderBy('id')->get() as $i => $p)
        <div class="td-player" data-session="{{ $p->session_id }}">
            {{ $p->player_name }}
        </div>
        @endforeach
    </div>

    {{-- Game controls --}}
    <div id="game-controls">

        {{-- Category selection (shown during play) --}}
        <div id="category-area" style="display:none">
            <p id="current-turn-text" style="text-align:center;margin-bottom:16px;color:var(--gold);font-size:1.1rem"></p>
            <div class="td-categories">
                @if($isAdult)
                <button class="td-cat-btn" onclick="drawCard('truth')">
                    <div style="font-size:1.5rem;margin-bottom:4px">🔥</div>
                    私密真心話
                </button>
                <button class="td-cat-btn" onclick="drawCard('dare')">
                    <div style="font-size:1.5rem;margin-bottom:4px">😈</div>
                    大膽挑戰
                </button>
                <button class="td-cat-btn" onclick="drawCard('couple')">
                    <div style="font-size:1.5rem;margin-bottom:4px">💋</div>
                    情趣互動
                </button>
                <button class="td-cat-btn" onclick="drawCard('party')">
                    <div style="font-size:1.5rem;margin-bottom:4px">🍷</div>
                    限制級派對
                </button>
                @else
                <button class="td-cat-btn" onclick="drawCard('truth')">
                    <div style="font-size:1.5rem;margin-bottom:4px">💬</div>
                    真心話
                </button>
                <button class="td-cat-btn" onclick="drawCard('dare')">
                    <div style="font-size:1.5rem;margin-bottom:4px">🎯</div>
                    大冒險
                </button>
                <button class="td-cat-btn" onclick="drawCard('couple')">
                    <div style="font-size:1.5rem;margin-bottom:4px">💕</div>
                    情侶題
                </button>
                <button class="td-cat-btn" onclick="drawCard('party')">
                    <div style="font-size:1.5rem;margin-bottom:4px">🎉</div>
                    派對題
                </button>
                @endif
            </div>
        </div>

        {{-- Card display --}}
        <div id="card-area" style="display:none">
            <div class="td-card">
                <div class="td-card-category" id="card-category"></div>
                <div class="td-card-content" id="card-content"></div>
                <div class="td-card-tier" id="card-tier"></div>
            </div>
            <div style="text-align:center">
                <button class="btn btn-gold btn-xl" onclick="nextPlayer()">下一位</button>
            </div>
        </div>

        {{-- No card message --}}
        <div id="no-card-area" style="display:none;text-align:center;padding:20px">
            <p style="color:var(--rose);margin-bottom:16px" id="no-card-message"></p>
            <button class="btn btn-outline-gold" onclick="showCategories()">重新選擇類別</button>
        </div>
    </div>
</div>

@include('partials.ad-unit', ['zone' => 'lobby_side'])

@endsection

@section('scripts')
<script>
var GAME_CODE = '{{ $game->code }}';
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

function drawCard(category) {
    fetch('/truth-dare/' + GAME_CODE + '/draw', {
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
            var catNames = {truth:'真心話', dare:'大冒險', couple:'情侶題', party:'派對題'};
            document.getElementById('card-category').textContent = catNames[data.card.category] || data.card.category;
            document.getElementById('card-content').textContent = data.card.content;
            document.getElementById('card-tier').textContent = data.card.tier === 'premium' ? '{{ $isAdult ? "🔞 18禁題目" : "🌟 Premium 題目" }}' : '';
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
    });
}

function nextPlayer() {
    fetch('/truth-dare/' + GAME_CODE + '/next' + (TAB_ID ? '?tab_id=' + TAB_ID : ''), {
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
    fetch('/truth-dare/' + GAME_CODE + '/state' + (TAB_ID ? '?tab_id=' + TAB_ID : ''), {headers: {'X-Tab-Id': TAB_ID || ''}})
    .then(r => r.json())
    .then(data => {
        // Update players
        var pa = document.getElementById('players-area');
        pa.innerHTML = '';
        data.players.forEach(function(p, i) {
            var div = document.createElement('div');
            div.className = 'td-player' + (data.game_state.current_player_index === i ? ' active' : '');
            div.textContent = p.player_name;
            pa.appendChild(div);
        });

        // Update turn text
        var cp = data.current_player;
        if (cp && data.status === 'playing') {
            var turnText = document.getElementById('current-turn-text');
            if (cp.session_id === MY_SESSION) {
                turnText.textContent = '輪到你了！選一個類別';
            } else {
                turnText.textContent = '輪到 ' + cp.player_name;
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
