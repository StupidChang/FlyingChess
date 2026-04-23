@extends('layouts.app')
@section('title', '情侶撲克牌 — 線下派對遊戲 — 情侶飛行棋')
@section('meta_description', '2-6 人同機暢玩情侶撲克牌！抽牌比大小配對，每回合指定親密任務，輕鬆→中等→激烈三階段升溫。')
@section('canonical', route('card-game.show'))

@section('styles')
<style>
/* ---- Card Game — Single-Device ---- */
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

.cg-card-back{width:80px;height:112px;background:linear-gradient(135deg,#1565c0,#0d47a1);border-radius:8px;border:3px solid var(--gold);display:flex;align-items:center;justify-content:center;font-size:2rem;box-shadow:0 4px 16px rgba(0,0,0,.3);transition:transform .3s}
.cg-card-back.flipping{animation:cardFlipOut .25s ease-in forwards}
@keyframes cardFlipOut{to{transform:rotateY(90deg);opacity:0}}

/* Poker card */
.poker-card{width:80px;height:112px;background:#fff;border-radius:8px;border:2px solid #ddd;display:inline-flex;flex-direction:column;align-items:center;justify-content:center;font-weight:700;box-shadow:0 2px 8px rgba(0,0,0,.2);animation:cardFlipIn .3s ease-out}
.poker-card.red{color:#d00}.poker-card.black{color:#111}
.poker-card .rank{font-size:1.1rem}.poker-card .suit{font-size:1.6rem}
.poker-card-sm{width:48px;height:68px;background:#fff;border-radius:6px;border:1.5px solid #ddd;display:inline-flex;flex-direction:column;align-items:center;justify-content:center;font-weight:700;box-shadow:0 1px 4px rgba(0,0,0,.15);flex-shrink:0}
.poker-card-sm.red{color:#d00}.poker-card-sm.black{color:#111}
.poker-card-sm .rank{font-size:.75rem}.poker-card-sm .suit{font-size:1rem}
@keyframes cardFlipIn{from{transform:rotateY(-90deg);opacity:0}to{transform:rotateY(0);opacity:1}}

/* Results */
.cg-results{animation:fadeIn .3s ease-out}
@keyframes fadeIn{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:none}}
.cg-ranking-cols{display:flex;gap:20px;flex-wrap:wrap;justify-content:center;margin-bottom:20px}
.cg-ranking-column{flex:1;min-width:140px;max-width:280px}
.cg-ranking-item{display:flex;align-items:center;gap:10px;padding:6px 0}
.cg-rank-num{font-size:.85rem;font-weight:700;color:var(--gold);min-width:20px}
.cg-activity-display{background:var(--card-bg,rgba(255,255,255,.05));border:1px solid var(--border);border-radius:12px;padding:20px;margin:16px 0}
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

    {{-- Drawing Phase — Simultaneous Reveal --}}
    <div id="drawing-phase" style="display:none">
        <div class="cg-round-badge" id="round-badge"></div>
        <div class="cg-deal-area" id="deal-area">
            {{-- Card slots rendered by JS --}}
        </div>
        <div class="cg-action-btns">
            <button class="btn btn-gold btn-xl" id="deal-btn" onclick="dealCards()">🃏 發牌</button>
            <button class="btn btn-gold btn-xl" id="flip-btn" style="display:none" onclick="flipAllCards()">翻牌！</button>
            <button class="btn btn-gold btn-xl" id="results-btn" style="display:none" onclick="showResults()">查看結果</button>
        </div>
    </div>

    {{-- Results Phase --}}
    <div id="results-phase" style="display:none" class="cg-results">
        <div class="cg-round-badge" id="result-round-badge"></div>

        <div class="cg-ranking-cols">
            <div class="cg-ranking-column">
                <h3 style="text-align:center;color:#2563eb;margin-bottom:8px">男生排名</h3>
                <div id="male-ranking"></div>
            </div>
            <div class="cg-ranking-column">
                <h3 style="text-align:center;color:#db2777;margin-bottom:8px">女生排名</h3>
                <div id="female-ranking"></div>
            </div>
        </div>

        <div id="pairings-display" class="cg-activity-display"></div>

        <div style="text-align:center;margin-top:20px;display:flex;gap:12px;justify-content:center;flex-wrap:wrap">
            <button class="btn btn-gold btn-xl" onclick="nextRound()" id="next-round-btn">下一回合</button>
            <button class="btn btn-outline" onclick="resetGame()">重新開始</button>
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
    /* ---- Config ---- */
    var IS_PREMIUM = {{ $isPremium ? 'true' : 'false' }};
    var ACTIVITIES = @json($activities);
    var SUITS = ['clubs','diamonds','hearts','spades'];
    var RANKS = ['2','3','4','5','6','7','8','9','10','J','Q','K','A'];
    var SUIT_SYMBOLS = {clubs:'\u2663',diamonds:'\u2666',hearts:'\u2665',spades:'\u2660'};

    /* ---- State ---- */
    var players = [];
    var round = 0;
    var usedCards = [];
    var cardsDealt = false; // cards dealt but not flipped

    /* ---- Helpers ---- */
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
    function hideAll(){
        ['setup-phase','drawing-phase','results-phase'].forEach(function(id){
            document.getElementById(id).style.display='none';
        });
    }

    /* ---- Setup ---- */
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

    /* ---- Drawing Phase: Simultaneous ---- */
    function startDrawingPhase(){
        hideAll();
        cardsDealt=false;
        document.getElementById('drawing-phase').style.display='block';
        document.getElementById('round-badge').innerHTML='第 '+round+' 回合 '+intensityTag();

        // Reset buttons
        document.getElementById('deal-btn').style.display='inline-flex';
        document.getElementById('flip-btn').style.display='none';
        document.getElementById('results-btn').style.display='none';

        // Show empty card slots with player names
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
                '<div class="card-placeholder" style="width:80px;height:112px;border:2px dashed var(--border);border-radius:8px;display:flex;align-items:center;justify-content:center;color:var(--text-dim);font-size:.8rem">等待發牌</div>';
            area.appendChild(slot);
        });
    }

    window.dealCards=function(){
        if(cardsDealt) return;
        cardsDealt=true;
        document.getElementById('deal-btn').style.display='none';

        // Deal cards to all players (track this round's keys to prevent duplicates on reshuffle)
        var roundKeys=[];
        players.forEach(function(p,i){
            p.card=pickCard(roundKeys);
            roundKeys.push(p.card.key);
            var slot=document.getElementById('card-slot-'+i);
            var placeholder=slot.querySelector('.card-placeholder');
            if(placeholder) placeholder.remove();
            // Show face-down card
            var back=document.createElement('div');
            back.className='cg-card-back';
            back.id='card-back-'+i;
            back.textContent='🃏';
            slot.appendChild(back);
        });

        // Show flip button after a brief pause
        setTimeout(function(){
            document.getElementById('flip-btn').style.display='inline-flex';
        },400);
    };

    window.flipAllCards=function(){
        document.getElementById('flip-btn').style.display='none';
        var flipped=0;

        players.forEach(function(p,i){
            var back=document.getElementById('card-back-'+i);
            if(!back) return;
            back.classList.add('flipping');
        });

        // After flip-out animation, replace with actual cards
        setTimeout(function(){
            players.forEach(function(p,i){
                var slot=document.getElementById('card-slot-'+i);
                var back=document.getElementById('card-back-'+i);
                if(back) back.remove();
                // Insert face-up card
                var cardDiv=document.createElement('div');
                cardDiv.innerHTML=makeCard(p.card,false);
                slot.appendChild(cardDiv.firstChild);
            });
            // Show results button after cards are revealed
            setTimeout(function(){
                document.getElementById('results-btn').style.display='inline-flex';
            },500);
        },280);
    };

    /* ---- Results ---- */
    window.showResults=function(){
        hideAll();
        document.getElementById('results-phase').style.display='block';
        document.getElementById('result-round-badge').innerHTML='第 '+round+' 回合結果 '+intensityTag();

        var males=[],females=[];
        players.forEach(function(p,i){
            if(!p.card) return;
            var entry={name:p.name,card:p.card,value:cardValue(p.card),idx:i};
            if(p.gender==='male') males.push(entry); else females.push(entry);
        });
        males.sort(function(a,b){return b.value-a.value});
        females.sort(function(a,b){return b.value-a.value});

        // Render rankings
        renderRanking('male-ranking',males);
        renderRanking('female-ranking',females);

        // Pair: highest male with lowest female
        var pairCount=Math.min(males.length,females.length);
        var pairDiv=document.getElementById('pairings-display');
        pairDiv.innerHTML='';

        for(var i=0;i<pairCount;i++){
            var m=males[i],f=females[females.length-1-i];
            var activity=getActivity();
            var item=document.createElement('div');
            item.className='cg-activity-item';
            item.innerHTML='<div class="cg-pairing-label">配對 #'+(i+1)+'</div>'+
                '<div class="cg-pairing-players">'+
                '<div class="cg-pairing-player"><span class="name">'+escHtml(m.name)+' <span class="cg-gender-tag cg-gender-male">男</span></span>'+makeCard(m.card,true)+'</div>'+
                '<div class="cg-pairing-vs">&</div>'+
                '<div class="cg-pairing-player"><span class="name">'+escHtml(f.name)+' <span class="cg-gender-tag cg-gender-female">女</span></span>'+makeCard(f.card,true)+'</div>'+
                '</div>'+
                '<div class="cg-activity-text">'+escHtml(activity)+'</div>';
            pairDiv.appendChild(item);
        }

        // Unpaired
        var unpairedM=males.slice(pairCount);
        var unpairedF=females.slice(0,Math.max(0,females.length-pairCount));
        var allUnpaired=unpairedM.concat(unpairedF);
        if(allUnpaired.length){
            var u=document.createElement('div');
            u.className='cg-activity-item';
            u.innerHTML='<div style="color:var(--text-dim);font-size:.9rem">未配對：'+allUnpaired.map(function(x){return escHtml(x.name)}).join('、')+' (本回合休息)</div>';
            pairDiv.appendChild(u);
        }

        // Premium check
        document.getElementById('upgrade-notice').style.display='none';
        document.getElementById('next-round-btn').style.display='inline-block';
        if(round>=6&&!IS_PREMIUM){
            document.getElementById('next-round-btn').style.display='none';
            document.getElementById('upgrade-notice').style.display='block';
        }
    };

    function renderRanking(containerId,entries){
        var el=document.getElementById(containerId);
        el.innerHTML='';
        entries.forEach(function(e,i){
            var d=document.createElement('div');
            d.className='cg-ranking-item';
            d.innerHTML='<span class="cg-rank-num">#'+(i+1)+'</span>'+makeCard(e.card,true)+'<span style="font-size:.85rem">'+escHtml(e.name)+'</span>';
            el.appendChild(d);
        });
    }

    window.nextRound=function(){
        round++;
        players.forEach(function(p){p.card=null});
        startDrawingPhase();
    };
    window.resetGame=function(){
        hideAll();
        round=0;usedCards=[];
        document.getElementById('setup-phase').style.display='block';
    };
})();
</script>
@endsection
