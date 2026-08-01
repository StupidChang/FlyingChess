{{--
  撲克牌遊戲(合併頁)—— 情侶撲克牌 / 國王遊戲

  為什麼合併:兩個遊戲的流程完全一樣 ——
    設定玩家 → 發牌 → 一起翻牌 → 揭曉 → 下一回合
  差別只在「揭曉那一步」:撲克牌是男女配對比大小 + 任務,國王是抽出 K + 號碼對照。
  牌的呈現已統一為 minigames.css 的 mg-card-* 共用元件。

  SEO 處理:兩個 URL 都保留(/card-game 與 /king-game),各自帶自己的
  title / description / canonical,進站時預設對應的模式。模式切換只改畫面與
  history.replaceState,不做 301 —— 兩組關鍵字的入口都不會消失。

  $mode: 'card' | 'king'  由 controller 決定預設模式
--}}
@extends('layouts.app')

@php
    $isKing = ($mode ?? 'card') === 'king';
@endphp

{{-- *_seo_title 只用在 meta;下面的 H1 與分頁按鈕仍用 *_title。 --}}
@section('title', ($isKing ? __('minigame.king_seo_title') : __('minigame.card_seo_title')) . ' — ' . __('ui.site_name'))
@section('meta_description', $isKing ? __('minigame.king_meta') : __('minigame.card_meta'))
@section('canonical', $isKing ? route('king-game.show') : route('card-game.show'))

{{-- 同一個 view 服務兩條路由,schema 也要跟著 $isKing 分岔,否則國王遊戲頁會
     宣告成撲克牌,連人數下限(3 vs 2)都是錯的。 --}}
@section('schema')
    @include('partials.game-schema', [
        'gameName' => $isKing ? __('minigame.king_title') : __('minigame.card_title'),
        'gameDescription' => $isKing ? __('games.desc_king') : __('games.desc_card'),
        'gamePath' => $isKing ? 'king-game' : 'card-game',
        'minPlayers' => $isKing ? 3 : 2,
        'maxPlayers' => 6,
    ])
    @include('partials.game-faq-schema', ['faqKey' => $isKing ? 'king-game' : 'card-game'])
@endsection

@section('faq')
    @include('partials.game-faq', ['faqKey' => $isKing ? 'king-game' : 'card-game'])
@endsection

@section('styles')
{{-- 每個小遊戲頁都要自己引入 minigames.css(mg-page / mg-setup / mg-card-* 都在裡面) --}}
<link rel="stylesheet" href="{{ asset_v('css/minigames.css') }}">
<style>
/* 模式切換頁籤 */
.cm-modes{display:flex;gap:8px;justify-content:center;margin:4px 0 20px;flex-wrap:wrap}
.cm-mode{padding:8px 18px;border-radius:999px;cursor:pointer;font-size:.88rem;font-weight:700;
    background:var(--surface);color:var(--text-dim);
    border:1px solid var(--border);transition:color .15s,border-color .15s,background .15s}
.cm-mode:hover{color:var(--text)}
.cm-mode[aria-selected="true"]{background:var(--surface2);color:var(--gold);border-color:var(--gold)}
.cm-mode:focus-visible{outline:none;box-shadow:0 0 0 3px rgba(var(--glow-rgb),.28)}

.cm-tip{text-align:center;color:var(--text-dim);font-size:.9rem;margin:0 0 12px}

/* 國王模式的倒數 */
.cm-countdown{display:flex;align-items:center;justify-content:center;min-height:104px;margin:10px 0 2px}
.cm-countdown-num{font-size:clamp(2.6rem,12vw,4rem);font-weight:900;line-height:1;
    color:var(--gold);text-shadow:0 4px 18px rgba(217,164,65,.35);
    animation:cmCountPop .9s ease-out}
.cm-countdown-num.go{font-size:clamp(1.6rem,7vw,2.4rem);letter-spacing:.06em}
@keyframes cmCountPop{
    0%{transform:scale(.5);opacity:0}
    35%{transform:scale(1.12);opacity:1}
    100%{transform:scale(1);opacity:1}
}

/* 國王揭曉 */
.cm-king-reveal{margin-top:24px}
.cm-king-banner{text-align:center;margin-bottom:20px;animation:cmKingPop .5s cubic-bezier(.34,1.56,.64,1) both}
.cm-crown{font-size:2.4rem;line-height:1;margin-bottom:4px}
.cm-king-label{font-size:.85rem;color:var(--text-dim);letter-spacing:.5px}
.cm-king-name{font-size:1.6rem;font-weight:800;color:var(--gold)}
@keyframes cmKingPop{from{opacity:0;transform:scale(.8)}to{opacity:1;transform:none}}
.cm-legend{display:flex;gap:8px;justify-content:center;flex-wrap:wrap}
.cm-legend-chip{padding:5px 12px;border-radius:999px;font-size:.82rem;
    border:1px solid var(--border);background:var(--surface);color:var(--text-dim)}
.cm-legend-chip b{color:var(--text);margin-right:3px}
.cm-legend-chip.is-king{border-color:var(--gold);color:var(--gold)}

/* 翻牌火花 */
.cm-sparkle{position:absolute;width:4px;height:4px;border-radius:50%;pointer-events:none;z-index:20}
@keyframes cmSparkleOut{
    0%{opacity:1;transform:translate(0,0) scale(1)}
    100%{opacity:0;transform:translate(var(--sx),var(--sy)) scale(0)}
}
.cm-sparkle.animate{animation:cmSparkleOut .7s ease-out forwards}

/* 性別選單只有撲克牌模式需要 */
body[data-cm-mode="king"] .p-gender{display:none}

@media (prefers-reduced-motion: reduce){
    .cm-countdown-num,.cm-king-banner{animation:none}
    .cm-sparkle{display:none}
}
</style>
@endsection

@section('content')
<div class="mg-page mg-page--lg mg-page--center" id="mg-page-root">
    <h1 class="mg-title" id="cm-title">{{ $isKing ? __('minigame.king_title') : __('minigame.card_title') }}</h1>
    <p class="mg-subtitle" id="cm-subtitle">{{ $isKing ? __('minigame.king_subtitle') : __('minigame.card_subtitle_long') }}</p>

    {{-- 模式切換 --}}
    <div class="cm-modes" role="tablist" aria-label="{{ __('minigame.cm_mode_label') }}">
        <button type="button" class="cm-mode" role="tab" data-mode="card"
                aria-selected="{{ $isKing ? 'false' : 'true' }}">{{ __('minigame.card_title') }}</button>
        <button type="button" class="cm-mode" role="tab" data-mode="king"
                aria-selected="{{ $isKing ? 'true' : 'false' }}">{{ __('minigame.king_title') }}</button>
    </div>

    {{-- 設定玩家 --}}
    <div id="setup-phase" class="mg-setup">
        <h2 class="mg-setup-heading" id="cm-setup-heading"></h2>
        <div id="players-list">
            @for ($i = 1; $i <= 3; $i++)
                <div class="mg-player-row" data-idx="{{ $i - 1 }}">
                    <input type="text" class="form-control p-name"
                           value="{{ __('minigame.player_default', ['n' => $i]) }}" maxlength="12">
                    <select class="form-control p-gender">
                        <option value="male" @if($i === 1) selected @endif>{{ __('minigame.card_male') }}</option>
                        <option value="female" @if($i !== 1) selected @endif>{{ __('minigame.card_female') }}</option>
                    </select>
                </div>
            @endfor
        </div>
        <button class="btn btn-sm btn-outline mg-add-player" id="add-player-btn" onclick="addPlayer()">{{ __('minigame.add_player') }}</button>
        @include('partials.escalate-toggle')
        <button class="btn btn-gold btn-full" onclick="startGame()">{{ __('minigame.start_game') }}</button>
    </div>

    {{-- 發牌 / 翻牌 --}}
    <div id="drawing-phase" style="display:none">
        <div class="mg-round-badge" id="round-badge"></div>
        <p class="cm-tip" id="cm-tip"></p>

        <div class="mg-deal-area" id="deal-area"></div>

        <div class="cm-countdown" id="cm-countdown" hidden aria-live="assertive">
            <span class="cm-countdown-num" id="cm-countdown-num"></span>
        </div>

        {{-- 國王模式的揭曉 --}}
        <div class="cm-king-reveal" id="king-reveal" style="display:none">
            <div class="cm-king-banner">
                <div class="cm-crown" aria-hidden="true">👑</div>
                <div class="cm-king-label">{{ __('minigame.king_is_king') }}</div>
                <div class="cm-king-name" id="king-name"></div>
            </div>
            <div class="cm-legend" id="king-legend"></div>
        </div>

        {{-- 撲克牌模式的揭曉 --}}
        <div id="inline-results"></div>

        <div class="mg-action-btns" id="action-btns">
            <button class="btn btn-gold btn-xl" id="deal-btn" onclick="dealCards()">{{ __('minigame.card_deal') }}</button>
            <button class="btn btn-gold btn-xl" id="flip-btn" style="display:none" onclick="flipAllCards()">{{ __('minigame.card_flip') }}</button>
            <button class="btn btn-gold btn-xl" id="next-round-btn" style="display:none" onclick="nextRound()">{{ __('minigame.card_next_round') }}</button>
            <button class="btn btn-outline" id="reset-btn" style="display:none" onclick="resetGame()">{{ __('minigame.reset_game') }}</button>
        </div>

        {{-- 擋下來的當下要同時給兩條路:看廣告(現在就能繼續)與升級(一勞永逸)。
             只給升級的話,不想付錢的人就直接離開了,連廣告收入都沒有。 --}}
        <div id="upgrade-notice" style="display:none;text-align:center;margin-top:12px">
            <p style="color:var(--gold);margin-bottom:8px" id="cm-gate-text"></p>
            <div class="mg-gate-actions">
                <button type="button" class="btn btn-gold"
                        onclick="window.rewardedUnlockOpen && rewardedUnlockOpen()">
                    {{ __('minigame.rewarded_cta', ['minutes' => \App\Support\PremiumAccess::rewardedMinutes()]) }}
                </button>
                <a href="{{ route('premium.index') }}" class="btn btn-outline-gold">{{ __('minigame.go_premium') }}</a>
            </div>
        </div>
    </div>
</div>
    {{-- 常駐的「看廣告解鎖」提示。刻意不做成彈窗:一群人圍著一台裝置玩的時候,
         遊戲中跳出任何東西毀掉的是整場氣氛,不只是一個人的體驗。 --}}
    @include('partials.rewarded-unlock')
@endsection

@section('scripts')
{{-- 玩家頭像:自己盯著玩家列補上挑選器,各遊戲不用改自己的產生邏輯 --}}
<script src="{{ asset_v('js/player-avatar.js') }}"></script>
<script src="{{ asset_v('js/escalation.js') }}"></script>
<script>
(function(){
    var IS_PREMIUM = {{ $isPremium ? 'true' : 'false' }};
    var ACTIVITIES = @json($activities);
    /* 由輕到重,而且只留真的拿得到的等級 —— 沒付費也沒看廣告的話 intense
       根本不在 ACTIVITIES 裡,升溫就停在拿得到的最高一級。 */
    var TIER_ORDER = ['mild','mild_plus','medium','medium_plus','intense'].filter(function(t){
        return ACTIVITIES[t] && ACTIVITIES[t].length;
    });
    var escalate = false;
    var MODE = @json($isKing ? 'king' : 'card');

    var URLS = {
        card: @json(route('card-game.show')),
        king: @json(route('king-game.show'))
    };
    var T = {
        card: {
            title:    @json(__('minigame.card_title')),
            subtitle: @json(__('minigame.card_subtitle_long')),
            setup:    @json(__('minigame.card_setup')),
            tip:      '',
            gate:     @json(__('minigame.card_premium_gate')),
            round:    @json(__('minigame.card_round_n', ['n' => '__N__']))
        },
        king: {
            title:    @json(__('minigame.king_title')),
            subtitle: @json(__('minigame.king_subtitle')),
            setup:    @json(__('minigame.king_setup_players')),
            tip:      @json(__('minigame.king_agree_tip')),
            gate:     @json(__('minigame.king_premium_gate')),
            round:    @json(__('minigame.king_round_n', ['n' => '__N__']))
        }
    };
    var MSG = {
        min2:      @json(__('minigame.min_players_2')),
        kingMin3:  @json(__('minigame.king_min_players')),
        needMF:    @json(__('minigame.card_need_male_female')),
        waiting:   @json(__('minigame.card_waiting_deal')),
        male:      @json(__('minigame.card_male')),
        female:    @json(__('minigame.card_female')),
        kingRole:  @json(__('minigame.king_role_king')),
        flipNow:   @json(__('minigame.king_flip_now')),
        resting:   @json(__('minigame.card_resting', ['names' => '__NAMES__'])),
        nameSep:   @json(__('minigame.name_separator')),
        tierMild:  @json(__('minigame.tier_mild')),
        tierMildP: @json(__('minigame.tier_mild_plus')),
        tierMedP:  @json(__('minigame.tier_medium_plus')),
        tierMed:   @json(__('minigame.tier_medium')),
        tierInt:   @json(__('minigame.tier_intense'))
    };

    var SUITS = ['clubs','diamonds','hearts','spades'];
    var RANKS = ['2','3','4','5','6','7','8','9','10','J','Q','K','A'];
    var SYM   = {clubs:'♣',diamonds:'♦',hearts:'♥',spades:'♠'};

    var players = [], round = 0, usedCards = [], cardsDealt = false, flipping = false;
    var playerCount = 3;
    var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    function esc(s){var d=document.createElement('div');d.appendChild(document.createTextNode(s));return d.innerHTML}
    function shuffle(a){for(var i=a.length-1;i>0;i--){var j=Math.floor(Math.random()*(i+1));var t=a[i];a[i]=a[j];a[j]=t}return a}
    function isRed(suit){return suit==='hearts'||suit==='diamonds'}
    function cardValue(c){return RANKS.indexOf(c.rank)*4+SUITS.indexOf(c.suit)}
    function el(id){return document.getElementById(id)}

    /* ---------- 模式 ---------- */
    function applyMode(m, pushUrl){
        MODE = m;
        document.body.setAttribute('data-cm-mode', m);
        el('cm-title').textContent = T[m].title;
        el('cm-subtitle').textContent = T[m].subtitle;
        el('cm-setup-heading').textContent = T[m].setup;
        el('cm-gate-text').textContent = T[m].gate;
        document.querySelectorAll('.cm-mode').forEach(function(b){
            b.setAttribute('aria-selected', String(b.dataset.mode === m));
        });
        // 切模式等於重新開始:兩種玩法的牌組與玩家條件都不同
        resetGame();
        if(pushUrl && window.history && history.replaceState){
            history.replaceState(null, '', URLS[m]);
            document.title = T[m].title + ' — ' + @json(__('ui.site_name'));
        }
    }
    document.querySelectorAll('.cm-mode').forEach(function(b){
        b.addEventListener('click', function(){
            if(b.dataset.mode !== MODE) applyMode(b.dataset.mode, true);
        });
    });

    /* ---------- 玩家設定 ---------- */
    window.addPlayer = function(){
        if(playerCount >= 6) return;
        playerCount++;
        var row = document.createElement('div');
        row.className = 'mg-player-row';
        var name = @json(__('minigame.player_default', ['n' => '__N__'])).replace('__N__', playerCount);
        row.innerHTML =
            '<input type="text" class="form-control p-name" value="'+esc(name)+'" maxlength="12">'+
            '<select class="form-control p-gender">'+
            '<option value="male">'+esc(MSG.male)+'</option>'+
            '<option value="female" selected>'+esc(MSG.female)+'</option></select>'+
            '<button class="mg-player-remove" onclick="removePlayer(this)">✕</button>';
        el('players-list').appendChild(row);
        if(playerCount >= 6) el('add-player-btn').style.display = 'none';
    };
    window.removePlayer = function(btn){
        btn.closest('.mg-player-row').remove();
        playerCount--;
        el('add-player-btn').style.display = 'inline-block';
    };

    window.startGame = function(){
        var rows = document.querySelectorAll('.mg-player-row');
        var fallback = @json(__('minigame.player_default_short'));
        players = [];
        rows.forEach(function(r){
            players.push({
                name: PlayerAvatar.displayName(r) || fallback,
                gender: r.querySelector('.p-gender').value,
                card: null
            });
        });

        if(MODE === 'king'){
            if(players.length < 3){ alert(MSG.kingMin3); return; }
        } else {
            if(players.length < 2){ alert(MSG.min2); return; }
            var hasM = players.some(function(p){return p.gender==='male'});
            var hasF = players.some(function(p){return p.gender==='female'});
            if(!hasM || !hasF){ alert(MSG.needMF); return; }
        }
        round = 1; usedCards = [];
        escalate = Escalation.enabled();
        startRound();
    };

    /* ---------- 牌組 ---------- */
    function pickCard(exclude){
        exclude = exclude || [];
        var avail = [];
        SUITS.forEach(function(s){ RANKS.forEach(function(r){
            var k = s+'_'+r;
            if(usedCards.indexOf(k) === -1) avail.push({key:k, suit:s, rank:r});
        })});
        if(!avail.length){ usedCards = exclude.slice(); return pickCard(exclude); }
        var c = avail[Math.floor(Math.random()*avail.length)];
        usedCards.push(c.key);
        return c;
    }

    /* 國王牌組:1 張 K + (n-1) 張號碼牌。號碼用連號而非隨機點數,
       這樣揭曉時才能與玩家 1:1 對應。花色只是視覺。 */
    function buildKingDeck(count){
        var kingSuit = SUITS[Math.floor(Math.random()*4)];
        var cards = [{role:'king', rank:'K', suit:kingSuit}];
        var nums = [];
        for(var n=1;n<=count-1;n++) nums.push(n);
        shuffle(nums);
        nums.forEach(function(n){
            cards.push({role:'number', rank:String(n), suit:SUITS[Math.floor(Math.random()*4)], number:n});
        });
        return shuffle(cards);
    }

    /* ---------- 回合 ---------- */
    function tierTag(){
        var label = {mild:MSG.tierMild, mild_plus:MSG.tierMildP, medium:MSG.tierMed,
                     medium_plus:MSG.tierMedP, intense:MSG.tierInt}[roundTier] || MSG.tierMild;
        return '<span class="mg-tag mg-tag-'+roundTier+'">'+esc(label)+'</span>';
    }

    function startRound(){
        el('setup-phase').style.display = 'none';
        el('drawing-phase').style.display = 'block';
        cardsDealt = false; flipping = false;
        // 一回合只決定一次等級,標籤與實際抽到的題目才會是同一級。
        roundTier = currentTier();

        var label = T[MODE].round.replace('__N__', round);
        el('round-badge').innerHTML = esc(label) + (MODE === 'card' ? ' ' + tierTag() : '');
        el('cm-tip').textContent = T[MODE].tip;
        el('cm-tip').style.display = T[MODE].tip ? '' : 'none';

        el('deal-btn').style.display = 'inline-flex';
        el('flip-btn').style.display = 'none';
        el('next-round-btn').style.display = 'none';
        el('reset-btn').style.display = 'none';
        el('upgrade-notice').style.display = 'none';
        el('inline-results').innerHTML = '';
        el('king-reveal').style.display = 'none';
        el('cm-countdown').hidden = true;

        var area = el('deal-area');
        area.innerHTML = '';
        players.forEach(function(p,i){
            var slot = document.createElement('div');
            slot.className = 'mg-card-slot';
            slot.id = 'slot-'+i;
            slot.style.animationDelay = (i*60)+'ms';
            var genderTag = '';
            if(MODE === 'card'){
                var gc = p.gender==='male' ? 'mg-gender-male' : 'mg-gender-female';
                var gl = p.gender==='male' ? MSG.male : MSG.female;
                genderTag = '<span class="mg-gender-tag '+gc+'">'+esc(gl)+'</span>';
            }
            slot.innerHTML = '<div class="slot-name">'+esc(p.name)+'</div>'+genderTag+
                '<div class="mg-card-placeholder">'+esc(MSG.waiting)+'</div>';
            area.appendChild(slot);
        });
    }

    window.dealCards = function(){
        if(cardsDealt) return;
        cardsDealt = true;
        el('deal-btn').style.display = 'none';

        var deck = MODE === 'king' ? buildKingDeck(players.length) : null;
        var roundKeys = [];

        players.forEach(function(p,i){
            p.card = deck ? deck[i] : pickCard(roundKeys);
            if(!deck) roundKeys.push(p.card.key);

            var slot = el('slot-'+i);
            var ph = slot.querySelector('.mg-card-placeholder');
            if(ph) ph.remove();

            var sym = SYM[p.card.suit] || p.card.suit;
            var cls = (isRed(p.card.suit) ? 'red' : 'black') +
                      (p.card.role === 'king' ? ' is-king' : '');
            var centre = p.card.role === 'king'
                ? '<span class="center-suit">'+sym+'</span><span class="center-label">'+esc(MSG.kingRole)+'</span>'
                : '<span class="center-suit">'+sym+'</span><span class="center-rank">'+p.card.rank+'</span>';
            var front =
                '<div class="mg-card-corner mg-card-corner-tl"><span class="corner-rank">'+p.card.rank+'</span><span class="corner-suit">'+sym+'</span></div>'+
                '<div class="mg-card-corner mg-card-corner-br"><span class="corner-rank">'+p.card.rank+'</span><span class="corner-suit">'+sym+'</span></div>'+
                '<div class="mg-card-center">'+centre+'</div>';

            var scene = document.createElement('div');
            scene.className = 'mg-card-scene dealing';
            scene.id = 'scene-'+i;
            scene.style.animationDelay = (i*100)+'ms';
            scene.innerHTML =
                '<div class="mg-card-inner" id="inner-'+i+'">'+
                '<div class="mg-card-face mg-card-back"><div class="mg-card-back-icon">♠</div></div>'+
                '<div class="mg-card-face mg-card-front '+cls+'">'+front+'</div>'+
                '</div>';
            slot.appendChild(scene);
        });

        setTimeout(function(){
            el('flip-btn').style.display = 'inline-flex';
        }, players.length*100 + 350);
    };

    function sparkle(scene){
        if(!scene || reduce) return;
        var r = scene.getBoundingClientRect();
        var colors = ['#ffd700','#ff6b6b','#a78bfa','#60a5fa','#f472b6'];
        for(var s=0;s<10;s++){
            var sp = document.createElement('div');
            sp.className = 'cm-sparkle';
            var a = Math.random()*Math.PI*2, d = 40+Math.random()*40;
            sp.style.cssText = 'left:'+(r.width/2)+'px;top:'+(r.height/2)+'px;background:'+colors[s%colors.length]+
                ';--sx:'+(Math.cos(a)*d)+'px;--sy:'+(Math.sin(a)*d)+'px';
            scene.appendChild(sp);
            void sp.offsetWidth;
            sp.classList.add('animate');
            (function(x){setTimeout(function(){x.remove()},800)})(sp);
        }
    }

    /* 撲克牌:逐張翻(有節奏感)。國王:倒數後同時翻(先講好規則才公平)。 */
    window.flipAllCards = function(){
        if(flipping) return;
        flipping = true;
        el('flip-btn').style.display = 'none';

        if(MODE === 'king'){
            el('cm-tip').style.display = 'none';
            var box = el('cm-countdown'), num = el('cm-countdown-num');
            box.hidden = false;
            var seq = ['3','2','1'], i = 0, step = reduce ? 200 : 900;
            (function tick(){
                if(i < seq.length){
                    num.className = 'cm-countdown-num';
                    num.textContent = seq[i];
                    void num.offsetWidth;
                    i++; setTimeout(tick, step);
                    return;
                }
                num.className = 'cm-countdown-num go';
                num.textContent = MSG.flipNow;
                players.forEach(function(p,k){
                    var inner = el('inner-'+k);
                    if(inner) inner.classList.add('flipped');
                });
                setTimeout(function(){
                    box.hidden = true;
                    revealKing();
                    finishRound();
                }, reduce ? 150 : 750);
            })();
            return;
        }

        players.forEach(function(p,i){
            setTimeout(function(){
                var inner = el('inner-'+i), scene = el('scene-'+i);
                if(inner) inner.classList.add('flipped');
                if(scene){ scene.classList.add('flip-bump'); sparkle(scene); }
            }, i*400);
        });
        setTimeout(function(){
            revealPairs();
            finishRound();
        }, (players.length-1)*400 + 600 + 800);
    };

    function finishRound(){
        flipping = false;
        el('reset-btn').style.display = 'inline-flex';
        el('next-round-btn').style.display = 'inline-flex';
    }

    /* ---------- 揭曉:國王 ---------- */
    function revealKing(){
        var kingIdx = -1, nums = [];
        players.forEach(function(p,i){
            if(p.card.role === 'king') kingIdx = i;
            else nums.push({name:p.name, n:p.card.number});
        });
        nums.sort(function(a,b){return a.n - b.n});

        el('king-name').textContent = players[kingIdx].name;
        var html = '<span class="cm-legend-chip is-king">👑 '+esc(players[kingIdx].name)+'</span>';
        nums.forEach(function(x){
            html += '<span class="cm-legend-chip"><b>'+x.n+'</b>'+esc(x.name)+'</span>';
        });
        el('king-legend').innerHTML = html;

        var panel = el('king-reveal');
        panel.style.display = 'block';
        panel.scrollIntoView({behavior: reduce ? 'auto' : 'smooth', block:'nearest'});
    }

    /* ---------- 揭曉:撲克牌配對 ---------- */
    /* 這一回合落在哪一級。開了升溫就照階梯走,沒開就每回合在所有等級裡隨機 ——
       原本這一段是寫死的三段式,等於「逐漸升溫」永遠開著,關不掉。 */
    function currentTier(){
        if(!TIER_ORDER.length) return 'mild';
        if(escalate) return Escalation.topTierFor(round, TIER_ORDER, true);
        return TIER_ORDER[Math.floor(Math.random()*TIER_ORDER.length)];
    }
    var roundTier = 'mild';

    function activity(){
        var pool = ACTIVITIES[roundTier] || ACTIVITIES.mild || [];
        return pool[Math.floor(Math.random()*pool.length)];
    }
    function revealPairs(){
        var males = [], females = [];
        players.forEach(function(p,i){
            if(!p.card) return;
            var e = {name:p.name, value:cardValue(p.card)};
            (p.gender === 'male' ? males : females).push(e);
        });
        males.sort(function(a,b){return b.value-a.value});
        females.sort(function(a,b){return b.value-a.value});

        var pairs = Math.min(males.length, females.length);
        var html = '<div class="mg-result-card">';
        for(var i=0;i<pairs;i++){
            var m = males[i], f = females[females.length-1-i];
            var big = m.value >= f.value ? m.name : f.name;
            var small = m.value >= f.value ? f.name : m.name;
            var text = activity().replace(/牌大的/g, big).replace(/牌小的/g, small);
            html += '<div class="mg-result-item"><div class="mg-result-text">'+esc(text)+'</div></div>';
        }
        var rest = males.slice(pairs).concat(females.slice(0, Math.max(0, females.length-pairs)));
        if(rest.length){
            html += '<div class="mg-result-item"><div style="color:var(--text-dim);font-size:.9rem">'+
                MSG.resting.replace('__NAMES__', rest.map(function(x){return esc(x.name)}).join(MSG.nameSep))+
                '</div></div>';
        }
        html += '</div>';
        el('inline-results').innerHTML = html;
    }

    window.nextRound = function(){
        round++;
        players.forEach(function(p){ p.card = null; });
        startRound();
    };
    window.resetGame = function(){
        el('drawing-phase').style.display = 'none';
        el('setup-phase').style.display = 'block';
        round = 0; usedCards = []; cardsDealt = false; flipping = false;
        el('inline-results').innerHTML = '';
        el('king-reveal').style.display = 'none';
        el('cm-countdown').hidden = true;
    };

    applyMode(MODE, false);
})();
</script>
@endsection
