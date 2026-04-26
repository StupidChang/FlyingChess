@extends('layouts.app')
@section('title', '骰子挑戰 — 情侶骰子遊戲 — 情侶飛行棋')
@section('meta_description', '情侶骰子挑戰！擲出動作＋部位＋時間的隨機組合，輕鬆→中等→激烈三階段升溫，2-6 人同機暢玩。')
@section('canonical', route('dice-game.show'))

@section('styles')
<style>
.dg-page{max-width:600px;margin:0 auto;padding:20px 16px;min-height:calc(100vh - 56px);position:relative;isolation:isolate}
.dg-page::before{content:'';position:absolute;inset:0;background:radial-gradient(ellipse at 50% 20%,rgba(var(--glow-rgb,180,60,100),.1) 0%,transparent 70%);animation:hero-glow 6s ease-in-out infinite;pointer-events:none;z-index:-1}
.dg-page>*{position:relative}
.dg-title{text-align:center;color:var(--gold);font-size:1.4rem;margin-bottom:4px}
.dg-subtitle{text-align:center;color:var(--text-dim);font-size:.85rem;margin-bottom:20px}
.dg-setup{background:var(--card-bg,rgba(255,255,255,.06));border:1px solid var(--border);border-radius:12px;padding:20px}
.dg-player-row{display:flex;gap:8px;align-items:center;margin-bottom:10px;flex-wrap:wrap}
.dg-player-row input[type=text]{flex:1;min-width:100px}
.dg-player-remove{background:none;border:none;color:#e53935;font-size:1.2rem;cursor:pointer;padding:0 4px}
.dg-add-player{margin-bottom:16px}

/* Inline toast */
.dg-toast{position:fixed;top:20px;left:50%;transform:translateX(-50%);z-index:9999;padding:10px 24px;border-radius:8px;background:#2a0a0f;border:1px solid var(--rose,#e53935);color:#f06080;font-weight:600;font-size:.9rem;animation:dg-toast-in .3s ease-out,dg-toast-out .4s 2.5s ease-in forwards;pointer-events:none}
@keyframes dg-toast-in{from{opacity:0;transform:translateX(-50%) translateY(-20px)}to{opacity:1;transform:translateX(-50%) translateY(0)}}
@keyframes dg-toast-out{to{opacity:0;transform:translateX(-50%) translateY(-20px)}}

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

/* Result */
.dg-result{text-align:center;padding:20px;animation:fadeIn .3s ease-out}
@keyframes fadeIn{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:none}}
.dg-result-text{font-size:1.3rem;font-weight:700;color:var(--text-main,#fff);margin:16px 0;padding:12px 20px;background:rgba(255,255,255,.05);border:1px solid var(--border);border-radius:12px;display:inline-block}
.dg-turn-badge{text-align:center;color:var(--gold);font-size:1.1rem;margin-bottom:16px}
.dg-current-player{font-size:1.2rem;color:var(--gold);font-weight:700;text-align:center;margin-bottom:12px}
.dg-intensity-tag{display:inline-block;font-size:.75rem;padding:2px 8px;border-radius:4px;font-weight:600;margin-left:8px}
.dg-intensity-mild{background:#66bb6a;color:#fff}
.dg-intensity-medium{background:#ffa726;color:#fff}
.dg-intensity-intense{background:#ef5350;color:#fff}
.dg-action-btns{text-align:center;margin-top:20px;display:flex;gap:12px;justify-content:center;flex-wrap:wrap}
</style>
@endsection

@section('content')
<div class="dg-page">
    <h1 class="dg-title">骰子挑戰</h1>
    <p class="dg-subtitle">擲出動作＋部位＋時間的隨機組合，輪流挑戰！</p>

    {{-- Setup Phase --}}
    <div id="setup-phase" class="dg-setup">
        <h2 style="color:var(--gold);font-size:1.1rem;margin-bottom:12px">設定玩家 (2-6人)</h2>
        <div id="players-list">
            <div class="dg-player-row" data-idx="0">
                <input type="text" class="form-control p-name" value="玩家 1" maxlength="12">
            </div>
            <div class="dg-player-row" data-idx="1">
                <input type="text" class="form-control p-name" value="玩家 2" maxlength="12">
            </div>
        </div>
        <button class="btn btn-sm btn-outline dg-add-player" id="add-player-btn" onclick="addPlayer()">+ 新增玩家</button>
        <button class="btn btn-gold btn-full" onclick="startGame()">開始遊戲</button>
    </div>

    {{-- Game Phase --}}
    <div id="game-phase" style="display:none">
        <div class="dg-turn-badge" id="turn-badge"></div>
        <div class="dg-current-player" id="current-player"></div>
        <div class="dg-dice-area" id="dice-area"></div>
        <div id="result-display" class="dg-result" style="display:none"></div>
        <div class="dg-action-btns">
            <button class="btn btn-gold btn-xl" id="roll-btn" onclick="rollDice()">🎲 擲骰子</button>
            <button class="btn btn-gold btn-xl" id="next-btn" style="display:none" onclick="nextTurn()">下一位</button>
            <button class="btn btn-outline" onclick="resetGame()">重新開始</button>
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

    function escHtml(s){var d=document.createElement('div');d.appendChild(document.createTextNode(s));return d.innerHTML}

    function showToast(msg){
        var old=document.querySelector('.dg-toast');
        if(old) old.remove();
        var t=document.createElement('div');
        t.className='dg-toast';
        t.textContent=msg;
        document.body.appendChild(t);
        setTimeout(function(){t.remove()},3200);
    }

    function getTier(){
        if(round<=3) return 'mild';
        if(round<=6) return 'medium';
        return (POOLS.intense ? 'intense' : 'medium');
    }
    function intensityTag(){
        var t=getTier();
        if(t==='mild') return '<span class="dg-intensity-tag dg-intensity-mild">輕鬆</span>';
        if(t==='medium') return '<span class="dg-intensity-tag dg-intensity-medium">中等</span>';
        return '<span class="dg-intensity-tag dg-intensity-intense">激烈</span>';
    }
    function pick(arr){return arr[Math.floor(Math.random()*arr.length)]}

    /* Setup */
    var playerCount=2;
    window.addPlayer=function(){
        if(playerCount>=6) return;
        playerCount++;
        var row=document.createElement('div');
        row.className='dg-player-row';
        row.innerHTML='<input type="text" class="form-control p-name" value="玩家 '+playerCount+'" maxlength="12">'+
            '<button class="dg-player-remove" onclick="removePlayer(this)">✕</button>';
        document.getElementById('players-list').appendChild(row);
        if(playerCount>=6) document.getElementById('add-player-btn').style.display='none';
    };
    window.removePlayer=function(btn){
        btn.closest('.dg-player-row').remove();
        playerCount--;
        document.getElementById('add-player-btn').style.display='inline-block';
    };

    window.startGame=function(){
        var rows=document.querySelectorAll('.dg-player-row');
        players=[];
        rows.forEach(function(r){
            players.push(r.querySelector('.p-name').value.trim()||'玩家');
        });
        if(players.length<2){showToast('至少需要 2 位玩家');return;}
        turn=0;round=1;
        showTurn();
    };

    function showTurn(){
        document.getElementById('setup-phase').style.display='none';
        document.getElementById('game-phase').style.display='block';
        document.getElementById('turn-badge').innerHTML='第 '+round+' 回合 '+intensityTag();
        document.getElementById('current-player').textContent='輪到：'+players[turn];
        document.getElementById('roll-btn').style.display='inline-flex';
        document.getElementById('next-btn').style.display='none';
        document.getElementById('result-display').style.display='none';

        // Build 3D dice
        var tier=getTier();
        var pool=POOLS[tier];
        var area=document.getElementById('dice-area');
        area.innerHTML='';
        var diceData=[
            {label:'動作',values:pool.actions},
            {label:'部位',values:pool.parts},
            {label:'時間',values:pool.durations}
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
            diceParams.push({
                el:el,
                sRx:-20, sRy:25,
                eRx:tRx+extraX*360, eRy:tRy+extraY*360,
                fRx:fr.rx, fRy:fr.ry,
                wAmp:12+Math.random()*18, wFreq:3+Math.random()*2,
                delay:i*70
            });
        }

        function easeOutExpo(t){return t===1?1:1-Math.pow(2,-10*t)}

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
                d.el.style.transform='rotateX('+rx.toFixed(1)+'deg) rotateY('+ry.toFixed(1)+'deg) rotateZ('+rz.toFixed(1)+'deg) scale3d('+sc.toFixed(3)+','+sc.toFixed(3)+','+sc.toFixed(3)+')';
                var lift=Math.sin(t*Math.PI);
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
                if(navigator.vibrate) navigator.vibrate(30);
                var rd=document.getElementById('result-display');
                rd.style.display='block';
                rd.innerHTML='<div class="dg-result-text">'+escHtml(action)+' '+escHtml(part)+' '+escHtml(duration)+'</div>';
                document.getElementById('next-btn').style.display='inline-flex';
            }
        }
        rollAnimId=requestAnimationFrame(tick);
    };

    window.nextTurn=function(){
        turn++;
        if(turn>=players.length){turn=0;round++;}
        if(round>6&&!IS_PREMIUM){
            document.getElementById('result-display').innerHTML=
                '<p style="color:var(--gold);margin:16px 0">免費版最多 6 回合，升級 Premium 解鎖無限回合與更刺激的骰子！</p>'+
                '<a href="{{ route('premium.index') }}" class="btn btn-outline-gold">升級 Premium</a>';
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
    };
})();
</script>
@endsection
