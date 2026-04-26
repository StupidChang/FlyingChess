@extends('layouts.app')
@section('title', '國王遊戲 — 情侶派對遊戲 — 情侶飛行棋')
@section('meta_description', '經典國王遊戲線上版！抽號碼牌決定身份，國王下指令，身份揭曉才知道是誰。2-6 人同機暢玩。')
@section('canonical', route('king-game.show'))

@section('styles')
<style>
.kg-page{max-width:600px;margin:0 auto;padding:20px 16px;min-height:calc(100vh - 56px);position:relative;isolation:isolate}
.kg-page::before{content:'';position:absolute;inset:0;background:radial-gradient(ellipse at 50% 20%,rgba(var(--glow-rgb,180,60,100),.1) 0%,transparent 70%);animation:hero-glow 6s ease-in-out infinite;pointer-events:none;z-index:-1}
.kg-page>*{position:relative}
.kg-title{text-align:center;color:var(--gold);font-size:1.4rem;margin-bottom:4px}
.kg-subtitle{text-align:center;color:var(--text-dim);font-size:.85rem;margin-bottom:20px}
.kg-setup{background:var(--card-bg,rgba(255,255,255,.06));border:1px solid var(--border);border-radius:12px;padding:20px}
.kg-player-row{display:flex;gap:8px;align-items:center;margin-bottom:10px;flex-wrap:wrap}
.kg-player-row input[type=text]{flex:1;min-width:100px}
.kg-player-remove{background:none;border:none;color:#e53935;font-size:1.2rem;cursor:pointer;padding:0 4px}
.kg-add-player{margin-bottom:16px}

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

.kg-round-badge{text-align:center;color:var(--gold);font-size:1.1rem;margin-bottom:16px}
.kg-action-btns{text-align:center;margin-top:20px;display:flex;gap:12px;justify-content:center;flex-wrap:wrap}
@keyframes cardDealIn{from{transform:scale(0);opacity:0}to{transform:scale(1);opacity:1}}
.kg-card-scene.dealing{animation:cardDealIn .3s cubic-bezier(.34,1.56,.64,1) both}

/* Inline toast */
.kg-toast{position:fixed;top:20px;left:50%;transform:translateX(-50%);z-index:9999;padding:10px 24px;border-radius:8px;background:#2a0a0f;border:1px solid var(--rose,#e53935);color:#f06080;font-weight:600;font-size:.9rem;animation:kg-toast-in .3s ease-out,kg-toast-out .4s 2.5s ease-in forwards;pointer-events:none}
@keyframes kg-toast-in{from{opacity:0;transform:translateX(-50%) translateY(-20px)}to{opacity:1;transform:translateX(-50%) translateY(0)}}
@keyframes kg-toast-out{to{opacity:0;transform:translateX(-50%) translateY(-20px)}}
</style>
@endsection

@section('content')
<div class="kg-page">
    <h1 class="kg-title">國王遊戲</h1>
    <p class="kg-subtitle">每人點自己的撲克牌偷看身份，抽到 K 的就是國王！</p>

    {{-- Setup Phase --}}
    <div id="setup-phase" class="kg-setup">
        <h2 style="color:var(--gold);font-size:1.1rem;margin-bottom:12px">設定玩家 (3-6人)</h2>
        <div id="players-list">
            <div class="kg-player-row" data-idx="0">
                <input type="text" class="form-control p-name" value="玩家 1" maxlength="12">
            </div>
            <div class="kg-player-row" data-idx="1">
                <input type="text" class="form-control p-name" value="玩家 2" maxlength="12">
            </div>
            <div class="kg-player-row" data-idx="2">
                <input type="text" class="form-control p-name" value="玩家 3" maxlength="12">
            </div>
        </div>
        <button class="btn btn-sm btn-outline kg-add-player" id="add-player-btn" onclick="addPlayer()">+ 新增玩家</button>
        <button class="btn btn-gold btn-full" onclick="startGame()">開始遊戲</button>
    </div>

    {{-- Deal Phase --}}
    <div id="deal-phase" style="display:none">
        <div class="kg-round-badge" id="round-badge"></div>
        <p style="text-align:center;color:var(--text-dim);font-size:.9rem;margin-bottom:12px">每人點自己的牌偷看身份（不要讓別人看到！）</p>
        <div class="kg-card-area" id="card-area"></div>
        <div class="kg-action-btns">
            <button class="btn btn-gold btn-xl" id="next-round-btn" style="display:none" onclick="nextRound()">下一回合</button>
            <button class="btn btn-outline" id="reset-btn" style="display:none" onclick="resetGame()">重新開始</button>
        </div>
        <div id="upgrade-notice" style="display:none;text-align:center;margin-top:12px">
            <p style="color:var(--gold);margin-bottom:8px">免費版最多 6 回合，升級 Premium 解鎖無限回合！</p>
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
    var players=[];
    var round=0;
    var assignments=[];
    var peeked=[];

    function escHtml(s){var d=document.createElement('div');d.appendChild(document.createTextNode(s));return d.innerHTML}
    function shuffle(a){for(var i=a.length-1;i>0;i--){var j=Math.floor(Math.random()*(i+1));var t=a[i];a[i]=a[j];a[j]=t}return a}
    function showToast(msg){
        var old=document.querySelector('.kg-toast');
        if(old) old.remove();
        var t=document.createElement('div');
        t.className='kg-toast';
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
        row.className='kg-player-row';
        row.innerHTML='<input type="text" class="form-control p-name" value="玩家 '+playerCount+'" maxlength="12">'+
            '<button class="kg-player-remove" onclick="removePlayer(this)">✕</button>';
        document.getElementById('players-list').appendChild(row);
        if(playerCount>=6) document.getElementById('add-player-btn').style.display='none';
    };
    window.removePlayer=function(btn){
        btn.closest('.kg-player-row').remove();
        playerCount--;
        document.getElementById('add-player-btn').style.display='inline-block';
    };

    window.startGame=function(){
        var rows=document.querySelectorAll('.kg-player-row');
        players=[];
        rows.forEach(function(r){
            players.push(r.querySelector('.p-name').value.trim()||'玩家');
        });
        if(players.length<3){showToast('國王遊戲至少需要 3 位玩家');return;}
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
        var cards=[{role:'king',rank:'K',suit:kingSuit,isRed:!!redSuits[kingSuit],label:'國王'}];
        for(var i=0;i<count-1;i++){
            var c=pool[i];
            cards.push({role:'number',rank:c.rank,suit:c.suit,isRed:c.isRed,label:c.rank+c.suit});
        }
        return shuffle(cards);
    }

    function dealRound(){
        document.getElementById('setup-phase').style.display='none';
        document.getElementById('deal-phase').style.display='block';
        document.getElementById('round-badge').innerHTML='第 '+round+' 回合';
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
                (isKing?'<span class="center-label">國王</span>':'<span class="center-label">'+a.rank+' '+a.suit+'</span>')+'</div>';

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
