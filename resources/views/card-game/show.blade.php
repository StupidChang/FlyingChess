@extends('layouts.app')
@section('title', '情侶撲克牌 — 線下派對遊戲 — 情侶飛行棋')
@section('meta_description', '2-6 人同機暢玩情侶撲克牌！抽牌比大小配對，每回合指定親密任務，輕鬆→中等→激烈三階段升溫。')
@section('canonical', route('card-game.show'))

@section('styles')
<style>
.cg-page{max-width:700px;margin:0 auto;padding:20px 16px;min-height:calc(100vh - 56px);position:relative}
.cg-page::before{content:'';position:absolute;inset:0;background:radial-gradient(ellipse at 50% 20%,rgba(var(--glow-rgb,180,60,100),.1) 0%,transparent 70%);animation:hero-glow 6s ease-in-out infinite;pointer-events:none;z-index:0}
.cg-page>*{position:relative;z-index:1}
.cg-title{text-align:center;color:var(--gold);font-size:1.4rem;margin-bottom:4px}
.cg-subtitle{text-align:center;color:var(--text-dim);font-size:.85rem;margin-bottom:20px}

/* Setup */
.cg-setup{background:var(--card-bg,rgba(255,255,255,.06));border:1px solid var(--border);border-radius:12px;padding:20px}
.cg-player-row{display:flex;gap:8px;align-items:center;margin-bottom:10px;flex-wrap:wrap}
.cg-player-row input[type=text]{flex:1;min-width:100px}
.cg-player-row select{width:auto;min-width:60px}
.cg-player-remove{background:none;border:none;color:#e53935;font-size:1.2rem;cursor:pointer;padding:0 4px}
.cg-add-player{margin-bottom:16px}

/* Simultaneous Card Reveal */
.cg-deal-area{display:flex;gap:16px;justify-content:center;flex-wrap:wrap;padding:20px 0}
.cg-card-slot{display:flex;flex-direction:column;align-items:center;gap:8px;min-width:80px}
.cg-card-slot .slot-name{font-size:.85rem;color:var(--text-dim);font-weight:600}
.cg-card-slot .slot-gender{display:inline-block;font-size:.7rem;padding:1px 6px;border-radius:4px;font-weight:600}
.cg-card-slot .slot-gender.male{background:#2563eb;color:#fff}
.cg-card-slot .slot-gender.female{background:#db2777;color:#fff}

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

/* Activity result glow */
.cg-activity-text{position:relative;overflow:hidden}
.cg-activity-text::after{
    content:'';position:absolute;top:0;left:-100%;width:50%;height:100%;
    background:linear-gradient(90deg,transparent,rgba(255,255,255,.1),transparent);
    animation:actShimmer 2.5s 1s ease-in-out infinite;
}
@keyframes actShimmer{0%{left:-100%}100%{left:200%}}

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

/* Inline results */
@keyframes fadeInUp{from{opacity:0;transform:translateY(16px);filter:blur(3px)}to{opacity:1;transform:none;filter:blur(0)}}
.cg-inline-results{animation:fadeInUp .5s cubic-bezier(.34,1.56,.64,1) both;margin-top:16px}
.cg-activity-display{background:var(--card-bg,rgba(255,255,255,.05));border:1px solid var(--border);border-radius:12px;padding:20px;margin:12px 0;position:relative;overflow:hidden}
.cg-activity-display::before{content:'';position:absolute;inset:-4px;border-radius:16px;z-index:-1;background:conic-gradient(from 0deg,rgba(212,160,23,.2),rgba(239,68,68,.15),rgba(168,85,247,.2),rgba(59,130,246,.15),rgba(212,160,23,.2));filter:blur(10px);opacity:.6}
.cg-activity-item{padding:14px 0;border-bottom:1px solid var(--border,rgba(255,255,255,.08));text-align:center}
.cg-activity-item:last-child{border-bottom:none}
.cg-pairing-label{font-size:.8rem;padding:2px 8px;border-radius:4px;display:inline-block;margin-bottom:8px;font-weight:600;background:var(--gold);color:#fff}
.cg-pairing-players{display:flex;align-items:center;justify-content:center;gap:12px;margin-bottom:10px;flex-wrap:wrap}
.cg-pairing-player{display:flex;flex-direction:column;align-items:center;gap:4px}
.cg-pairing-player .name{font-size:.85rem;color:var(--text-dim);font-weight:600}
.cg-pairing-vs{font-size:1.2rem;font-weight:700;color:var(--gold)}
.cg-activity-text{font-size:1.15rem;font-weight:700;color:var(--text-main,#fff);margin-top:8px;padding:8px 16px;background:rgba(255,255,255,.05);border-radius:8px;display:inline-block}
.cg-gender-tag{display:inline-block;font-size:.7rem;padding:1px 6px;border-radius:4px;font-weight:600;vertical-align:middle;margin-left:4px}
.cg-gender-male{background:#2563eb;color:#fff}
.cg-gender-female{background:#db2777;color:#fff}
.cg-intensity-tag{display:inline-block;font-size:.75rem;padding:2px 8px;border-radius:4px;font-weight:600;margin-left:8px}
.cg-intensity-mild{background:#66bb6a;color:#fff}
.cg-intensity-medium{background:#ffa726;color:#fff}
.cg-intensity-intense{background:#ef5350;color:#fff}
.cg-round-badge{text-align:center;color:var(--gold);font-size:1.2rem;margin-bottom:16px}
.cg-action-btns{text-align:center;margin-top:20px;display:flex;gap:12px;justify-content:center;flex-wrap:wrap}
</style>
@endsection

@section('content')
<div class="cg-page">
    <h1 class="cg-title">情侶撲克牌</h1>
    <p class="cg-subtitle">同一台裝置，發牌後同時翻開比大小，男女配對執行親密任務（需至少一男一女）</p>

    {{-- Setup Phase --}}
    <div id="setup-phase" class="cg-setup">
        <h2 style="color:var(--gold);font-size:1.1rem;margin-bottom:12px">設定玩家 (2-6人，需至少一男一女)</h2>
        <div id="players-list">
            <div class="cg-player-row" data-idx="0">
                <input type="text" class="form-control p-name" value="玩家 1" maxlength="12">
                <select class="form-control p-gender">
                    <option value="male">男</option>
                    <option value="female">女</option>
                </select>
            </div>
            <div class="cg-player-row" data-idx="1">
                <input type="text" class="form-control p-name" value="玩家 2" maxlength="12">
                <select class="form-control p-gender">
                    <option value="male">男</option>
                    <option value="female" selected>女</option>
                </select>
            </div>
        </div>
        <button class="btn btn-sm btn-outline cg-add-player" id="add-player-btn" onclick="addPlayer()">+ 新增玩家</button>
        <button class="btn btn-gold btn-full" onclick="startGame()">開始遊戲</button>
    </div>

    {{-- Drawing Phase --}}
    <div id="drawing-phase" style="display:none">
        <div class="cg-round-badge" id="round-badge"></div>
        <div class="cg-deal-area" id="deal-area"></div>
        <div id="inline-results"></div>
        <div class="cg-action-btns" id="action-btns">
            <button class="btn btn-gold btn-xl" id="deal-btn" onclick="dealCards()">🃏 發牌</button>
            <button class="btn btn-gold btn-xl" id="flip-btn" style="display:none" onclick="flipAllCards()">翻牌！</button>
            <button class="btn btn-gold btn-xl" id="next-round-btn" style="display:none" onclick="nextRound()">下一回合</button>
            <button class="btn btn-outline" id="reset-btn" style="display:none" onclick="resetGame()">重新開始</button>
        </div>
        <div id="upgrade-notice" style="display:none;text-align:center;margin-top:12px">
            <p style="color:var(--gold);margin-bottom:8px">免費版最多 6 回合，升級 Premium 解鎖無限回合與更刺激的任務！</p>
            <a href="{{ route('premium.index') }}" class="btn btn-outline-gold">升級 Premium</a>
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
        if(round<=3) return '<span class="cg-intensity-tag cg-intensity-mild">輕鬆</span>';
        if(round<=6) return '<span class="cg-intensity-tag cg-intensity-medium">中等</span>';
        return '<span class="cg-intensity-tag cg-intensity-intense">激烈</span>';
    }

    /* Setup */
    var playerCount=2;
    window.addPlayer=function(){
        if(playerCount>=6) return;
        playerCount++;
        var row=document.createElement('div');
        row.className='cg-player-row';
        row.setAttribute('data-idx',playerCount-1);
        row.innerHTML='<input type="text" class="form-control p-name" value="玩家 '+playerCount+'" maxlength="12">'+
            '<select class="form-control p-gender"><option value="male">男</option><option value="female">女</option></select>'+
            '<button class="cg-player-remove" onclick="removePlayer(this)">✕</button>';
        document.getElementById('players-list').appendChild(row);
        if(playerCount>=6) document.getElementById('add-player-btn').style.display='none';
    };
    window.removePlayer=function(btn){
        btn.closest('.cg-player-row').remove();
        playerCount--;
        document.getElementById('add-player-btn').style.display='inline-block';
    };

    window.startGame=function(){
        var rows=document.querySelectorAll('.cg-player-row');
        players=[];
        rows.forEach(function(r){
            var name=r.querySelector('.p-name').value.trim()||'玩家';
            var gender=r.querySelector('.p-gender').value;
            players.push({name:name,gender:gender,card:null});
        });
        if(players.length<2){alert('至少需要 2 位玩家');return;}
        var hasMale=players.some(function(p){return p.gender==='male'});
        var hasFemale=players.some(function(p){return p.gender==='female'});
        if(!hasMale||!hasFemale){alert('需要至少一位男生和一位女生');return;}
        round=1;usedCards=[];
        startDrawingPhase();
    };

    function startDrawingPhase(){
        document.getElementById('setup-phase').style.display='none';
        document.getElementById('drawing-phase').style.display='block';
        cardsDealt=false;
        document.getElementById('round-badge').innerHTML='第 '+round+' 回合 '+intensityTag();
        document.getElementById('deal-btn').style.display='inline-flex';
        document.getElementById('flip-btn').style.display='none';
        document.getElementById('next-round-btn').style.display='none';
        document.getElementById('reset-btn').style.display='none';
        document.getElementById('upgrade-notice').style.display='none';
        document.getElementById('inline-results').innerHTML='';

        var area=document.getElementById('deal-area');
        area.innerHTML='';
        players.forEach(function(p,i){
            var gClass=p.gender==='male'?'male':'female';
            var gLabel=p.gender==='male'?'男':'女';
            var slot=document.createElement('div');
            slot.className='cg-card-slot';
            slot.id='card-slot-'+i;
            slot.innerHTML=
                '<div class="slot-name">'+escHtml(p.name)+'</div>'+
                '<span class="slot-gender '+gClass+'">'+gLabel+'</span>'+
                '<div class="cg-card-placeholder">等待發牌</div>';
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
        var html='<div class="cg-inline-results"><div class="cg-activity-display">';

        for(var i=0;i<pairCount;i++){
            var m=males[i],f=females[females.length-1-i];
            var activity=getActivity();
            var bigName,smallName;
            if(m.value>=f.value){bigName=m.name;smallName=f.name}
            else{bigName=f.name;smallName=m.name}
            activity=activity.replace(/牌大的/g,bigName).replace(/牌小的/g,smallName);
            html+='<div class="cg-activity-item">'+
                '<div class="cg-activity-text">'+escHtml(activity)+'</div></div>';
        }

        var unpairedM=males.slice(pairCount);
        var unpairedF=females.slice(0,Math.max(0,females.length-pairCount));
        var allUnpaired=unpairedM.concat(unpairedF);
        if(allUnpaired.length){
            html+='<div class="cg-activity-item"><div style="color:var(--text-dim);font-size:.9rem">'+allUnpaired.map(function(x){return escHtml(x.name)}).join('、')+' 本回合休息</div></div>';
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
    };
})();
</script>
@endsection
