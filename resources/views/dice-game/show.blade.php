@extends('layouts.app')
@section('title', __('minigame.dice_title') . ' — ' . __('ui.site_name'))
@section('meta_description', __('minigame.dice_meta'))
@section('canonical', route('dice-game.show'))

@section('styles')
<link rel="stylesheet" href="{{ asset_v('css/minigames.css') }}">
<style>
/* 3D Dice */
.dg-dice-area{display:flex;gap:38px;justify-content:center;flex-wrap:wrap;padding:34px 6px}
.dg-dice-wrapper{text-align:center}
.dg-dice-label{font-size:.8rem;color:var(--text-dim);margin-bottom:8px;font-weight:600}
.dg-dice-scene{width:80px;height:80px;perspective:300px;margin:0 auto;filter:drop-shadow(0 2px 4px rgba(0,0,0,.3));transform:translateZ(0)}
.dg-dice{width:100%;height:100%;position:relative;transform-style:preserve-3d;transform:rotateX(-20deg) rotateY(25deg);-webkit-backface-visibility:hidden;backface-visibility:hidden}
/* outline:transparent + backface hints let the compositor anti-alias the rotated edges (kills the jaggies) */
.dg-dice-face{position:absolute;width:80px;height:80px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:.85rem;font-weight:700;color:#fff;border:2px solid rgba(255,255,255,.2);-webkit-backface-visibility:hidden;backface-visibility:hidden;outline:1px solid transparent;text-align:center;line-height:1.2;padding:4px}
.dg-dice-face.f1{transform:rotateY(0deg) translateZ(40px)}
.dg-dice-face.f2{transform:rotateY(180deg) translateZ(40px)}
.dg-dice-face.f3{transform:rotateY(90deg) translateZ(40px)}
.dg-dice-face.f4{transform:rotateY(-90deg) translateZ(40px)}
.dg-dice-face.f5{transform:rotateX(90deg) translateZ(40px)}
.dg-dice-face.f6{transform:rotateX(-90deg) translateZ(40px)}
/* Colour by dice type (stable regardless of which dice are toggled on) */
.dg-die-action .dg-dice-face{background:linear-gradient(135deg,#e53935,#c62828)}
.dg-die-part .dg-dice-face{background:linear-gradient(135deg,#2563eb,#1d4ed8)}
.dg-die-time .dg-dice-face{background:linear-gradient(135deg,#7c3aed,#6d28d9)}
.dg-die-prop .dg-dice-face{background:linear-gradient(135deg,#0d9488,#0f766e)}
.dg-die-custom .dg-dice-face{background:linear-gradient(135deg,#d9a441,#b8860b)}

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

/* Play area: dice stage stays centered in the page column; the picker floats
   as an independent sidebar to the LEFT (outside the column) on wide screens,
   and stacks above the dice on narrower screens. */
.dg-play{position:relative;margin-top:8px}
.dg-stage{max-width:none}
.dg-picker{position:absolute;top:0;right:calc(100% + 24px);width:198px;text-align:left}
.dg-picker-label{font-size:.82rem;color:var(--text-dim);margin-bottom:10px;font-weight:700;letter-spacing:.3px}
.dg-picker-list{
  display:flex;flex-direction:column;gap:16px;min-width:200px;
  background:var(--surface);border:1px solid var(--border);border-radius:16px;
  padding:16px 14px;box-shadow:0 6px 20px rgba(0,0,0,.20);
}
.dg-picker-group{display:flex;flex-direction:column;gap:5px}
.dg-picker-head{font-size:.66rem;color:var(--text-dim);font-weight:800;letter-spacing:1.2px;text-transform:uppercase;opacity:.65;padding-left:4px;margin-bottom:2px}
.dg-picker-item{
  display:flex;align-items:center;gap:10px;
  padding:9px 12px;border-radius:10px;cursor:pointer;
  background:var(--surface2);border:1px solid transparent;color:var(--text-dim);
  font-size:.9rem;font-weight:600;transition:background .15s,color .15s,border-color .15s,box-shadow .15s;
}
.dg-picker-item:hover{color:var(--text);background:var(--border)}
.dg-picker-item.active{background:rgba(244,63,94,.14);border-color:var(--accent);color:var(--text);box-shadow:inset 3px 0 0 var(--accent)}
.dg-picker-dot{width:9px;height:9px;border-radius:50%;flex-shrink:0;box-shadow:0 0 0 3px rgba(255,255,255,.05)}
.dg-picker-dot-action{background:#e53935}
.dg-picker-dot-part{background:#2563eb}
.dg-picker-dot-time{background:#7c3aed}
.dg-picker-dot-prop{background:#0d9488}
.dg-picker-dot-custom{background:#d9a441}
.dg-picker-name{flex:1;white-space:nowrap}
.dg-picker-check{width:16px;text-align:center;color:var(--accent);font-weight:800;opacity:0;transition:opacity .15s}
.dg-picker-item.active .dg-picker-check{opacity:1}
.dg-picker-item.locked{opacity:.5}
.dg-picker-item.locked:hover{background:var(--surface2);color:var(--text-dim)}
.dg-lock{width:16px;text-align:center;font-size:.72rem}
.dg-manage-link{display:inline-block;margin-top:10px;font-size:.82rem;color:var(--accent)}
.dg-manage-link:hover{text-decoration:underline}
/* Not enough room to the left → put the picker back in flow, above the dice */
@media(max-width:1060px){
  .dg-picker{position:static;right:auto;width:100%;max-width:360px;margin:0 auto 22px}
  .dg-picker-list{max-width:360px;margin:0 auto}
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

        <div class="dg-play">
            {{-- Left: pick which dice to use --}}
            <aside class="dg-picker">
                <div class="dg-picker-label">{{ __('minigame.dice_pick_tier') }}</div>
                <div class="dg-picker-list" id="dice-select"></div>
                @auth
                    <a href="{{ route('dice.index') }}" class="dg-manage-link">{{ __('minigame.dice_manage') }} →</a>
                @endauth
            </aside>

            {{-- Right: dice stage --}}
            <div class="dg-stage">
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
    </div>
</div>

@include('partials.ad-unit', ['zone' => 'lobby_side'])
@endsection

@section('scripts')
<script>
(function(){
    var IS_PREMIUM = {{ $isPremium ? 'true' : 'false' }};
    var DICE = @json($dice);            // built-in dice: {id,cat,intensity,premium,locked,custom,faces}
    var CUSTOM = @json($customDice);    // user's saved custom dice (same shape + name)
    var ALL = DICE.concat(CUSTOM);
    var BY_ID = {};
    ALL.forEach(function(d){ BY_ID[d.id]=d; });
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

    // Each category offers gentle/bold/wild variants (wild = premium); the player
    // picks which dice to include. Custom account dice are grouped under 我的骰子.
    var CAT_ORDER = ['action','part','time','prop','custom'];
    var CAT_LABELS = {
        action: @json(__('minigame.dice_label_action')),
        part:   @json(__('minigame.dice_label_part')),
        time:   @json(__('minigame.dice_label_time')),
        prop:   @json(__('minigame.dice_label_prop')),
        custom: @json(__('minigame.dice_my'))
    };
    var INT_LABELS = {
        gentle:   @json(__('minigame.dice_int_gentle')),
        bold:     @json(__('minigame.dice_int_bold')),
        wild:     @json(__('minigame.dice_int_wild')),
        standard: @json(__('minigame.dice_int_standard'))
    };
    var WILD_LOCKED_MSG = @json(__('minigame.dice_wild_locked'));
    var NEED_ONE_MSG    = @json(__('minigame.dice_need_one'));

    var enabled = {};   // die id -> true
    function defaultEnabled(){
        enabled = {};
        ['builtin_action_gentle','builtin_part_gentle','builtin_time'].forEach(function(id){
            if(BY_ID[id]) enabled[id]=true;
        });
    }
    defaultEnabled();

    var builtDice=[];   // [{catClass,topLabel,values:[…≤6]}] — dice currently on the table

    function updateBadge(){
        var roundLabel = @json(__('minigame.round_n', ['n' => '__N__'])).replace('__N__', round);
        document.getElementById('turn-badge').textContent=roundLabel;
    }

    function shuffled(arr){
        var a=arr.slice();
        for(var i=a.length-1;i>0;i--){var j=Math.floor(Math.random()*(i+1));var t=a[i];a[i]=a[j];a[j]=t;}
        return a;
    }

    function catClassOf(d){ return d.custom ? 'custom' : d.cat; }
    function itemLabel(d){
        if(d.custom) return d.name;
        if(d.intensity) return INT_LABELS[d.intensity] || '';
        return INT_LABELS.standard;
    }
    function topLabelOf(d){
        if(d.custom) return d.name;
        if(d.intensity) return CAT_LABELS[d.cat]+' · '+(INT_LABELS[d.intensity]||'');
        return CAT_LABELS[d.cat];
    }
    function activeDice(){ return ALL.filter(function(d){return enabled[d.id] && !d.locked}); }

    function renderDiceSelect(){
        var wrap=document.getElementById('dice-select');
        if(!wrap) return;
        wrap.innerHTML='';
        CAT_ORDER.forEach(function(cat){
            var items = ALL.filter(function(d){ return cat==='custom' ? d.custom : (d.cat===cat && !d.custom); });
            if(!items.length) return;
            var group=document.createElement('div');
            group.className='dg-picker-group';
            group.innerHTML='<div class="dg-picker-head">'+escHtml(CAT_LABELS[cat]||cat)+'</div>';
            items.forEach(function(d){
                var cc=catClassOf(d);
                var b=document.createElement('button');
                b.type='button';
                var cls='dg-picker-item dg-cat-'+cc;
                if(enabled[d.id] && !d.locked) cls+=' active';
                if(d.locked) cls+=' locked';
                b.className=cls;
                b.innerHTML='<span class="dg-picker-dot dg-picker-dot-'+cc+'"></span>'+
                    '<span class="dg-picker-name">'+escHtml(itemLabel(d))+'</span>'+
                    (d.locked?'<span class="dg-lock">🔒</span>':'<span class="dg-picker-check">✓</span>');
                b.onclick=function(){toggleDie(d.id)};
                group.appendChild(b);
            });
            wrap.appendChild(group);
        });
    }

    function groupOf(d){ return d.custom ? 'custom' : d.cat; }

    window.toggleDie=function(id){
        if(rollAnimId) return;                 // don't toggle mid-roll
        var d=BY_ID[id]; if(!d) return;
        if(d.locked){ showToast(WILD_LOCKED_MSG); return; }
        if(enabled[id]){
            // turning the selected one off — but keep at least one die overall
            if(activeDice().length<=1){ showToast(NEED_ONE_MSG); return; }
            enabled[id]=false;
        } else {
            // single-select per group: picking a variant replaces the group's current pick
            var g=groupOf(d);
            ALL.forEach(function(o){ if(groupOf(o)===g && enabled[o.id]) enabled[o.id]=false; });
            enabled[id]=true;
        }
        renderDiceSelect();
        buildDice();
        // let the player re-roll with the new set without advancing the turn
        document.getElementById('result-display').style.display='none';
        document.getElementById('next-btn').style.display='none';
        document.getElementById('roll-btn').style.display='inline-flex';
    };

    function buildDice(){
        var defs=activeDice();
        var area=document.getElementById('dice-area');
        area.innerHTML='';
        builtDice=[];
        defs.forEach(function(d,di){
            var faces=(d.faces&&d.faces.length)?d.faces:[''];
            var values=shuffled(faces).slice(0,6);
            builtDice.push({catClass:catClassOf(d),topLabel:topLabelOf(d),values:values});
            var facesHtml='';
            for(var fi=0;fi<values.length;fi++){
                facesHtml+='<div class="dg-dice-face f'+(fi+1)+'">'+escHtml(values[fi])+'</div>';
            }
            var w=document.createElement('div');
            w.className='dg-dice-wrapper dg-die-'+catClassOf(d);
            w.innerHTML='<div class="dg-dice-label">'+escHtml(topLabelOf(d))+'</div>'+
                '<div class="dg-dice-scene"><div class="dg-dice" id="dice-'+di+'">'+ facesHtml +'</div></div>';
            area.appendChild(w);
        });
    }

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
        turn=0;round=1;defaultEnabled();
        clearHistory();
        showTurn();
    };

    function showTurn(){
        document.getElementById('setup-phase').style.display='none';
        document.getElementById('game-phase').style.display='block';
        updateBadge();
        var turnLabel = @json(__('minigame.turn_player', ['name' => '__NAME__'])).replace('__NAME__', players[turn]);
        document.getElementById('current-player').textContent=turnLabel;
        document.getElementById('roll-btn').style.display='inline-flex';
        document.getElementById('next-btn').style.display='none';
        document.getElementById('result-display').style.display='none';

        renderDiceSelect();
        buildDice();
    }

    window.rollDice=function(){
        document.getElementById('roll-btn').style.display='none';
        var actionBtns=document.querySelector('.mg-action-btns');
        if(actionBtns) actionBtns.classList.add('dg-rolling');

        // Roll every active die: pick a random face from the faces currently shown.
        var faceRot=[
            {rx:0,ry:0},{rx:0,ry:180},{rx:0,ry:-90},{rx:0,ry:90},{rx:-90,ry:0},{rx:90,ry:0}
        ];
        var indices=[];
        var resultTokens=[];
        for(var bi=0;bi<builtDice.length;bi++){
            var vals=builtDice[bi].values;
            var idx=Math.floor(Math.random()*Math.min(vals.length,6));
            indices.push(idx);
            resultTokens.push(vals[idx]);
        }
        var resultText=resultTokens.join(' ');

        // Build animation params for each dice
        var ANIM_DUR=1800;
        var startTime=null;
        var diceParams=[];
        for(var i=0;i<builtDice.length;i++){
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
            rd.innerHTML='<div class="mg-result-text">'+escHtml(resultText)+'</div>';
            document.getElementById('next-btn').style.display='inline-flex';
            addHistory(resultText);
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
