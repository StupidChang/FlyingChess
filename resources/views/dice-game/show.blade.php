@extends('layouts.app')
@section('title', __('minigame.dice_title') . ' — ' . __('ui.site_name'))
@section('meta_description', __('minigame.dice_meta'))
@section('canonical', route('dice-game.show'))

@section('styles')
<link rel="stylesheet" href="{{ asset('css/minigames.css') }}">
<style>
/* 3D Dice */
.dg-dice-area{display:flex;gap:24px;justify-content:center;flex-wrap:wrap;padding:30px 0}
.dg-dice-wrapper{text-align:center}
.dg-dice-label{font-size:.8rem;color:var(--text-dim);margin-bottom:8px;font-weight:600}
.dg-dice-scene{width:80px;height:80px;perspective:300px;margin:0 auto;filter:drop-shadow(0 2px 4px rgba(0,0,0,.3))}
.dg-dice{width:100%;height:100%;position:relative;transform-style:preserve-3d;transform:rotateX(-20deg) rotateY(25deg)}
.dg-dice-face{position:absolute;width:80px;height:80px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:.85rem;font-weight:700;color:#fff;border:2px solid rgba(255,255,255,.2);backface-visibility:hidden;text-align:center;line-height:1.2;padding:4px}
.dg-dice-face.f1{background:linear-gradient(135deg,#e53935,#c62828);transform:rotateY(0deg) translateZ(40px)}
.dg-dice-face.f2{background:linear-gradient(135deg,#e53935,#c62828);transform:rotateY(180deg) translateZ(40px)}
.dg-dice-face.f3{background:linear-gradient(135deg,#e53935,#c62828);transform:rotateY(90deg) translateZ(40px)}
.dg-dice-face.f4{background:linear-gradient(135deg,#e53935,#c62828);transform:rotateY(-90deg) translateZ(40px)}
.dg-dice-face.f5{background:linear-gradient(135deg,#e53935,#c62828);transform:rotateX(90deg) translateZ(40px)}
.dg-dice-face.f6{background:linear-gradient(135deg,#e53935,#c62828);transform:rotateX(-90deg) translateZ(40px)}
.dg-dice-wrapper:nth-child(2) .dg-dice-face{background:linear-gradient(135deg,#2563eb,#1d4ed8)}
.dg-dice-wrapper:nth-child(3) .dg-dice-face{background:linear-gradient(135deg,#7c3aed,#6d28d9)}

/* Result glow — one-shot pulse on the settled dice */
.dg-dice-scene.dg-glow{animation:dgGlowPulse .8s ease-out 1}
@keyframes dgGlowPulse{
  0%{filter:drop-shadow(0 2px 4px rgba(0,0,0,.3))}
  35%{filter:drop-shadow(0 2px 4px rgba(0,0,0,.3)) drop-shadow(0 0 16px rgba(255,205,90,.9))}
  100%{filter:drop-shadow(0 2px 4px rgba(0,0,0,.3))}
}
@media (prefers-reduced-motion: reduce){
  .dg-dice-scene.dg-glow{animation:none}
}

/* Result */
.dg-result{text-align:center;padding:20px;animation:fadeIn .3s ease-out}
@keyframes fadeIn{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:none}}

/* Buttons visually disabled while dice are rolling (roll-btn is fully
   hidden already; this covers the still-visible reset button) */
.mg-action-btns.dg-rolling .btn-outline{opacity:.45;pointer-events:none;filter:grayscale(.4)}

/* Roll history — small chip trail, newest on top */
.dg-history{display:flex;flex-direction:column;gap:6px;margin-top:18px;max-width:420px;margin-left:auto;margin-right:auto}
.dg-history-item{
    display:flex;align-items:center;gap:8px;padding:6px 12px;font-size:.78rem;
    background:var(--surface,#151823);border:1px solid var(--border,#2a2f42);border-radius:8px;
    color:var(--text-dim,#9aa1b5);animation:dgHistoryIn .35s cubic-bezier(.34,1.56,.64,1) both;
}
.dg-history-round{
    flex-shrink:0;width:20px;height:20px;border-radius:50%;background:var(--gold,#d9a441);color:#241a04;
    font-weight:700;font-size:.68rem;display:flex;align-items:center;justify-content:center;
}
.dg-history-text{overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
@keyframes dgHistoryIn{from{opacity:0;transform:translateX(-12px)}to{opacity:1;transform:translateX(0)}}
@media (prefers-reduced-motion: reduce){
    .dg-history-item{animation:none}
}
</style>
@endsection

@section('content')
<div class="mg-page mg-page--md mg-page--center" id="mg-page-root">
    <h1 class="mg-title">{{ __('minigame.dice_title') }}</h1>
    <p class="mg-subtitle">{{ __('minigame.dice_subtitle') }}</p>

    {{-- Setup Phase --}}
    <div id="setup-phase" class="mg-setup">
        <h2 class="mg-setup-heading">{{ __('minigame.players_setup') }}</h2>
        <div id="players-list">
            <div class="mg-player-row" data-idx="0">
                <input type="text" class="form-control p-name" value="{{ __('minigame.player_default', ['n' => 1]) }}" maxlength="12">
            </div>
            <div class="mg-player-row" data-idx="1">
                <input type="text" class="form-control p-name" value="{{ __('minigame.player_default', ['n' => 2]) }}" maxlength="12">
            </div>
        </div>
        <button class="btn btn-sm btn-outline mg-add-player" id="add-player-btn" onclick="addPlayer()">{{ __('minigame.add_player') }}</button>
        <button class="btn btn-gold btn-full" onclick="startGame()">{{ __('minigame.start_game') }}</button>
    </div>

    {{-- Game Phase --}}
    <div id="game-phase" style="display:none">
        <div class="mg-round-badge" id="turn-badge"></div>
        <div class="mg-current-player" id="current-player"></div>
        <div class="dg-dice-area" id="dice-area"></div>
        <div id="result-display" class="dg-result" style="display:none"></div>
        <div class="dg-history" id="roll-history"></div>
        <div class="mg-action-btns">
            <button class="btn btn-gold btn-xl" id="roll-btn" onclick="rollDice()">{{ __('minigame.dice_roll') }}</button>
            <button class="btn btn-gold btn-xl" id="next-btn" style="display:none" onclick="nextTurn()">{{ __('minigame.next_turn') }}</button>
            <button class="btn btn-outline" onclick="resetGame()">{{ __('minigame.reset_game') }}</button>
        </div>
    </div>
</div>

@include('partials.ad-unit', ['zone' => 'lobby_side'])
@endsection

@section('scripts')
<script>
(function(){
    var IS_PREMIUM = {{ $isPremium ? 'true' : 'false' }};
    var POOLS = @json($dicePools);
    var players = [];
    var turn = 0;
    var round = 0;
    var rollAnimId = null;
    var HISTORY_MAX = 5;

    function addHistory(text){
        var list = document.getElementById('roll-history');
        if(!list) return;
        var item = document.createElement('div');
        item.className = 'dg-history-item';
        item.innerHTML = '<span class="dg-history-round">'+round+'</span><span class="dg-history-text"></span>';
        item.querySelector('.dg-history-text').textContent = text;
        list.insertBefore(item, list.firstChild);
        while(list.children.length > HISTORY_MAX){
            list.removeChild(list.lastChild);
        }
    }
    function clearHistory(){
        var list = document.getElementById('roll-history');
        if(list) list.innerHTML = '';
    }

    function escHtml(s){var d=document.createElement('div');d.appendChild(document.createTextNode(s));return d.innerHTML}

    function showToast(msg){
        var old=document.querySelector('.mg-toast');
        if(old) old.remove();
        var t=document.createElement('div');
        t.className='mg-toast';
        t.textContent=msg;
        document.body.appendChild(t);
        setTimeout(function(){t.remove()},3200);
    }

    function getTier(){
        if(round<=3) return 'mild';
        if(round<=6) return 'medium';
        return (POOLS.intense ? 'intense' : 'medium');
    }
    var TIER_LABELS={
        mild: @json(__('minigame.tier_mild')),
        medium: @json(__('minigame.tier_medium')),
        intense: @json(__('minigame.tier_intense')),
    };
    function intensityTag(){
        var t=getTier();
        return '<span class="mg-tag mg-tag-'+t+'">'+escHtml(TIER_LABELS[t])+'</span>';
    }
    function pick(arr){return arr[Math.floor(Math.random()*arr.length)]}

    /* Setup */
    var playerCount=2;
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
        if(players.length<2){showToast(@json(__('minigame.min_players_2')));return;}
        turn=0;round=1;
        clearHistory();
        showTurn();
    };

    function showTurn(){
        document.getElementById('setup-phase').style.display='none';
        document.getElementById('game-phase').style.display='block';
        var roundLabel = @json(__('minigame.round_n', ['n' => '__N__'])).replace('__N__', round);
        document.getElementById('turn-badge').innerHTML=escHtml(roundLabel)+' '+intensityTag();
        var turnLabel = @json(__('minigame.turn_player', ['name' => '__NAME__'])).replace('__NAME__', players[turn]);
        document.getElementById('current-player').textContent=turnLabel;
        document.getElementById('roll-btn').style.display='inline-flex';
        document.getElementById('next-btn').style.display='none';
        document.getElementById('result-display').style.display='none';

        // Build 3D dice
        var tier=getTier();
        var pool=POOLS[tier];
        var area=document.getElementById('dice-area');
        area.innerHTML='';
        var diceData=[
            {label:@json(__('minigame.dice_label_action')),values:pool.actions},
            {label:@json(__('minigame.dice_label_part')),values:pool.parts},
            {label:@json(__('minigame.dice_label_time')),values:pool.durations}
        ];
        diceData.forEach(function(dd,di){
            var w=document.createElement('div');
            w.className='dg-dice-wrapper';
            var facesHtml='';
            var vals=dd.values.slice(0,6);
            for(var fi=0;fi<vals.length;fi++){
                facesHtml+='<div class="dg-dice-face f'+(fi+1)+'">'+escHtml(vals[fi])+'</div>';
            }
            w.innerHTML='<div class="dg-dice-label">'+dd.label+'</div>'+
                '<div class="dg-dice-scene"><div class="dg-dice" id="dice-'+di+'">'+ facesHtml +'</div></div>';
            area.appendChild(w);
        });
    }

    window.rollDice=function(){
        document.getElementById('roll-btn').style.display='none';
        var actionBtns=document.querySelector('.mg-action-btns');
        if(actionBtns) actionBtns.classList.add('dg-rolling');
        var tier=getTier();
        var pool=POOLS[tier];

        var actionIdx=Math.floor(Math.random()*Math.min(pool.actions.length,6));
        var partIdx=Math.floor(Math.random()*Math.min(pool.parts.length,6));
        var durIdx=Math.floor(Math.random()*Math.min(pool.durations.length,6));

        var action=pool.actions[actionIdx];
        var part=pool.parts[partIdx];
        var duration=pool.durations[durIdx];

        // Face index → rotation to show that face toward camera
        var faceRot=[
            {rx:0,ry:0},{rx:0,ry:180},{rx:0,ry:-90},{rx:0,ry:90},{rx:-90,ry:0},{rx:90,ry:0}
        ];
        var indices=[actionIdx,partIdx,durIdx];

        // Build animation params for each dice
        var ANIM_DUR=1800;
        var startTime=null;
        var diceParams=[];
        for(var i=0;i<3;i++){
            var el=document.getElementById('dice-'+i);
            if(!el) continue;
            var fr=faceRot[indices[i]%6];
            // Normalize target to positive for clean multi-spin math
            var tRx=fr.rx; while(tRx<0) tRx+=360;
            var tRy=fr.ry; while(tRy<0) tRy+=360;
            var extraX=3+Math.floor(Math.random()*2);
            var extraY=2+Math.floor(Math.random()*2);
            // Randomize spin direction per axis so every roll takes a visually
            // different path (still lands on the correct face — direction
            // doesn't affect the final rotateX/rotateY value once normalized).
            var signX=Math.random()<0.5?1:-1;
            var signY=Math.random()<0.5?1:-1;
            diceParams.push({
                el:el,
                sRx:-20, sRy:25,
                eRx:tRx+signX*extraX*360, eRy:tRy+signY*extraY*360,
                fRx:fr.rx, fRy:fr.ry,
                wAmp:12+Math.random()*18, wFreq:3+Math.random()*2,
                delay:i*70
            });
        }

        function easeOutExpo(t){return t===1?1:1-Math.pow(2,-10*t)}

        // Result text appears the moment the dice settle (start of spring bounce)
        function showResult(){
            var rd=document.getElementById('result-display');
            rd.style.display='block';
            rd.innerHTML='<div class="mg-result-text">'+escHtml(action)+' '+escHtml(part)+' '+escHtml(duration)+'</div>';
            document.getElementById('next-btn').style.display='inline-flex';
            addHistory(action+' '+part+' '+duration);
            for(var g=0;g<diceParams.length;g++){
                (function(scene){
                    scene.classList.remove('dg-glow');
                    void scene.offsetWidth; // restart one-shot glow animation
                    scene.classList.add('dg-glow');
                    setTimeout(function(){scene.classList.remove('dg-glow')},850);
                })(diceParams[g].el.parentElement);
            }
        }

        // Spring settle: settled face overshoots a few degrees, then bounces back
        // to 0 — a subtle squash/stretch scale pulse rides along to sell the
        // sense of the die "thudding" onto the table.
        function springSettle(){
            var SPR=450, st=null;
            function sTick(now){
                if(st===null) st=now;
                var t=Math.min((now-st)/SPR,1);
                var delta=9*Math.sin(t*Math.PI*2)*(1-t);
                var bounce=1+0.05*Math.sin(t*Math.PI*2)*(1-t);
                for(var k=0;k<diceParams.length;k++){
                    var d=diceParams[k];
                    d.el.style.transform='rotateX('+(d.fRx+delta).toFixed(1)+'deg) rotateY('+(d.fRy+delta*0.6).toFixed(1)+'deg) scale3d('+bounce.toFixed(3)+','+bounce.toFixed(3)+','+bounce.toFixed(3)+')';
                }
                if(t<1){
                    rollAnimId=requestAnimationFrame(sTick);
                } else {
                    rollAnimId=null;
                    for(var m=0;m<diceParams.length;m++){
                        diceParams[m].el.style.transform='rotateX('+diceParams[m].fRx+'deg) rotateY('+diceParams[m].fRy+'deg)';
                    }
                }
            }
            rollAnimId=requestAnimationFrame(sTick);
        }

        // Reduced motion: skip animation, show the result faces directly
        var REDUCED=window.matchMedia&&window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        if(REDUCED){
            for(var q=0;q<diceParams.length;q++){
                diceParams[q].el.style.transform='rotateX('+diceParams[q].fRx+'deg) rotateY('+diceParams[q].fRy+'deg)';
            }
            if(actionBtns) actionBtns.classList.remove('dg-rolling');
            showResult();
            return;
        }

        function tick(now){
            if(!startTime) startTime=now;
            var allDone=true;
            for(var j=0;j<diceParams.length;j++){
                var d=diceParams[j];
                var elapsed=now-startTime-d.delay;
                if(elapsed<0){allDone=false;continue;}
                var t=Math.min(elapsed/ANIM_DUR,1);
                var e=easeOutExpo(t);
                var rx=d.sRx+(d.eRx-d.sRx)*e;
                var ry=d.sRy+(d.eRy-d.sRy)*e;
                var rz=d.wAmp*(1-e)*Math.sin(e*d.wFreq*Math.PI*2);
                var sc=1+0.06*Math.sin(t*Math.PI);
                var lift=Math.sin(t*Math.PI);
                var hop=-(lift*10); // small vertical hop, in sync with the shadow below
                d.el.style.transform='translateY('+hop.toFixed(1)+'px) rotateX('+rx.toFixed(1)+'deg) rotateY('+ry.toFixed(1)+'deg) rotateZ('+rz.toFixed(1)+'deg) scale3d('+sc.toFixed(3)+','+sc.toFixed(3)+','+sc.toFixed(3)+')';
                d.el.parentElement.style.filter='drop-shadow(0 '+(2+lift*12).toFixed(0)+'px '+(4+lift*16).toFixed(0)+'px rgba(0,0,0,'+(0.25+lift*0.3).toFixed(2)+'))';
                if(t<1) allDone=false;
            }
            if(!allDone){
                rollAnimId=requestAnimationFrame(tick);
            } else {
                rollAnimId=null;
                for(var k=0;k<diceParams.length;k++){
                    diceParams[k].el.style.transform='rotateX('+diceParams[k].fRx+'deg) rotateY('+diceParams[k].fRy+'deg)';
                    diceParams[k].el.parentElement.style.filter='drop-shadow(0 2px 4px rgba(0,0,0,.3))';
                }
                if(actionBtns) actionBtns.classList.remove('dg-rolling');
                if(navigator.vibrate) navigator.vibrate(30);
                showResult();    // result text synced with dice touchdown
                springSettle();  // overshoot a few degrees, bounce back to rest
            }
        }
        rollAnimId=requestAnimationFrame(tick);
    };

    window.nextTurn=function(){
        turn++;
        if(turn>=players.length){turn=0;round++;}
        if(round>6&&!IS_PREMIUM){
            document.getElementById('result-display').innerHTML=
                '<p style="color:var(--gold);margin:16px 0">'+escHtml(@json(__('minigame.dice_premium_gate')))+'</p>'+
                '<a href="{{ route('premium.index') }}" class="btn btn-outline-gold">'+escHtml(@json(__('minigame.go_premium')))+'</a>';
            document.getElementById('next-btn').style.display='none';
            return;
        }
        showTurn();
    };
    window.resetGame=function(){
        if(rollAnimId){cancelAnimationFrame(rollAnimId);rollAnimId=null;}
        document.getElementById('game-phase').style.display='none';
        document.getElementById('setup-phase').style.display='block';
        turn=0;round=0;
        clearHistory();
    };
})();
</script>
@endsection
