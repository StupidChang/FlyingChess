@extends('layouts.app')
@section('title', '真心話大冒險 — 房間 ' . $game->code)
@section('robots', 'noindex,nofollow')
@section('content')

<div class="td-game-area">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:8px">
        <h1 style="font-size:1.3rem;color:var(--gold)">
            真心話大冒險
            <span style="font-size:.9rem;color:var(--text-dim)">#{{ $game->code }}</span>
        </h1>
        <div style="display:flex;gap:8px;align-items:center">
            @if($hostIsPremium)
                <span class="badge-premium">Premium 題庫已啟用</span>
            @endif
            <form action="{{ route('truth-dare.leave', $game->code) }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline">離開房間</button>
            </form>
        </div>
    </div>

    {{-- Join form (if not in room) --}}
    @if(!$myPlayer)
    <div class="form-card" style="margin-bottom:20px">
        <h2 style="font-size:1.1rem;color:var(--gold);margin-bottom:12px">加入房間</h2>
        <form action="{{ route('truth-dare.join', $game->code) }}" method="POST" style="display:flex;gap:8px">
            @csrf
            <input type="text" name="player_name" class="form-control" placeholder="你的暱稱"
                   required maxlength="20" value="{{ $playerName }}" style="flex:1">
            <button type="submit" class="btn btn-gold">加入</button>
        </form>
    </div>
    @endif

    {{-- Players --}}
    <div id="players-area" class="td-players">
        @foreach($game->players()->orderBy('id')->get() as $i => $p)
        <div class="td-player" data-session="{{ $p->session_id }}">
            {{ $p->player_name }}
            @if($p->is_host) <span style="color:var(--gold);font-size:.75rem">(房主)</span> @endif
        </div>
        @endforeach
    </div>

    {{-- Game controls --}}
    <div id="game-controls">
        @if($game->isWaiting())
        <div id="waiting-area" style="text-align:center;padding:20px">
            <p style="color:var(--text-dim);margin-bottom:16px">等待玩家加入中... ({{ $game->players()->count() }}/6)</p>
            @if($myPlayer)
            <button class="btn btn-gold btn-xl" onclick="startGame()">開始遊戲</button>
            @endif
        </div>
        @endif

        {{-- Category selection (shown during play) --}}
        <div id="category-area" style="display:none">
            <p id="current-turn-text" style="text-align:center;margin-bottom:16px;color:var(--gold);font-size:1.1rem"></p>
            <div class="td-categories">
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
var MY_SESSION = '{{ session()->getId() }}';
var pollTimer;

@if($myPlayer && env('GOOGLE_GA4_ID'))
if (typeof gtag !== 'undefined') {
    gtag('event', 'game_joined', {game_type: 'truth_or_dare', game_code: GAME_CODE});
}
@endif

function startGame() {
    fetch('/truth-dare/' + GAME_CODE + '/start', {
        method: 'POST',
        headers: {'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json'}
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            IS_PLAYING = true;
            document.getElementById('waiting-area').style.display = 'none';
            showCategories();
            @if(env('GOOGLE_GA4_ID'))
            gtag('event', 'game_created', {game_type: 'truth_or_dare', game_code: GAME_CODE});
            @endif
        }
    });
}

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
            'Accept': 'application/json'
        },
        body: JSON.stringify({category: category})
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            var catNames = {truth:'真心話', dare:'大冒險', couple:'情侶題', party:'派對題'};
            document.getElementById('card-category').textContent = catNames[data.card.category] || data.card.category;
            document.getElementById('card-content').textContent = data.card.content;
            document.getElementById('card-tier').textContent = data.card.tier === 'premium' ? '🌟 Premium 題目' : '';
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
    fetch('/truth-dare/' + GAME_CODE + '/next', {
        method: 'POST',
        headers: {'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json'}
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
    fetch('/truth-dare/' + GAME_CODE + '/state')
    .then(r => r.json())
    .then(data => {
        // Update players
        var pa = document.getElementById('players-area');
        pa.innerHTML = '';
        data.players.forEach(function(p, i) {
            var div = document.createElement('div');
            div.className = 'td-player' + (data.game_state.current_player_index === i ? ' active' : '');
            div.textContent = p.player_name;
            if (p.is_host) div.innerHTML += ' <span style="color:var(--gold);font-size:.75rem">(房主)</span>';
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
            var wa = document.getElementById('waiting-area');
            if (wa) wa.style.display = 'none';
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
