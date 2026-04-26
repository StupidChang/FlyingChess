@extends('layouts.app')
@section('title', '命運轉盤 — 情侶派對遊戲 — 情侶飛行棋')
@section('meta_description', '情侶命運轉盤！轉動轉盤隨機決定任務，三階段升溫。2-6 人輪流挑戰，同機暢玩。')
@section('canonical', route('wheel-game.show'))

@section('styles')
<style>
.wg-page{max-width:640px;margin:0 auto;padding:20px 16px;min-height:calc(100vh - 56px);position:relative;isolation:isolate}
.wg-page::before{content:'';position:absolute;inset:0;background:radial-gradient(ellipse at 50% 20%,rgba(var(--glow-rgb,180,60,100),.12) 0%,transparent 70%);animation:hero-glow 6s ease-in-out infinite;pointer-events:none;z-index:-1}
.wg-page>*{position:relative}
.wg-title{text-align:center;color:var(--gold);font-size:1.4rem;margin-bottom:4px}
.wg-subtitle{text-align:center;color:var(--text-dim);font-size:.85rem;margin-bottom:24px}

/* ── Toast ── */
.wg-toast{position:fixed;top:20px;left:50%;transform:translateX(-50%);z-index:9999;padding:10px 24px;border-radius:8px;background:#2a0a0f;border:1px solid var(--rose,#e53935);color:#f06080;font-weight:600;font-size:.9rem;animation:wg-toast-in .3s ease-out,wg-toast-out .4s 2.5s ease-in forwards;pointer-events:none}
@keyframes wg-toast-in{from{opacity:0;transform:translateX(-50%) translateY(-20px)}to{opacity:1;transform:translateX(-50%) translateY(0)}}
@keyframes wg-toast-out{to{opacity:0;transform:translateX(-50%) translateY(-20px)}}

/* ── Phase 1: Lobby — wheel picker ── */
.wg-lobby-grid{display:grid;gap:16px}
.wg-wheel-card{
    background:var(--surface,rgba(255,255,255,.04));
    border:1px solid var(--border);
    border-radius:14px;
    padding:20px;
    display:flex;gap:16px;align-items:flex-start;
    cursor:pointer;
    transition:border-color .2s,transform .15s,box-shadow .2s;
    position:relative;overflow:hidden;
}
.wg-wheel-card::after{content:'';position:absolute;inset:0;background:linear-gradient(135deg,rgba(255,255,255,.03) 0%,transparent 60%);pointer-events:none}
.wg-wheel-card:hover{border-color:var(--gold);transform:translateY(-2px);box-shadow:0 6px 20px rgba(0,0,0,.3)}
.wg-wheel-card.locked{opacity:.6;cursor:not-allowed}
.wg-wheel-card.locked:hover{border-color:var(--border);transform:none;box-shadow:none}

.wg-card-icon{width:72px;height:72px;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;position:relative}
.wg-card-icon canvas{border-radius:50%;box-shadow:0 2px 8px rgba(0,0,0,.3)}
.wg-card-icon-mild{background:rgba(102,187,106,.15);color:#66bb6a}
.wg-card-icon-medium{background:rgba(255,167,38,.15);color:#ffa726}
.wg-card-icon-intense{background:rgba(239,83,80,.15);color:#ef5350}

.wg-card-body{flex:1;min-width:0}
.wg-card-header{display:flex;align-items:center;gap:8px;margin-bottom:4px}
.wg-card-name{font-size:1.05rem;font-weight:700;color:var(--text)}
.wg-card-badge{font-size:.7rem;padding:2px 8px;border-radius:4px;font-weight:600}
.wg-badge-mild{background:rgba(102,187,106,.15);color:#66bb6a}
.wg-badge-medium{background:rgba(255,167,38,.15);color:#ffa726}
.wg-badge-intense{background:rgba(239,83,80,.15);color:#ef5350}
.wg-card-desc{font-size:.8rem;color:var(--text-dim);line-height:1.5;margin-bottom:8px}
.wg-card-preview{display:flex;flex-wrap:wrap;gap:4px}
.wg-card-preview-tag{font-size:.7rem;color:var(--text-dim);background:rgba(255,255,255,.06);padding:2px 8px;border-radius:4px;border:1px solid rgba(255,255,255,.06)}
.wg-card-count{font-size:.75rem;color:var(--text-dim);margin-top:6px}
.wg-card-lock{position:absolute;top:16px;right:16px;font-size:.8rem;color:var(--text-dim)}

/* ── Phase 2: Setup ── */
.wg-setup{background:var(--card-bg,rgba(255,255,255,.06));border:1px solid var(--border);border-radius:12px;padding:20px}
.wg-player-row{display:flex;gap:8px;align-items:center;margin-bottom:10px;flex-wrap:wrap}
.wg-player-row input[type=text]{flex:1;min-width:100px}
.wg-player-remove{background:none;border:none;color:#e53935;font-size:1.2rem;cursor:pointer;padding:0 4px}
.wg-add-player{margin-bottom:16px}
.wg-selected-wheel{display:flex;align-items:center;gap:10px;padding:12px;background:rgba(255,215,0,.05);border:1px solid rgba(255,215,0,.2);border-radius:10px;margin-bottom:16px}
.wg-selected-icon{font-size:1.2rem}
.wg-selected-info{flex:1}
.wg-selected-name{font-weight:700;color:var(--gold);font-size:.95rem}
.wg-selected-count{font-size:.75rem;color:var(--text-dim)}
.wg-change-btn{font-size:.8rem;color:var(--gold);background:none;border:1px solid rgba(255,215,0,.3);border-radius:6px;padding:4px 10px;cursor:pointer;transition:background .15s}
.wg-change-btn:hover{background:rgba(255,215,0,.1)}

/* ── Phase 3: Game ── */
.wg-wheel-wrapper{position:relative;width:320px;height:320px;margin:16px auto}
.wg-wheel-glow{position:absolute;inset:-12px;border-radius:50%;background:conic-gradient(#e53935,#fb8c00,#fdd835,#43a047,#1e88e5,#8e24aa,#f06292,#e53935);filter:blur(16px);opacity:.35;animation:wg-glow-spin 8s linear infinite;z-index:0}
@keyframes wg-glow-spin{from{transform:rotate(0deg)}to{transform:rotate(360deg)}}
.wg-wheel-ring{position:absolute;inset:-4px;border-radius:50%;border:3px solid transparent;background:linear-gradient(var(--bg),var(--bg)) padding-box,conic-gradient(#e53935,#fb8c00,#fdd835,#43a047,#1e88e5,#8e24aa,#f06292,#e53935) border-box;z-index:1}
.wg-wheel-container{position:relative;width:320px;height:320px;z-index:2}
.wg-wheel-canvas{width:320px;height:320px;border-radius:50%;transition:transform 5s cubic-bezier(.17,.67,.05,.99);box-shadow:0 0 30px rgba(0,0,0,.4)}
.wg-wheel-pointer{position:absolute;top:-16px;left:50%;transform:translateX(-50%);z-index:5;font-size:0;width:0;height:0;border-left:14px solid transparent;border-right:14px solid transparent;border-top:28px solid var(--gold);filter:drop-shadow(0 2px 6px rgba(0,0,0,.5))}
.wg-wheel-center{position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);width:50px;height:50px;background:radial-gradient(circle,#fff 0%,#f0e6d3 100%);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.85rem;box-shadow:0 0 16px rgba(255,215,0,.5),0 2px 8px rgba(0,0,0,.3);z-index:3;color:var(--gold);font-weight:800;letter-spacing:1px;border:2px solid var(--gold)}
.wg-wheel-wrapper.spinning .wg-wheel-glow{opacity:.6;filter:blur(20px);animation:wg-glow-spin 2s linear infinite}

/* ── Task list (replaces legend) ── */
.wg-task-panel{margin:20px auto 0;max-width:380px;background:var(--surface,rgba(255,255,255,.04));border:1px solid var(--border);border-radius:12px;overflow:hidden}
.wg-task-header{padding:12px 16px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between}
.wg-task-header-title{font-size:.85rem;font-weight:700;color:var(--text)}
.wg-task-header-badge{font-size:.7rem;padding:2px 8px;border-radius:4px;font-weight:600}
.wg-task-list{list-style:none;padding:0;margin:0}
.wg-task-item{display:flex;align-items:center;gap:10px;padding:10px 16px;border-bottom:1px solid rgba(255,255,255,.04);transition:background .15s}
.wg-task-item:last-child{border-bottom:none}
.wg-task-item:hover{background:rgba(255,255,255,.03)}
.wg-task-num{width:22px;height:22px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.7rem;font-weight:700;color:#fff;flex-shrink:0}
.wg-task-text{font-size:.85rem;color:var(--text-dim);line-height:1.4}

/* ── Result ── */
.wg-result{text-align:center;padding:16px;animation:wg-result-in .5s cubic-bezier(.34,1.56,.64,1)}
@keyframes wg-result-in{from{opacity:0;transform:scale(.8) translateY(20px)}to{opacity:1;transform:scale(1) translateY(0)}}
.wg-result-text{font-size:1.25rem;font-weight:700;color:var(--gold);margin:12px 0;padding:16px 24px;background:rgba(255,215,0,.08);border:1px solid rgba(255,215,0,.3);border-radius:12px;display:inline-block;box-shadow:0 0 20px rgba(255,215,0,.1)}
.wg-turn-badge{text-align:center;color:var(--gold);font-size:1.1rem;margin-bottom:12px}
.wg-current-player{font-size:1.3rem;color:var(--gold);font-weight:700;text-align:center;margin-bottom:8px;text-shadow:0 0 12px rgba(255,215,0,.3)}
.wg-action-btns{text-align:center;margin-top:20px;display:flex;gap:12px;justify-content:center;flex-wrap:wrap}

#spin-btn{animation:wg-btn-pulse 2s ease-in-out infinite}
@keyframes wg-btn-pulse{0%,100%{box-shadow:0 0 0 0 rgba(255,215,0,.4)}50%{box-shadow:0 0 0 12px rgba(255,215,0,0)}}
#spin-btn:hover{animation:none}

#confetti-canvas{position:fixed;top:0;left:0;width:100%;height:100%;pointer-events:none;z-index:9999}
.wg-particles{position:absolute;inset:-20px;border-radius:50%;z-index:0;pointer-events:none}
.wg-particle{position:absolute;width:4px;height:4px;border-radius:50%;opacity:0}
.wg-wheel-wrapper.spinning .wg-particle{animation:wg-particle-float 1.5s ease-out infinite}
@keyframes wg-particle-float{0%{opacity:1;transform:translate(0,0) scale(1)}100%{opacity:0;transform:translate(var(--dx),var(--dy)) scale(0)}}
</style>
@endsection

@section('content')
<div class="wg-page">
    <h1 class="wg-title">命運轉盤</h1>
    <p class="wg-subtitle">選擇轉盤風格，轉動命運之輪！</p>

    {{-- Phase 1: Lobby — pick a wheel --}}
    <div id="lobby-phase">
        <div class="wg-lobby-grid" id="wheel-cards"></div>
    </div>

    {{-- Phase 2: Setup — add players --}}
    <div id="setup-phase" style="display:none">
        <div class="wg-setup">
            <div class="wg-selected-wheel" id="selected-wheel-bar"></div>

            <h2 style="color:var(--gold);font-size:1.1rem;margin-bottom:12px">設定玩家 (2-6人)</h2>
            <div id="players-list">
                <div class="wg-player-row" data-idx="0">
                    <input type="text" class="form-control p-name" value="玩家 1" maxlength="12">
                </div>
                <div class="wg-player-row" data-idx="1">
                    <input type="text" class="form-control p-name" value="玩家 2" maxlength="12">
                </div>
            </div>
            <button class="btn btn-sm btn-outline wg-add-player" id="add-player-btn" onclick="addPlayer()">+ 新增玩家</button>
            <button class="btn btn-gold btn-full" onclick="startGame()">開始遊戲</button>
        </div>
    </div>

    {{-- Phase 3: Game — spin --}}
    <div id="game-phase" style="display:none">
        <div class="wg-turn-badge" id="turn-badge"></div>
        <div class="wg-current-player" id="current-player"></div>

        <div class="wg-wheel-wrapper" id="wheel-wrapper">
            <div class="wg-wheel-glow"></div>
            <div class="wg-wheel-ring"></div>
            <div class="wg-particles" id="particles"></div>
            <div class="wg-wheel-container">
                <div class="wg-wheel-pointer"></div>
                <canvas id="wheel-canvas" class="wg-wheel-canvas" width="320" height="320"></canvas>
                <div class="wg-wheel-center">SPIN</div>
            </div>
        </div>

        <div id="result-display" class="wg-result" style="display:none"></div>

        <div id="task-panel" class="wg-task-panel"></div>

        <div class="wg-action-btns">
            <button class="btn btn-gold btn-xl" id="spin-btn" onclick="spinWheel()">🎯 轉動轉盤</button>
            <button class="btn btn-gold btn-xl" id="next-btn" style="display:none" onclick="nextTurn()">下一位</button>
            <button class="btn btn-outline" onclick="resetGame()">重新開始</button>
        </div>
    </div>
</div>
<canvas id="confetti-canvas"></canvas>

@include('partials.ad-unit', ['zone' => 'lobby_side'])
@endsection

@section('scripts')
<script>
(function(){
    var IS_PREMIUM = {{ $isPremium ? 'true' : 'false' }};
    var SEGMENTS = @json($segments);
    var players=[];
    var turn=0;
    var round=0;
    var spinning=false;
    var spinTimer=null;
    var currentAngle=0;
    var currentSegments=[];
    var currentTier='mild';

    var TIER_META={
        mild:   {name:'輕鬆互動',icon:'🌸',desc:'適合暖場，輕鬆可愛的互動指令',badge:'wg-badge-mild'},
        medium: {name:'親密升溫',icon:'🔥',desc:'更近一步的親密接觸，感情加溫',badge:'wg-badge-medium'},
        intense:{name:'大膽挑戰',icon:'💋',desc:'極致浪漫的大膽指令，僅限 Premium',badge:'wg-badge-intense'}
    };

    var COLORS=[
        ['#e53935','#ff6659'],['#fb8c00','#ffbd45'],['#fdd835','#ffff6b'],
        ['#43a047','#76d275'],['#1e88e5','#6ab7ff'],['#8e24aa','#c158dc'],
        ['#f06292','#ff94c2'],['#26a69a','#64d8cb']
    ];

    function escHtml(s){var d=document.createElement('div');d.appendChild(document.createTextNode(s));return d.innerHTML}

    function showToast(msg){
        var old=document.querySelector('.wg-toast');
        if(old) old.remove();
        var t=document.createElement('div');
        t.className='wg-toast';
        t.textContent=msg;
        document.body.appendChild(t);
        setTimeout(function(){t.remove()},3200);
    }

    function shuffle(arr){
        var a=arr.slice();
        for(var i=a.length-1;i>0;i--){var j=Math.floor(Math.random()*(i+1));var t=a[i];a[i]=a[j];a[j]=t}
        return a;
    }

    function pickSegments(tier){
        var pool=SEGMENTS[tier];
        if(!pool||!pool.length) return [];
        if(pool.length<=8) return pool.slice();
        return shuffle(pool).slice(0,8);
    }

    function showPhase(phase){
        ['lobby-phase','setup-phase','game-phase'].forEach(function(id){
            document.getElementById(id).style.display='none';
        });
        document.getElementById(phase).style.display='block';
    }

    /* ═══════════════════════════════════════════
       Phase 1 — Lobby: Render wheel cards
       ═══════════════════════════════════════════ */
    function drawMiniWheel(canvas,segments){
        var ctx=canvas.getContext('2d');
        var cx=36,cy=36,r=34;
        var n=Math.min(segments.length,8);
        if(n===0) return;
        var arc=2*Math.PI/n;
        ctx.clearRect(0,0,72,72);
        for(var i=0;i<n;i++){
            var sa=i*arc-Math.PI/2;
            var ea=(i+1)*arc-Math.PI/2;
            ctx.beginPath();ctx.moveTo(cx,cy);ctx.arc(cx,cy,r,sa,ea);ctx.closePath();
            ctx.fillStyle=COLORS[i%COLORS.length][0];ctx.fill();
            ctx.strokeStyle='rgba(255,255,255,.3)';ctx.lineWidth=1;ctx.stroke();
        }
        ctx.beginPath();ctx.arc(cx,cy,8,0,2*Math.PI);ctx.fillStyle='#fff';ctx.fill();
    }

    function renderLobby(){
        var grid=document.getElementById('wheel-cards');
        var html='';
        ['mild','medium','intense'].forEach(function(tier){
            var meta=TIER_META[tier];
            var pool=SEGMENTS[tier]||[];
            var locked=(tier==='intense'&&!IS_PREMIUM);
            var preview=pool.slice(0,4);

            html+='<div class="wg-wheel-card'+(locked?' locked':'')+'" onclick="selectWheel(\''+tier+'\')">';
            html+='<div class="wg-card-icon wg-card-icon-'+tier+'"><canvas class="wg-mini-wheel" data-tier="'+tier+'" width="72" height="72"></canvas></div>';
            html+='<div class="wg-card-body">';
            html+='<div class="wg-card-header"><span class="wg-card-name">'+meta.name+'</span><span class="wg-card-badge '+meta.badge+'">'+pool.length+' 個任務</span></div>';
            html+='<div class="wg-card-desc">'+meta.desc+'</div>';

            if(preview.length){
                html+='<div class="wg-card-preview">';
                preview.forEach(function(s){html+='<span class="wg-card-preview-tag">'+escHtml(s)+'</span>';});
                if(pool.length>4) html+='<span class="wg-card-preview-tag">⋯</span>';
                html+='</div>';
            }
            if(locked) html+='<div class="wg-card-lock">🔒 Premium</div>';
            html+='</div></div>';
        });
        grid.innerHTML=html;

        // Draw mini wheel thumbnails
        document.querySelectorAll('.wg-mini-wheel').forEach(function(c){
            var t=c.getAttribute('data-tier');
            var segs=SEGMENTS[t]||[];
            drawMiniWheel(c,segs.slice(0,8));
        });
    }

    window.selectWheel=function(tier){
        if(tier==='intense'&&!IS_PREMIUM){
            showToast('大膽轉盤需要 Premium 會員才能使用');
            return;
        }
        var pool=SEGMENTS[tier];
        if(!pool||!pool.length){showToast('此轉盤目前沒有任務');return;}
        currentTier=tier;
        renderSelectedBar();
        showPhase('setup-phase');
    };

    function renderSelectedBar(){
        var meta=TIER_META[currentTier];
        var pool=SEGMENTS[currentTier]||[];
        var bar=document.getElementById('selected-wheel-bar');
        bar.innerHTML=
            '<span class="wg-selected-icon">'+meta.icon+'</span>'+
            '<div class="wg-selected-info"><div class="wg-selected-name">'+meta.name+'</div><div class="wg-selected-count">'+pool.length+' 個任務，每輪隨機抽 8 個</div></div>'+
            '<button class="wg-change-btn" onclick="backToLobby()">換轉盤</button>';
    }

    window.backToLobby=function(){
        showPhase('lobby-phase');
    };

    /* ═══════════════════════════════════════════
       Phase 2 — Setup: Players
       ═══════════════════════════════════════════ */
    var playerCount=2;
    window.addPlayer=function(){
        if(playerCount>=6) return;
        playerCount++;
        var row=document.createElement('div');
        row.className='wg-player-row';
        row.innerHTML='<input type="text" class="form-control p-name" value="玩家 '+playerCount+'" maxlength="12">'+
            '<button class="wg-player-remove" onclick="removePlayer(this)">✕</button>';
        document.getElementById('players-list').appendChild(row);
        if(playerCount>=6) document.getElementById('add-player-btn').style.display='none';
    };
    window.removePlayer=function(btn){
        btn.closest('.wg-player-row').remove();
        playerCount--;
        document.getElementById('add-player-btn').style.display='inline-block';
    };

    window.startGame=function(){
        var rows=document.querySelectorAll('.wg-player-row');
        players=[];
        rows.forEach(function(r){
            players.push(r.querySelector('.p-name').value.trim()||'玩家');
        });
        if(players.length<2){showToast('至少需要 2 位玩家');return;}
        turn=0;round=1;
        showTurn();
    };

    /* ═══════════════════════════════════════════
       Phase 3 — Game: Wheel + task list
       ═══════════════════════════════════════════ */
    function showTurn(){
        showPhase('game-phase');
        var meta=TIER_META[currentTier];
        document.getElementById('turn-badge').innerHTML='第 '+round+' 回合 <span class="wg-card-badge '+meta.badge+'">'+meta.name+'</span>';
        document.getElementById('current-player').textContent='🎯 '+players[turn]+' 的回合';
        document.getElementById('spin-btn').style.display='inline-flex';
        document.getElementById('next-btn').style.display='none';
        document.getElementById('result-display').style.display='none';
        document.getElementById('wheel-wrapper').classList.remove('spinning');
        spinning=false;

        currentSegments=pickSegments(currentTier);
        var canvas=document.getElementById('wheel-canvas');
        canvas.style.transition='none';
        canvas.style.transform='rotate(0deg)';
        currentAngle=0;
        drawWheel();
        buildTaskPanel();
        createParticles();
    }

    function drawWheel(){
        var canvas=document.getElementById('wheel-canvas');
        var ctx=canvas.getContext('2d');
        var cx=160,cy=160,r=156;
        var n=currentSegments.length;
        if(n===0) return;
        var arc=2*Math.PI/n;

        ctx.clearRect(0,0,320,320);

        for(var i=0;i<n;i++){
            var startAngle=i*arc-Math.PI/2;
            var endAngle=(i+1)*arc-Math.PI/2;
            var grad=ctx.createRadialGradient(cx,cy,20,cx,cy,r);
            grad.addColorStop(0,COLORS[i%COLORS.length][1]);
            grad.addColorStop(1,COLORS[i%COLORS.length][0]);

            ctx.beginPath();
            ctx.moveTo(cx,cy);
            ctx.arc(cx,cy,r,startAngle,endAngle);
            ctx.closePath();
            ctx.fillStyle=grad;
            ctx.fill();
            ctx.strokeStyle='rgba(255,255,255,.4)';
            ctx.lineWidth=2;
            ctx.stroke();

            // Text — multi-line for long text
            ctx.save();
            ctx.translate(cx,cy);
            ctx.rotate(startAngle+arc/2);
            ctx.fillStyle='#fff';
            ctx.shadowColor='rgba(0,0,0,.5)';
            ctx.shadowBlur=3;
            ctx.textAlign='center';

            var text=currentSegments[i];
            if(text.length<=6){
                ctx.font='bold 11px sans-serif';
                ctx.fillText(text,r*0.6,4);
            } else if(text.length<=10){
                ctx.font='bold 10px sans-serif';
                ctx.fillText(text.substring(0,6),r*0.58,-4);
                ctx.fillText(text.substring(6),r*0.58,10);
            } else {
                ctx.font='bold 9px sans-serif';
                ctx.fillText(text.substring(0,6),r*0.58,-4);
                ctx.fillText(text.substring(6,12),r*0.58,10);
            }
            ctx.shadowBlur=0;
            ctx.restore();
        }

        ctx.beginPath();
        ctx.arc(cx,cy,r,0,2*Math.PI);
        ctx.strokeStyle='rgba(255,255,255,.15)';
        ctx.lineWidth=3;
        ctx.stroke();
    }

    function buildTaskPanel(){
        var panel=document.getElementById('task-panel');
        if(!currentSegments.length){panel.innerHTML='';return;}
        var meta=TIER_META[currentTier];
        var html='<div class="wg-task-header"><span class="wg-task-header-title">本輪任務</span><span class="wg-task-header-badge '+meta.badge+'">'+meta.name+'</span></div>';
        html+='<ul class="wg-task-list">';
        for(var i=0;i<currentSegments.length;i++){
            html+='<li class="wg-task-item"><span class="wg-task-num" style="background:'+COLORS[i%COLORS.length][0]+'">'+(i+1)+'</span><span class="wg-task-text">'+escHtml(currentSegments[i])+'</span></li>';
        }
        html+='</ul>';
        panel.innerHTML=html;
    }

    function createParticles(){
        var el=document.getElementById('particles');
        el.innerHTML='';
        for(var i=0;i<20;i++){
            var dot=document.createElement('div');
            dot.className='wg-particle';
            var angle=Math.random()*360;
            var dist=140+Math.random()*30;
            var x=160+Math.cos(angle*Math.PI/180)*dist;
            var y=160+Math.sin(angle*Math.PI/180)*dist;
            dot.style.left=x+'px';
            dot.style.top=y+'px';
            dot.style.background=COLORS[Math.floor(Math.random()*COLORS.length)][0];
            dot.style.setProperty('--dx',(Math.random()-.5)*40+'px');
            dot.style.setProperty('--dy',(Math.random()-.5)*40+'px');
            dot.style.animationDelay=(Math.random()*1.5)+'s';
            el.appendChild(dot);
        }
    }

    function fireConfetti(){
        var c=document.getElementById('confetti-canvas');
        c.width=window.innerWidth;c.height=window.innerHeight;
        var ctx=c.getContext('2d');var particles=[];
        var cols=['#e53935','#fb8c00','#fdd835','#43a047','#1e88e5','#8e24aa','#f06292','#ffd700'];
        for(var i=0;i<80;i++){
            particles.push({x:c.width/2,y:c.height/2,vx:(Math.random()-.5)*14,vy:Math.random()*-12-4,w:Math.random()*8+4,h:Math.random()*6+2,color:cols[Math.floor(Math.random()*cols.length)],rot:Math.random()*360,rv:(Math.random()-.5)*12,life:1});
        }
        var frame=0;
        function draw(){
            ctx.clearRect(0,0,c.width,c.height);var alive=false;
            particles.forEach(function(p){if(p.life<=0)return;alive=true;p.x+=p.vx;p.vy+=.3;p.y+=p.vy;p.rot+=p.rv;p.life-=.012;ctx.save();ctx.translate(p.x,p.y);ctx.rotate(p.rot*Math.PI/180);ctx.globalAlpha=Math.max(0,p.life);ctx.fillStyle=p.color;ctx.fillRect(-p.w/2,-p.h/2,p.w,p.h);ctx.restore()});
            frame++;if(alive&&frame<200)requestAnimationFrame(draw);else ctx.clearRect(0,0,c.width,c.height);
        }
        draw();
    }

    window.spinWheel=function(){
        if(spinning) return;
        spinning=true;
        document.getElementById('spin-btn').style.display='none';
        document.getElementById('wheel-wrapper').classList.add('spinning');

        var n=currentSegments.length;
        var segAngle=360/n;
        var winIdx=Math.floor(Math.random()*n);
        var targetAngle=360*5+(360-winIdx*segAngle-segAngle/2);

        var canvas=document.getElementById('wheel-canvas');
        canvas.style.transition='transform 5s cubic-bezier(.17,.67,.05,.99)';
        canvas.style.transform='rotate('+targetAngle+'deg)';
        currentAngle=targetAngle%360;

        spinTimer=setTimeout(function(){
            spinTimer=null;
            document.getElementById('wheel-wrapper').classList.remove('spinning');
            var result=currentSegments[winIdx];
            var rd=document.getElementById('result-display');
            rd.style.display='block';
            rd.innerHTML='<div class="wg-result-text">'+escHtml(result)+'</div>';
            document.getElementById('next-btn').style.display='inline-flex';
            fireConfetti();
        },5300);
    };

    window.nextTurn=function(){
        turn++;
        if(turn>=players.length){turn=0;round++;}
        if(round>6&&!IS_PREMIUM){
            document.getElementById('result-display').innerHTML=
                '<p style="color:var(--gold);margin:16px 0">免費版最多 6 回合，升級 Premium 解鎖無限回合與更刺激的任務！</p>'+
                '<a href="{{ route('premium.index') }}" class="btn btn-outline-gold">升級 Premium</a>';
            document.getElementById('next-btn').style.display='none';
            return;
        }
        showTurn();
    };

    window.resetGame=function(){
        if(spinTimer){clearTimeout(spinTimer);spinTimer=null;}
        spinning=false;
        turn=0;round=0;
        showPhase('lobby-phase');
    };

    // Init — render lobby
    renderLobby();
})();
</script>
@endsection
