@extends('layouts.app')
@section('title', __('minigame.king_title') . ' — ' . __('ui.site_name'))
@section('meta_description', __('minigame.king_meta'))
@section('canonical', route('king-game.show'))

@section('styles')
<link rel="stylesheet" href="{{ asset('css/minigames.css') }}">
<style>
/* Cards — poker style, bigger */
.kg-card-area{display:flex;gap:16px;justify-content:center;flex-wrap:wrap;padding:20px 0}
.kg-card-slot{text-align:center}
.kg-card-slot .slot-name{font-size:.85rem;color:var(--text-dim);font-weight:600;margin-bottom:6px}
.kg-card-scene{width:100px;height:140px;perspective:600px;margin:0 auto;cursor:pointer}
.kg-card-inner{position:relative;width:100%;height:100%;transition:transform .6s cubic-bezier(.4,0,.2,1);transform-style:preserve-3d}
.kg-card-inner.flipped{transform:rotateY(180deg)}
.kg-card-face{position:absolute;inset:0;backface-visibility:hidden;border-radius:10px}

/* Card back */
.kg-card-back{
  background:#b91c1c;
  border:3px solid #fff;
  box-shadow:0 4px 14px rgba(0,0,0,.35);
  overflow:hidden;
}
.kg-card-back::before{
  content:'';position:absolute;inset:5px;border:1.5px solid rgba(255,255,255,.4);border-radius:5px;
  background:repeating-conic-gradient(#b91c1c 0% 25%,#991b1b 0% 50%) 50%/12px 12px;
}
.kg-card-back-icon{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;font-size:1.8rem;color:rgba(255,255,255,.3);z-index:1}

/* Card front — poker layout */
.kg-card-front{
  background:#fffdf7;border:2px solid #d4d0c8;
  box-shadow:0 2px 10px rgba(0,0,0,.22);transform:rotateY(180deg);
}
.kg-card-corner{position:absolute;display:flex;flex-direction:column;align-items:center;line-height:1}
.kg-card-corner-tl{top:6px;left:7px}
.kg-card-corner-br{bottom:6px;right:7px;transform:rotate(180deg)}
.kg-card-corner .corner-rank{font-size:.85rem;font-weight:800}
.kg-card-corner .corner-suit{font-size:.75rem;margin-top:-1px}
.kg-card-center{position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:2px}
.kg-card-center .center-suit{font-size:2.2rem}
.kg-card-center .center-label{font-size:.8rem;font-weight:700;letter-spacing:.3px}

/* King card */
.kg-card-front.king{background:linear-gradient(175deg,#fffdf0 30%,#fef3c7 100%);border-color:#d97706}
.kg-card-front.king .kg-card-corner{color:#d97706}
.kg-card-front.king .center-suit{font-size:2.6rem}
.kg-card-front.king .center-label{color:#92400e;font-size:.85rem}

/* Number card — black suits */
.kg-card-front.number .kg-card-corner{color:#1e3a5f}
.kg-card-front.number .center-suit{color:#1e3a5f}
/* Number card — red suits */
.kg-card-front.number.red-suit .kg-card-corner{color:#b91c1c}
.kg-card-front.number.red-suit .center-suit{color:#b91c1c}

@media(min-width:500px){
  .kg-card-scene{width:110px;height:154px}
  .kg-card-corner .corner-rank{font-size:.95rem}
  .kg-card-corner .corner-suit{font-size:.85rem}
  .kg-card-center .center-suit{font-size:2.6rem}
  .kg-card-center .center-label{font-size:.9rem}
}

@keyframes cardDealIn{from{transform:scale(0);opacity:0}to{transform:scale(1);opacity:1}}
.kg-card-scene.dealing{animation:cardDealIn .3s cubic-bezier(.34,1.56,.64,1) both}
</style>
@endsection

@section('content')
<div class="mg-page mg-page--md">
    <h1 class="mg-title">{{ __('minigame.king_title') }}</h1>
    <p class="mg-subtitle">{{ __('minigame.king_subtitle_long') }}</p>

    {{-- Setup Phase --}}
    <div id="setup-phase" class="mg-setup">
        <h2 class="mg-setup-heading">{{ __('minigame.king_setup') }}</h2>
        <div id="players-list">
            <div class="mg-player-row" data-idx="0">
                <input type="text" class="form-control p-name" value="{{ __('minigame.player_default', ['n' => 1]) }}" maxlength="12">
            </div>
            <div class="mg-player-row" data-idx="1">
                <input type="text" class="form-control p-name" value="{{ __('minigame.player_default', ['n' => 2]) }}" maxlength="12">
            </div>
            <div class="mg-player-row" data-idx="2">
                <input type="text" class="form-control p-name" value="{{ __('minigame.player_default', ['n' => 3]) }}" maxlength="12">
            </div>
        </div>
        <button class="btn btn-sm btn-outline mg-add-player" id="add-player-btn" onclick="addPlayer()">{{ __('minigame.add_player') }}</button>
        <button class="btn btn-gold btn-full" onclick="startGame()">{{ __('minigame.start_game') }}</button>
    </div>

    {{-- Deal Phase --}}
    <div id="deal-phase" style="display:none">
        <div class="mg-round-badge" id="round-badge"></div>
        <p style="text-align:center;color:var(--text-dim);font-size:.9rem;margin-bottom:12px">{{ __('minigame.king_peek_tip') }}</p>
        <div class="kg-card-area" id="card-area"></div>
        <div class="mg-action-btns">
            <button class="btn btn-gold btn-xl" id="next-round-btn" style="display:none" onclick="nextRound()">{{ __('minigame.next_turn') }}</button>
            <button class="btn btn-outline" id="reset-btn" style="display:none" onclick="resetGame()">{{ __('minigame.reset_game') }}</button>
        </div>
        <div id="upgrade-notice" style="display:none;text-align:center;margin-top:12px">
            <p style="color:var(--gold);margin-bottom:8px">{{ __('minigame.king_premium_gate') }}</p>
            <a href="{{ route('premium.index') }}" class="btn btn-outline-gold">{{ __('minigame.go_premium') }}</a>
        </div>
    </div>
</div>

@include('partials.ad-unit', ['zone' => 'lobby_side'])
@endsection

@section('scripts')
<script>
(function(){
    var IS_PREMIUM = {{ $isPremium ? 'true' : 'false' }};
    var players=[];
    var round=0;
    var assignments=[];
    var peeked=[];

    function escHtml(s){var d=document.createElement('div');d.appendChild(document.createTextNode(s));return d.innerHTML}
    function shuffle(a){for(var i=a.length-1;i>0;i--){var j=Math.floor(Math.random()*(i+1));var t=a[i];a[i]=a[j];a[j]=t}return a}
    function showToast(msg){
        var old=document.querySelector('.mg-toast');
        if(old) old.remove();
        var t=document.createElement('div');
        t.className='mg-toast';
        t.textContent=msg;
        document.body.appendChild(t);
        setTimeout(function(){t.remove()},3200);
    }

    /* Setup */
    var playerCount=3;
    window.addPlayer=function(){
        if(playerCount>=6) return;
        playerCount++;
        var row=document.createElement('div');
        row.className='mg-player-row';
        var defaultName = @json(__('minigame.player_default', ['n' => '__N__'])).replace('__N__', playerCount);
        row.innerHTML='<input type="text" class="form-control p-name" value="'+escHtml(defaultName)+'" maxlength="12">'+
            '<button class="mg-player-remove" onclick="removePlayer(this)">✕</button>';
        document.getElementById('players-list').appendChild(row);
        if(playerCount>=6) document.getElementById('add-player-btn').style.display='none';
    };
    window.removePlayer=function(btn){
        btn.closest('.mg-player-row').remove();
        playerCount--;
        document.getElementById('add-player-btn').style.display='inline-block';
    };

    window.startGame=function(){
        var rows=document.querySelectorAll('.mg-player-row');
        players=[];
        var fallbackName = @json(__('minigame.player_default_short'));
        rows.forEach(function(r){
            players.push(r.querySelector('.p-name').value.trim()||fallbackName);
        });
        if(players.length<3){showToast(@json(__('minigame.king_min_players')));return;}
        round=1;
        dealRound();
    };

    function buildDeck(count){
        // Build a mini poker deck: 1 King + (count-1) number cards, each with unique rank+suit
        var allSuits=['♠','♥','♦','♣'];
        var allRanks=['A','2','3','4','5','6','7','8','9','10','J','Q'];
        var redSuits={'♥':true,'♦':true};
        // Generate enough unique rank+suit combos for non-king players
        var pool=[];
        for(var r=0;r<allRanks.length;r++){
            for(var s=0;s<allSuits.length;s++){
                pool.push({rank:allRanks[r],suit:allSuits[s],isRed:!!redSuits[allSuits[s]]});
            }
        }
        shuffle(pool);
        // Pick (count-1) cards for non-king, plus 1 King with random suit
        var kingSuit=allSuits[Math.floor(Math.random()*4)];
        var cards=[{role:'king',rank:'K',suit:kingSuit,isRed:!!redSuits[kingSuit],label:@json(__('minigame.king_role_king'))}];
        for(var i=0;i<count-1;i++){
            var c=pool[i];
            cards.push({role:'number',rank:c.rank,suit:c.suit,isRed:c.isRed,label:c.rank+c.suit});
        }
        return shuffle(cards);
    }

    function dealRound(){
        document.getElementById('setup-phase').style.display='none';
        document.getElementById('deal-phase').style.display='block';
        var roundLabel = @json(__('minigame.king_round_n', ['n' => '__N__'])).replace('__N__', round);
        document.getElementById('round-badge').innerHTML=escHtml(roundLabel);
        document.getElementById('next-round-btn').style.display='none';
        document.getElementById('reset-btn').style.display='none';
        document.getElementById('upgrade-notice').style.display='none';
        peeked=[];

        var deck=buildDeck(players.length);

        assignments=[];
        for(var i=0;i<players.length;i++){
            assignments.push({name:players[i],role:deck[i].role,rank:deck[i].rank,suit:deck[i].suit,isRed:deck[i].isRed,label:deck[i].label});
        }

        var area=document.getElementById('card-area');
        area.innerHTML='';
        players.forEach(function(name,i){
            var a=assignments[i];
            var isKing=a.role==='king';
            var cls=isKing?'king':'number'+(a.isRed?' red-suit':'');

            var frontHtml=
                '<div class="kg-card-corner kg-card-corner-tl"><span class="corner-rank">'+a.rank+'</span><span class="corner-suit">'+a.suit+'</span></div>'+
                '<div class="kg-card-corner kg-card-corner-br"><span class="corner-rank">'+a.rank+'</span><span class="corner-suit">'+a.suit+'</span></div>'+
                '<div class="kg-card-center"><span class="center-suit">'+a.suit+'</span>'+
                (isKing?'<span class="center-label">'+escHtml(@json(__('minigame.king_role_king')))+'</span>':'<span class="center-label">'+a.rank+' '+a.suit+'</span>')+'</div>';

            var backSuit=['♠','♥','♦','♣'][i%4];
            var slot=document.createElement('div');
            slot.className='kg-card-slot';
            slot.innerHTML='<div class="slot-name">'+escHtml(name)+'</div>'+
                '<div class="kg-card-scene dealing" style="animation-delay:'+(i*100)+'ms" onclick="peekCard('+i+')">'+
                '<div class="kg-card-inner" id="king-card-'+i+'">'+
                '<div class="kg-card-face kg-card-back"><div class="kg-card-back-icon">'+backSuit+'</div></div>'+
                '<div class="kg-card-face kg-card-front '+cls+'">'+frontHtml+'</div>'+
                '</div></div>';
            area.appendChild(slot);
        });
    }

    window.peekCard=function(idx){
        var inner=document.getElementById('king-card-'+idx);
        if(!inner) return;
        if(inner.classList.contains('flipped')){
            inner.classList.remove('flipped');
        } else {
            inner.classList.add('flipped');
            if(peeked.indexOf(idx)===-1) peeked.push(idx);
            if(peeked.length>=players.length){
                // All peeked — show next round button directly
                document.getElementById('reset-btn').style.display='inline-flex';
                if(round>=6&&!IS_PREMIUM){
                    document.getElementById('upgrade-notice').style.display='block';
                } else {
                    document.getElementById('next-round-btn').style.display='inline-flex';
                }
            }
        }
    };

    window.nextRound=function(){
        round++;
        dealRound();
    };
    window.resetGame=function(){
        document.getElementById('deal-phase').style.display='none';
        round=0;
        document.getElementById('setup-phase').style.display='block';
    };
})();
</script>
@endsection
