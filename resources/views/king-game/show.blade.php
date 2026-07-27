@extends('layouts.app')
@section('title', __('minigame.king_title') . ' — ' . __('ui.site_name'))
@section('meta_description', __('minigame.king_meta'))
@section('canonical', route('king-game.show'))

@section('styles')
<link rel="stylesheet" href="{{ asset_v('css/minigames.css') }}">
<style>





@media(min-width:500px){
  .mg-card-scene{width:110px;height:154px}
  .mg-card-corner .corner-rank{font-size:.95rem}
  .mg-card-corner .corner-suit{font-size:.85rem}
  .mg-card-center .center-suit{font-size:2.6rem}
  .mg-card-center .center-label{font-size:.9rem}
}

@keyframes cardDealIn{from{transform:scale(0);opacity:0}to{transform:scale(1);opacity:1}}

/* ── Reveal phase: king banner + number legend ─────────────────────── */
.kg-reveal{margin-top:28px}

@keyframes kgKingPop{0%{transform:scale(.5);opacity:0}60%{transform:scale(1.08);opacity:1}100%{transform:scale(1);opacity:1}}
@keyframes kgGoldPulse{0%,100%{text-shadow:0 0 0 rgba(217,164,65,0)}50%{text-shadow:0 0 20px rgba(217,164,65,.7)}}
@keyframes kgLegendIn{from{opacity:0;transform:translateY(6px)}to{opacity:1;transform:translateY(0)}}

.kg-king-banner{text-align:center;margin-bottom:22px;animation:kgKingPop .5s cubic-bezier(.34,1.56,.64,1) both}
.kg-king-banner .kg-crown-icon{font-size:2.4rem;line-height:1;margin-bottom:4px;animation:kgGoldPulse 1.8s ease-in-out .5s 2}
.kg-king-banner .kg-king-label{font-size:.85rem;color:var(--text-dim,#9aa1b5);letter-spacing:.5px;margin-bottom:2px}
.kg-king-banner .kg-king-name{font-size:1.6rem;font-weight:800;color:var(--gold,#d9a441)}

.kg-name-badge{display:inline-block;background:rgba(217,164,65,.16);color:var(--gold,#d9a441);font-weight:800;padding:2px 10px;border-radius:6px;margin:0 2px;white-space:nowrap}

.kg-legend{display:flex;gap:8px;flex-wrap:wrap;justify-content:center;margin-top:16px;animation:kgLegendIn .4s .35s ease-out both}
.kg-legend-chip{display:inline-flex;align-items:center;gap:5px;background:var(--surface2,#1d2130);border:1px solid var(--border,#2a2f42);border-radius:999px;padding:4px 12px;font-size:.8rem;color:var(--text-dim,#9aa1b5)}
.kg-legend-chip b{color:var(--text,#e9ebf2);font-weight:800}
.kg-legend-chip.kg-legend-king{border-color:var(--gold,#d9a441);color:var(--gold,#d9a441)}

@media(prefers-reduced-motion:reduce){
  .mg-card-scene.dealing,.kg-king-banner,.kg-king-banner .kg-crown-icon,.kg-legend{animation:none !important}
}
/* 集體翻牌:確認按鈕與倒數 */
.kg-agree{display:flex;justify-content:center;margin:18px 0 4px}
.kg-agree .btn{min-width:min(200px,60vw)}

.kg-countdown{display:flex;flex-direction:column;align-items:center;
    justify-content:center;min-height:104px;margin:10px 0 2px}
.kg-countdown-num{font-size:clamp(2.6rem,12vw,4rem);font-weight:900;line-height:1;
    color:var(--gold);text-shadow:0 4px 18px rgba(217,164,65,.35);
    animation:kg-count-pop .9s ease-out}
.kg-countdown-num.go{font-size:clamp(1.6rem,7vw,2.4rem);letter-spacing:.06em}
@keyframes kg-count-pop{
    0%  {transform:scale(.5);opacity:0}
    35% {transform:scale(1.12);opacity:1}
    100%{transform:scale(1);opacity:1}
}
@media (prefers-reduced-motion: reduce){
    .kg-countdown-num{animation:none}
}
</style>

@endsection

@section('content')
<div class="mg-page mg-page--md mg-page--center" id="mg-page-root">
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
        <p id="peek-tip" style="text-align:center;color:var(--text-dim);font-size:.9rem;margin-bottom:12px">{{ __('minigame.king_agree_tip') }}</p>
        <div class="mg-deal-area" id="card-area"></div>

        {{-- 集體翻牌:先確認規則講好,倒數後所有牌同時翻開。
             改成集體翻牌的理由:國王遊戲的重點是「先講好懲罰、再看誰是國王」,
             一個一個偷看會讓先看到的人有資訊優勢。 --}}
        <div class="kg-agree" id="kg-agree">
            <button type="button" class="btn btn-gold btn-xl" id="kg-agree-btn" onclick="confirmRules()">
                {{ __('minigame.king_agree_btn') }}
            </button>
        </div>

        <div class="kg-countdown" id="kg-countdown" hidden aria-live="assertive">
            <span class="kg-countdown-num" id="kg-countdown-num"></span>
        </div>

        {{-- Reveal: 只顯示誰是國王與號碼對照,指令由玩家自己講好 --}}
        <div id="reveal-phase" class="kg-reveal" style="display:none">
            <div class="kg-king-banner" id="king-banner">
                <div class="kg-crown-icon" aria-hidden="true">👑</div>
                <div class="kg-king-label">{{ __('minigame.king_is_king') }}</div>
                <div class="kg-king-name" id="king-banner-name"></div>
            </div>
            <div class="kg-legend" id="kg-legend"></div>
        </div>

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

@endsection

@section('scripts')
<script>
(function(){
    var IS_PREMIUM = {{ $isPremium ? 'true' : 'false' }};
    var players=[];
    var round=0;
    var assignments=[];
    var peeked=[];
    var currentReveal=null;

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
        // Build a mini deck: 1 King card + (count-1) number cards.
        // Number cards use a sequential 1..(count-1) game number (not a random poker rank) so that
        // this number can later be matched 1:1 against a player name in the legend.
        // Suit is kept purely as visual card-suit flavor.
        var allSuits=['♠','♥','♦','♣'];
        var redSuits={'♥':true,'♦':true};
        var kingSuit=allSuits[Math.floor(Math.random()*4)];
        var cards=[{role:'king',rank:'K',suit:kingSuit,isRed:!!redSuits[kingSuit],label:@json(__('minigame.king_role_king')),number:null}];

        var numbers=[];
        for(var n=1;n<=count-1;n++) numbers.push(n);
        shuffle(numbers);
        for(var i=0;i<numbers.length;i++){
            var suit=allSuits[Math.floor(Math.random()*4)];
            var isRed=!!redSuits[suit];
            cards.push({role:'number',rank:String(numbers[i]),suit:suit,isRed:isRed,label:String(numbers[i]),number:numbers[i]});
        }
        return shuffle(cards);
    }



    function nameBadge(a){
        return '<span class="kg-name-badge">'+escHtml(a.number+'號 '+a.name)+'</span>';
    }


    function renderLegend(kingName,numberAssignments){
        var html='<span class="kg-legend-chip kg-legend-king">👑 '+escHtml(kingName)+'</span>';
        numberAssignments.forEach(function(a){
            html+='<span class="kg-legend-chip"><b>'+a.number+'</b> '+escHtml(a.name)+'</span>';
        });
        return html;
    }

    /* 揭曉內容只有「誰是國王」與「誰是幾號」。
       指令由玩家自己在翻牌前講好 —— 所以不再自動產生任何任務文字。 */
    function buildReveal(){
        var king=assignments.filter(function(a){return a.role==='king'})[0];
        var numberAssignments=assignments.filter(function(a){return a.role==='number'});
        currentReveal={
            kingName:king.name,
            legendHtml:renderLegend(king.name,numberAssignments)
        };
    }

    function showReveal(){
        if(!currentReveal) return;
        document.getElementById('king-banner-name').textContent=currentReveal.kingName;
        document.getElementById('kg-legend').innerHTML=currentReveal.legendHtml;
        var tip=document.getElementById('peek-tip');
        if(tip) tip.style.display='none';
        var panel=document.getElementById('reveal-phase');
        panel.style.display='block';
        var reduceMotion=window.matchMedia&&window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        panel.scrollIntoView({behavior:reduceMotion?'auto':'smooth',block:'start'});
    }

    function dealRound(){
        document.getElementById('setup-phase').style.display='none';
        document.getElementById('deal-phase').style.display='block';
        var roundLabel = @json(__('minigame.king_round_n', ['n' => '__N__'])).replace('__N__', round);
        document.getElementById('round-badge').innerHTML=escHtml(roundLabel);
        document.getElementById('next-round-btn').style.display='none';
        document.getElementById('reset-btn').style.display='none';
        document.getElementById('upgrade-notice').style.display='none';
        document.getElementById('reveal-phase').style.display='none';
        var tip=document.getElementById('peek-tip');
        if(tip) tip.style.display='';
        // 每一輪都要把確認按鈕與倒數還原
        document.getElementById('kg-agree').style.display='flex';
        document.getElementById('kg-countdown').hidden=true;
        flipping=false;
        peeked=[];

        var deck=buildDeck(players.length);

        assignments=[];
        for(var i=0;i<players.length;i++){
            assignments.push({name:players[i],role:deck[i].role,rank:deck[i].rank,suit:deck[i].suit,isRed:deck[i].isRed,label:deck[i].label,number:deck[i].number});
        }
        buildReveal();

        var area=document.getElementById('card-area');
        area.innerHTML='';
        players.forEach(function(name,i){
            var a=assignments[i];
            var isKing=a.role==='king';
            // 配色改用共用元件的經典撲克規則:黑桃梅花黑、紅心方塊紅。
            // 國王牌額外加 is-king 疊一層金色。
            var cls=(a.isRed?'red':'black')+(isKing?' is-king':'');

            // King card keeps the poker-suit motif front and center; number cards flip the
            // emphasis so the player's game number is the dominant glyph (it's the piece of
            // information they actually need to remember), with the suit demoted to flavor.
            // 號碼牌就是標準撲克牌:大花色 + rank —— 玩家的號碼就是牌面數字,
            // 不必再把數字放大成主視覺(那反而不像撲克牌)。
            var centerHtml=isKing
                ? '<span class="center-suit">'+a.suit+'</span><span class="center-label">'+escHtml(@json(__('minigame.king_role_king')))+'</span>'
                : '<span class="center-suit">'+a.suit+'</span><span class="center-rank">'+a.rank+'</span>';
            var frontHtml=
                '<div class="mg-card-corner mg-card-corner-tl"><span class="corner-rank">'+a.rank+'</span><span class="corner-suit">'+a.suit+'</span></div>'+
                '<div class="mg-card-corner mg-card-corner-br"><span class="corner-rank">'+a.rank+'</span><span class="corner-suit">'+a.suit+'</span></div>'+
                '<div class="mg-card-center">'+centerHtml+'</div>';

            var backSuit=['♠','♥','♦','♣'][i%4];
            var slot=document.createElement('div');
            slot.className='mg-card-slot';
            slot.innerHTML='<div class="slot-name">'+escHtml(name)+'</div>'+
                '<div class="mg-card-scene dealing" style="animation-delay:'+(i*100)+'ms">'+
                '<div class="mg-card-inner" id="king-card-'+i+'">'+
                '<div class="mg-card-face mg-card-back"><div class="mg-card-back-icon">'+backSuit+'</div></div>'+
                '<div class="mg-card-face mg-card-front '+cls+'">'+frontHtml+'</div>'+
                '</div></div>';
            area.appendChild(slot);
        });
    }

    /* 集體翻牌:確認規則 → 倒數 → 所有牌同時翻開 → 揭曉國王與指令。
       不再逐張偷看 —— 先翻到的人會有資訊優勢,也違背「先講好懲罰」的玩法。 */
    var flipping=false;

    window.confirmRules=function(){
        if(flipping) return;
        flipping=true;

        document.getElementById('kg-agree').style.display='none';
        var tip=document.getElementById('peek-tip');
        if(tip) tip.style.display='none';

        var box=document.getElementById('kg-countdown');
        var num=document.getElementById('kg-countdown-num');
        box.hidden=false;

        var reduce = window.matchMedia
            && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        var step = reduce ? 200 : 900;
        var seq=['3','2','1'];
        var i=0;

        function tick(){
            if(i<seq.length){
                num.className='kg-countdown-num';
                num.textContent=seq[i];
                // 重新觸發動畫
                void num.offsetWidth;
                i++;
                setTimeout(tick, step);
                return;
            }
            num.className='kg-countdown-num go';
            num.textContent=@json(__('minigame.king_flip_now'));
            flipAll();
        }
        tick();
    };

    function flipAll(){
        // 同時翻開,不做逐張延遲 —— 「一起翻」才公平
        for(var i=0;i<players.length;i++){
            var inner=document.getElementById('king-card-'+i);
            if(inner) inner.classList.add('flipped');
        }
        var reduce = window.matchMedia
            && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        setTimeout(function(){
            document.getElementById('kg-countdown').hidden=true;
            showReveal();
            document.getElementById('reset-btn').style.display='inline-flex';
            if(round>=6&&!IS_PREMIUM){
                document.getElementById('upgrade-notice').style.display='block';
            } else {
                document.getElementById('next-round-btn').style.display='inline-flex';
            }
            flipping=false;
        }, reduce ? 120 : 700);
    }

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
