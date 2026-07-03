@extends('layouts.app')
@section('title', __('minigame.card_title') . ' — ' . __('ui.site_name'))
@section('meta_description', __('minigame.card_meta'))
@section('canonical', route('card-game.show'))

@section('styles')
<link rel="stylesheet" href="{{ asset('css/minigames.css') }}">
<style>
/* Simultaneous Card Reveal */
.cg-deal-area{display:flex;gap:16px;justify-content:center;flex-wrap:wrap;padding:20px 0}
.cg-card-slot{display:flex;flex-direction:column;align-items:center;gap:8px;min-width:80px}
.cg-card-slot .slot-name{font-size:.85rem;color:var(--text-dim);font-weight:600}

/* 3D Card Scene — poker style */
.cg-card-scene{width:100px;height:140px;perspective:600px}
.cg-card-inner{position:relative;width:100%;height:100%;transition:transform .6s cubic-bezier(.4,0,.2,1);transform-style:preserve-3d}
.cg-card-inner.flipped{transform:rotateY(180deg)}
.cg-card-face{position:absolute;inset:0;backface-visibility:hidden;border-radius:10px}

/* Card back — red poker style */
.cg-card-back{
  background:#b91c1c;
  border:3px solid #fff;
  box-shadow:0 4px 14px rgba(0,0,0,.35);
  overflow:hidden;
}
.cg-card-back::before{
  content:'';position:absolute;inset:5px;border:1.5px solid rgba(255,255,255,.4);border-radius:5px;
  background:repeating-conic-gradient(#b91c1c 0% 25%,#991b1b 0% 50%) 50%/12px 12px;
}
.cg-card-back-icon{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;font-size:1.8rem;color:rgba(255,255,255,.3);z-index:1}

/* Card front — poker layout */
.cg-card-front{
  background:#fffdf7;border:2px solid #d4d0c8;
  box-shadow:0 2px 10px rgba(0,0,0,.22);transform:rotateY(180deg);
}
.cg-card-corner{position:absolute;display:flex;flex-direction:column;align-items:center;line-height:1}
.cg-card-corner-tl{top:6px;left:7px}
.cg-card-corner-br{bottom:6px;right:7px;transform:rotate(180deg)}
.cg-card-corner .corner-rank{font-size:.85rem;font-weight:800}
.cg-card-corner .corner-suit{font-size:.75rem;margin-top:-1px}
.cg-card-center{position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:2px}
.cg-card-center .center-suit{font-size:2.2rem}
.cg-card-center .center-rank{font-size:.9rem;font-weight:700;letter-spacing:.3px}
.cg-card-front.red .cg-card-corner{color:#c62828}
.cg-card-front.red .cg-card-center{color:#c62828}
.cg-card-front.black .cg-card-corner{color:#1a1a1a}
.cg-card-front.black .cg-card-center{color:#1a1a1a}
@media(min-width:600px){
  .cg-card-scene{width:110px;height:154px}
  .cg-card-corner .corner-rank{font-size:.95rem}
  .cg-card-corner .corner-suit{font-size:.85rem}
  .cg-card-center .center-suit{font-size:2.6rem}
  .cg-card-center .center-rank{font-size:1rem}
}

@keyframes cardDealIn{from{transform:scale(0) translateY(30px);opacity:0}to{transform:scale(1) translateY(0);opacity:1}}
.cg-card-scene.dealing{animation:cardDealIn .35s cubic-bezier(.34,1.56,.64,1) both}
@keyframes cardFlipBump{0%{filter:brightness(1) drop-shadow(0 0 0 transparent)}50%{filter:brightness(1.2) drop-shadow(0 0 12px rgba(212,160,23,.5))}100%{filter:brightness(1) drop-shadow(0 0 0 transparent)}}
.cg-card-scene.flip-bump{animation:cardFlipBump .8s ease-out}

/* Flip sparkle particles */
.cg-sparkle{position:absolute;width:4px;height:4px;border-radius:50%;pointer-events:none;z-index:20}
@keyframes sparkleOut{
    0%{opacity:1;transform:translate(0,0) scale(1)}
    100%{opacity:0;transform:translate(var(--sx),var(--sy)) scale(0)}
}
.cg-sparkle.animate{animation:sparkleOut .7s ease-out forwards}

.cg-card-placeholder{width:100px;height:140px;border:2px dashed var(--border);border-radius:10px;display:flex;align-items:center;justify-content:center;color:var(--text-dim);font-size:.8rem}
@media(min-width:600px){.cg-card-placeholder{width:110px;height:154px}}
@media(max-width:420px){
    .cg-card-scene{width:80px;height:112px}
    .cg-card-placeholder{width:80px;height:112px}
    .cg-card-corner .corner-rank{font-size:.7rem}
    .cg-card-corner .corner-suit{font-size:.6rem}
    .cg-card-center .center-suit{font-size:1.6rem}
    .cg-card-back-icon{font-size:1.4rem}
    .cg-deal-area{gap:10px}
    .cg-card-slot{min-width:70px}
}

/* Poker card small */
.poker-card-sm{width:48px;height:68px;background:#fff;border-radius:6px;border:1.5px solid #ddd;display:inline-flex;flex-direction:column;align-items:center;justify-content:center;font-weight:700;box-shadow:0 1px 4px rgba(0,0,0,.15);flex-shrink:0}
.poker-card-sm.red{color:#d00}.poker-card-sm.black{color:#111}
.poker-card-sm .rank{font-size:.75rem}.poker-card-sm .suit{font-size:1rem}

/* Inline results — layout is page-specific, tag/badge tokens shared via mg-* */
@keyframes fadeInUp{from{opacity:0;transform:translateY(16px);filter:blur(3px)}to{opacity:1;transform:none;filter:blur(0)}}
.cg-inline-results{animation:fadeInUp .5s cubic-bezier(.34,1.56,.64,1) both;margin-top:16px}
/* Shimmer sweep layered on top of the shared .mg-result-text */
.cg-activity-text{position:relative;overflow:hidden}
.cg-activity-text::after{
    content:'';position:absolute;top:0;left:-100%;width:50%;height:100%;
    background:linear-gradient(90deg,transparent,rgba(255,255,255,.1),transparent);
    animation:actShimmer 2.5s 1s ease-in-out infinite;
}
@keyframes actShimmer{0%{left:-100%}100%{left:200%}}
</style>
@endsection

@section('content')
<div class="mg-page mg-page--lg mg-page--center" id="mg-page-root">
    <h1 class="mg-title">{{ __('minigame.card_title') }}</h1>
    <p class="mg-subtitle">{{ __('minigame.card_subtitle_long') }}</p>

    {{-- Setup Phase --}}
    <div id="setup-phase" class="mg-setup">
        <h2 class="mg-setup-heading">{{ __('minigame.card_setup') }}</h2>
        <div id="players-list">
            <div class="mg-player-row" data-idx="0">
                <input type="text" class="form-control p-name" value="{{ __('minigame.player_default', ['n' => 1]) }}" maxlength="12">
                <select class="form-control p-gender">
                    <option value="male">{{ __('minigame.card_male') }}</option>
                    <option value="female">{{ __('minigame.card_female') }}</option>
                </select>
            </div>
            <div class="mg-player-row" data-idx="1">
                <input type="text" class="form-control p-name" value="{{ __('minigame.player_default', ['n' => 2]) }}" maxlength="12">
                <select class="form-control p-gender">
                    <option value="male">{{ __('minigame.card_male') }}</option>
                    <option value="female" selected>{{ __('minigame.card_female') }}</option>
                </select>
            </div>
        </div>
        <button class="btn btn-sm btn-outline mg-add-player" id="add-player-btn" onclick="addPlayer()">{{ __('minigame.add_player') }}</button>
        <button class="btn btn-gold btn-full" onclick="startGame()">{{ __('minigame.start_game') }}</button>
    </div>

    {{-- Drawing Phase --}}
    <div id="drawing-phase" style="display:none">
        <div class="mg-round-badge" id="round-badge"></div>
        <div class="cg-deal-area" id="deal-area"></div>
        <div id="inline-results"></div>
        <div class="mg-action-btns" id="action-btns">
            <button class="btn btn-gold btn-xl" id="deal-btn" onclick="dealCards()">{{ __('minigame.card_deal') }}</button>
            <button class="btn btn-gold btn-xl" id="flip-btn" style="display:none" onclick="flipAllCards()">{{ __('minigame.card_flip') }}</button>
            <button class="btn btn-gold btn-xl" id="next-round-btn" style="display:none" onclick="nextRound()">{{ __('minigame.card_next_round') }}</button>
            <button class="btn btn-outline" id="reset-btn" style="display:none" onclick="resetGame()">{{ __('minigame.reset_game') }}</button>
        </div>
        <div id="upgrade-notice" style="display:none;text-align:center;margin-top:12px">
            <p style="color:var(--gold);margin-bottom:8px">{{ __('minigame.card_premium_gate') }}</p>
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
    var ACTIVITIES = @json($activities);
    var SUITS = ['clubs','diamonds','hearts','spades'];
    var RANKS = ['2','3','4','5','6','7','8','9','10','J','Q','K','A'];
    var SUIT_SYMBOLS = {clubs:'\u2663',diamonds:'\u2666',hearts:'\u2665',spades:'\u2660'};

    var players = [];
    var round = 0;
    var usedCards = [];
    var cardsDealt = false;

    function escHtml(s){var d=document.createElement('div');d.appendChild(document.createTextNode(s));return d.innerHTML}
    function isRed(suit){return suit==='hearts'||suit==='diamonds'}
    function cardValue(card){return RANKS.indexOf(card.rank)*4+SUITS.indexOf(card.suit)}
    function makeCard(card,small){
        var cls=small?'poker-card-sm':'poker-card';
        var cc=isRed(card.suit)?'red':'black';
        return '<div class="'+cls+' '+cc+'"><div class="rank">'+card.rank+'</div><div class="suit">'+(SUIT_SYMBOLS[card.suit]||card.suit)+'</div></div>';
    }
    function pickCard(roundExclude){
        roundExclude=roundExclude||[];
        var avail=[];
        SUITS.forEach(function(s){RANKS.forEach(function(r){
            var key=s+'_'+r;
            if(usedCards.indexOf(key)===-1) avail.push({key:key,suit:s,rank:r});
        })});
        if(!avail.length){
            usedCards=roundExclude.slice();
            return pickCard(roundExclude);
        }
        var c=avail[Math.floor(Math.random()*avail.length)];
        usedCards.push(c.key);
        return c;
    }
    function getActivity(){
        var pool;
        if(round<=3) pool=ACTIVITIES.mild;
        else if(round<=6) pool=ACTIVITIES.medium;
        else pool=ACTIVITIES.intense||ACTIVITIES.medium;
        return pool[Math.floor(Math.random()*pool.length)];
    }
    function intensityTag(){
        var TIERS = {
            mild: @json(__('minigame.tier_mild')),
            medium: @json(__('minigame.tier_medium')),
            intense: @json(__('minigame.tier_intense')),
        };
        if(round<=3) return '<span class="mg-tag mg-tag-mild">'+TIERS.mild+'</span>';
        if(round<=6) return '<span class="mg-tag mg-tag-medium">'+TIERS.medium+'</span>';
        return '<span class="mg-tag mg-tag-intense">'+TIERS.intense+'</span>';
    }

    /* Setup */
    var playerCount=2;
    window.addPlayer=function(){
        if(playerCount>=6) return;
        playerCount++;
        var row=document.createElement('div');
        row.className='mg-player-row';
        row.setAttribute('data-idx',playerCount-1);
        var defaultName = @json(__('minigame.player_default', ['n' => '__N__'])).replace('__N__', playerCount);
        var optMale = @json(__('minigame.card_male'));
        var optFemale = @json(__('minigame.card_female'));
        row.innerHTML='<input type="text" class="form-control p-name" value="'+escHtml(defaultName)+'" maxlength="12">'+
            '<select class="form-control p-gender"><option value="male">'+optMale+'</option><option value="female">'+optFemale+'</option></select>'+
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
        var fallbackName=@json(__('minigame.player_default_short'));
        rows.forEach(function(r){
            var name=r.querySelector('.p-name').value.trim()||fallbackName;
            var gender=r.querySelector('.p-gender').value;
            players.push({name:name,gender:gender,card:null});
        });
        if(players.length<2){alert(@json(__('minigame.min_players_2')));return;}
        var hasMale=players.some(function(p){return p.gender==='male'});
        var hasFemale=players.some(function(p){return p.gender==='female'});
        if(!hasMale||!hasFemale){alert(@json(__('minigame.card_need_male_female')));return;}
        round=1;usedCards=[];
        var root=document.getElementById('mg-page-root');
        if(root) root.classList.remove('mg-page--center');
        startDrawingPhase();
    };

    function startDrawingPhase(){
        document.getElementById('setup-phase').style.display='none';
        document.getElementById('drawing-phase').style.display='block';
        cardsDealt=false;
        var roundLabel = @json(__('minigame.card_round_n', ['n' => '__N__'])).replace('__N__', round);
        document.getElementById('round-badge').innerHTML=escHtml(roundLabel)+' '+intensityTag();
        document.getElementById('deal-btn').style.display='inline-flex';
        document.getElementById('flip-btn').style.display='none';
        document.getElementById('next-round-btn').style.display='none';
        document.getElementById('reset-btn').style.display='none';
        document.getElementById('upgrade-notice').style.display='none';
        document.getElementById('inline-results').innerHTML='';

        var area=document.getElementById('deal-area');
        area.innerHTML='';
        players.forEach(function(p,i){
            var gClass=p.gender==='male'?'mg-gender-male':'mg-gender-female';
            var gLabel=p.gender==='male'?@json(__('minigame.card_male')):@json(__('minigame.card_female'));
            var waiting=@json(__('minigame.card_waiting_deal'));
            var slot=document.createElement('div');
            slot.className='cg-card-slot';
            slot.id='card-slot-'+i;
            slot.innerHTML=
                '<div class="slot-name">'+escHtml(p.name)+'</div>'+
                '<span class="mg-gender-tag '+gClass+'">'+escHtml(gLabel)+'</span>'+
                '<div class="cg-card-placeholder">'+escHtml(waiting)+'</div>';
            area.appendChild(slot);
        });
    }

    window.dealCards=function(){
        if(cardsDealt) return;
        cardsDealt=true;
        document.getElementById('deal-btn').style.display='none';

        var roundKeys=[];
        players.forEach(function(p,i){
            p.card=pickCard(roundKeys);
            roundKeys.push(p.card.key);
            var slot=document.getElementById('card-slot-'+i);
            var placeholder=slot.querySelector('.cg-card-placeholder');
            if(placeholder) placeholder.remove();

            var cc=isRed(p.card.suit)?'red':'black';
            var sym=SUIT_SYMBOLS[p.card.suit]||p.card.suit;
            var frontHtml=
                '<div class="cg-card-corner cg-card-corner-tl"><span class="corner-rank">'+p.card.rank+'</span><span class="corner-suit">'+sym+'</span></div>'+
                '<div class="cg-card-corner cg-card-corner-br"><span class="corner-rank">'+p.card.rank+'</span><span class="corner-suit">'+sym+'</span></div>'+
                '<div class="cg-card-center"><span class="center-suit">'+sym+'</span><span class="center-rank">'+p.card.rank+'</span></div>';
            var scene=document.createElement('div');
            scene.className='cg-card-scene dealing';
            scene.id='card-scene-'+i;
            scene.style.animationDelay=(i*100)+'ms';
            scene.innerHTML=
                '<div class="cg-card-inner" id="card-inner-'+i+'">'+
                '<div class="cg-card-face cg-card-back"><div class="cg-card-back-icon">\u2660</div></div>'+
                '<div class="cg-card-face cg-card-front '+cc+'">'+frontHtml+'</div>'+
                '</div>';
            slot.appendChild(scene);
        });

        var totalDealTime=players.length*100+350;
        setTimeout(function(){
            document.getElementById('flip-btn').style.display='inline-flex';
        },totalDealTime);
    };

    function spawnSparkles(scene){
        if(!scene) return;
        var rect=scene.getBoundingClientRect();
        var cx=rect.width/2,cy=rect.height/2;
        var colors=['#ffd700','#ff6b6b','#a78bfa','#60a5fa','#f472b6'];
        for(var s=0;s<10;s++){
            var spark=document.createElement('div');
            spark.className='cg-sparkle';
            var angle=Math.random()*Math.PI*2;
            var dist=40+Math.random()*40;
            spark.style.cssText='left:'+cx+'px;top:'+cy+'px;background:'+colors[s%colors.length]+
                ';--sx:'+(Math.cos(angle)*dist)+'px;--sy:'+(Math.sin(angle)*dist)+'px';
            scene.appendChild(spark);
            void spark.offsetWidth;
            spark.classList.add('animate');
            (function(el){setTimeout(function(){el.remove()},800)})(spark);
        }
    }

    window.flipAllCards=function(){
        document.getElementById('flip-btn').style.display='none';

        players.forEach(function(p,i){
            setTimeout(function(){
                var inner=document.getElementById('card-inner-'+i);
                var scene=document.getElementById('card-scene-'+i);
                if(inner) inner.classList.add('flipped');
                if(scene){scene.classList.add('flip-bump');spawnSparkles(scene);}
            },i*400);
        });

        // After all flipped, show pairings inline + next round button
        var totalFlipTime=(players.length-1)*400+600+800;
        setTimeout(function(){
            showInlineResults();
        },totalFlipTime);
    };

    function showInlineResults(){
        var males=[],females=[];
        players.forEach(function(p,i){
            if(!p.card) return;
            var entry={name:p.name,card:p.card,value:cardValue(p.card),idx:i};
            if(p.gender==='male') males.push(entry); else females.push(entry);
        });
        males.sort(function(a,b){return b.value-a.value});
        females.sort(function(a,b){return b.value-a.value});

        var pairCount=Math.min(males.length,females.length);
        var html='<div class="cg-inline-results"><div class="mg-result-card">';

        for(var i=0;i<pairCount;i++){
            var m=males[i],f=females[females.length-1-i];
            var activity=getActivity();
            var bigName,smallName;
            if(m.value>=f.value){bigName=m.name;smallName=f.name}
            else{bigName=f.name;smallName=m.name}
            activity=activity.replace(/牌大的/g,bigName).replace(/牌小的/g,smallName);
            html+='<div class="mg-result-item">'+
                '<div class="mg-result-text cg-activity-text">'+escHtml(activity)+'</div></div>';
        }

        var unpairedM=males.slice(pairCount);
        var unpairedF=females.slice(0,Math.max(0,females.length-pairCount));
        var allUnpaired=unpairedM.concat(unpairedF);
        if(allUnpaired.length){
            var restTpl = @json(__('minigame.card_resting', ['names' => '__NAMES__']));
            var nameList = allUnpaired.map(function(x){return escHtml(x.name)}).join(@json(__('minigame.name_separator')));
            html+='<div class="mg-result-item"><div style="color:var(--text-dim);font-size:.9rem">'+restTpl.replace('__NAMES__', nameList)+'</div></div>';
        }

        html+='</div></div>';
        document.getElementById('inline-results').innerHTML=html;

        // Show buttons
        document.getElementById('reset-btn').style.display='inline-flex';
        if(round>=6&&!IS_PREMIUM){
            document.getElementById('upgrade-notice').style.display='block';
        } else {
            document.getElementById('next-round-btn').style.display='inline-flex';
        }
    }

    window.nextRound=function(){
        round++;
        players.forEach(function(p){p.card=null});
        startDrawingPhase();
    };
    window.resetGame=function(){
        document.getElementById('drawing-phase').style.display='none';
        round=0;usedCards=[];
        document.getElementById('setup-phase').style.display='block';
        var root=document.getElementById('mg-page-root');
        if(root) root.classList.add('mg-page--center');
    };
})();
</script>
@endsection
