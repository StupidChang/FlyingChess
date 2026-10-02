@extends('layouts.app')
{{-- dice_seo_title 只用在 meta;頁面 H1 仍是 dice_title。 --}}
@section('title', __('minigame.dice_seo_title') . ' — ' . __('ui.site_name'))
@section('meta_description', __('minigame.dice_meta'))
@section('canonical', route('dice-game.show'))

@section('schema')
    @include('partials.game-schema', [
        'gameName' => __('minigame.dice_title'),
        'gameDescription' => __('games.desc_dice'),
        'gamePath' => 'dice-game',
        'minPlayers' => 2,
        'maxPlayers' => 6,
    ])
    @include('partials.game-faq-schema', ['faqKey' => 'dice-game'])
@endsection

@section('faq')
    @include('partials.game-faq', ['faqKey' => 'dice-game'])
@endsection

@section('styles')
<link rel="stylesheet" href="{{ asset_v('css/minigames.css') }}">
<style>
/* ── 骰子遊戲(2026-10-02 重排)──
   單欄:回合列 → 骰子舞台 → 結果卡片 → 按鈕 → 骰子設定 → 最近幾輪。
   原本的左側長清單在窄螢幕排到骰子上面、寬螢幕又把整頁撐成三欄,改成舞台下方的
   設定面板:每個類別一列「關|溫柔|大膽|狂野」,手機也排得下。 */
#mg-page-root{max-width:760px}
#setup-phase{max-width:520px;margin-left:auto;margin-right:auto}
.dg-play{--die:84px;display:flex;flex-direction:column;gap:16px;margin-top:4px}
@media(max-width:480px){.dg-play{--die:64px}}

/* 回合列 */
.dg-turnbar{display:flex;align-items:center;justify-content:center;gap:10px;flex-wrap:wrap}
.dg-turnbar .mg-round-badge{margin:0;font-size:.82rem;font-weight:700;color:var(--text-dim);
  padding:4px 10px;border:1px solid var(--border);border-radius:999px;background:var(--surface)}
.dg-turnbar .mg-current-player{margin:0;font-size:1.25rem}

/* 舞台 */
.dg-stage{background:var(--surface);border:1px solid var(--border);border-radius:18px;padding:22px 14px 20px;
  background-image:radial-gradient(ellipse at 50% 0%,color-mix(in srgb,var(--accent) 10%,transparent),transparent 70%)}
.dg-dice-area{display:flex;gap:22px 26px;justify-content:center;flex-wrap:wrap;padding:6px 0 4px}
@media(max-width:480px){.dg-dice-area{gap:16px 14px}}
.dg-dice-wrapper{text-align:center;width:calc(var(--die) + 18px)}
.dg-dice-label{font-size:.72rem;color:var(--text-dim);margin-bottom:10px;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.dg-dice-scene{width:var(--die);height:var(--die);perspective:320px;margin:0 auto;filter:drop-shadow(0 4px 8px rgba(0,0,0,.35));transform:translateZ(0)}
.dg-dice{width:100%;height:100%;position:relative;transform-style:preserve-3d;transform:rotateX(-20deg) rotateY(25deg);-webkit-backface-visibility:hidden;backface-visibility:hidden}
/* outline:transparent + backface hints let the compositor anti-alias the rotated edges (kills the jaggies) */
.dg-dice-face{position:absolute;width:var(--die);height:var(--die);border-radius:12px;display:flex;align-items:center;justify-content:center;
  font-size:calc(var(--die) * .17);font-weight:800;color:#fff;border:2px solid rgba(255,255,255,.22);-webkit-backface-visibility:hidden;backface-visibility:hidden;
  outline:1px solid transparent;text-align:center;line-height:1.15;padding:5px;overflow-wrap:anywhere;text-shadow:0 1px 2px rgba(0,0,0,.35)}
.dg-dice-face.f1{transform:rotateY(0deg) translateZ(calc(var(--die) / 2))}
.dg-dice-face.f2{transform:rotateY(180deg) translateZ(calc(var(--die) / 2))}
.dg-dice-face.f3{transform:rotateY(90deg) translateZ(calc(var(--die) / 2))}
.dg-dice-face.f4{transform:rotateY(-90deg) translateZ(calc(var(--die) / 2))}
.dg-dice-face.f5{transform:rotateX(90deg) translateZ(calc(var(--die) / 2))}
.dg-dice-face.f6{transform:rotateX(-90deg) translateZ(calc(var(--die) / 2))}
/* 每種骰子一個顏色,跟有沒有勾選無關 */
.dg-die-action .dg-dice-face{background:linear-gradient(135deg,#e53935,#c62828)}
.dg-die-part .dg-dice-face{background:linear-gradient(135deg,#2563eb,#1d4ed8)}
.dg-die-time .dg-dice-face{background:linear-gradient(135deg,#7c3aed,#6d28d9)}
.dg-die-prop .dg-dice-face{background:linear-gradient(135deg,#0d9488,#0f766e)}
.dg-die-play .dg-dice-face{background:linear-gradient(135deg,#db2777,#9d174d)}
.dg-die-custom .dg-dice-face{background:linear-gradient(135deg,#d9a441,#b8860b)}
.dg-die-twist .dg-dice-face{background:linear-gradient(135deg,#ea580c,#c2410c)}
.dg-die-who .dg-dice-face{background:linear-gradient(135deg,#475569,#334155)}
.dg-dice-scene.dg-glow{animation:dgGlowPulse .8s ease-out 1}
@keyframes dgGlowPulse{
  0%{filter:drop-shadow(0 4px 8px rgba(0,0,0,.35))}
  35%{filter:drop-shadow(0 4px 8px rgba(0,0,0,.35)) drop-shadow(0 0 16px rgba(255,205,90,.9))}
  100%{filter:drop-shadow(0 4px 8px rgba(0,0,0,.35))}
}

/* 結果卡片:一個主角(要做什麼),其他都是配角 */
.dg-result{text-align:center;margin-top:18px;padding:18px 14px 20px;animation:fadeIn .3s ease-out}
@keyframes fadeIn{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:none}}
.dg-r-who{font-size:.85rem;font-weight:600;color:var(--text-dim);margin-bottom:6px}
.dg-result .dg-r-hero{font-size:clamp(1.6rem,6vw,2.1rem);font-weight:800;color:var(--text);line-height:1.25;margin:0;letter-spacing:.01em}
.dg-r-meta{display:flex;justify-content:center;flex-wrap:wrap;gap:8px;margin-top:14px}
.dg-pill{position:relative;overflow:hidden;display:inline-flex;align-items:center;gap:6px;height:34px;padding:0 14px;border-radius:999px;
  font:inherit;font-size:.9rem;font-weight:700;color:var(--text);background:var(--surface2);border:1px solid var(--border);font-variant-numeric:tabular-nums}
.dg-pill-prop{border-color:color-mix(in srgb,#0d9488 55%,var(--border))}
.dg-pill-time{border-color:color-mix(in srgb,#7c3aed 60%,var(--border))}
button.dg-pill{cursor:pointer}
button.dg-pill:hover{background:var(--border)}
.dg-pill-fill{position:absolute;inset:0;background:color-mix(in srgb,#7c3aed 35%,transparent);transform:scaleX(0);transform-origin:left;transition:transform .25s linear}
.dg-pill-label{position:relative}
.dg-pill.is-done{border-color:var(--accent);color:var(--accent);animation:dg-timer-flash .5s ease-in-out 3}
@keyframes dg-timer-flash{50%{opacity:.35}}
.dg-r-twist{margin:14px auto 0;max-width:460px;font-size:.95rem;font-weight:600;line-height:1.5;color:#fb923c}
/* 按鈕列 */
.dg-play .mg-action-btns{margin-top:16px}
.dg-reset{flex-basis:100%;background:none;border:0;color:var(--text-dim);font:inherit;font-size:.82rem;cursor:pointer;text-decoration:underline;padding:6px}
.dg-reset:hover{color:var(--text)}
.mg-action-btns.dg-rolling .dg-reset{opacity:.45;pointer-events:none}

/* 骰子設定 */
.dg-settings{background:var(--surface);border:1px solid var(--border);border-radius:16px;padding:14px 16px}
.dg-settings > summary{cursor:pointer;font-weight:700;font-size:.92rem;list-style:none;display:flex;justify-content:space-between;align-items:center}
.dg-settings > summary::-webkit-details-marker{display:none}
.dg-settings > summary::after{content:'▾';color:var(--text-dim);transition:transform .2s}
.dg-settings[open] > summary::after{transform:rotate(180deg)}
.dg-picker-list{display:flex;flex-direction:column;gap:10px;margin-top:14px}
.dg-row{display:grid;grid-template-columns:96px 1fr;align-items:center;gap:10px}
.dg-row-name{display:flex;align-items:center;gap:8px;font-size:.85rem;font-weight:700;color:var(--text)}
.dg-picker-dot{width:9px;height:9px;border-radius:50%;flex:none}
.dg-picker-dot-action{background:#e53935}.dg-picker-dot-part{background:#2563eb}.dg-picker-dot-time{background:#7c3aed}
.dg-picker-dot-prop{background:#0d9488}.dg-picker-dot-play{background:#db2777}.dg-picker-dot-custom{background:#d9a441}
.dg-picker-dot-twist{background:#ea580c}
.dg-seg{display:flex;flex-wrap:wrap;gap:4px;background:var(--surface2);border-radius:10px;padding:3px}
.dg-seg button{flex:1 1 0;min-width:64px;border:0;border-radius:8px;padding:7px 8px;background:none;color:var(--text-dim);
  font:inherit;font-size:.82rem;font-weight:700;cursor:pointer;white-space:nowrap;transition:background .15s,color .15s}
.dg-seg button:hover{color:var(--text)}
.dg-seg button.active{background:var(--accent);color:#fff}
.dg-seg button.locked{opacity:.5}
.dg-seg button.is-off.active{background:var(--border);color:var(--text)}
@media(max-width:480px){
  .dg-row{grid-template-columns:1fr}
  .dg-seg button{min-width:0}
}
.dg-manage-link{display:inline-block;margin-top:12px;font-size:.82rem;color:var(--accent)}
.dg-manage-link:hover{text-decoration:underline}

/* 前幾輪 */
.dg-hist > summary{cursor:pointer;font-size:.82rem;color:var(--text-dim);text-align:center;list-style:none;padding:4px}
.dg-hist > summary::-webkit-details-marker{display:none}
.dg-hist > summary:hover{color:var(--text)}
.dg-history{display:flex;flex-direction:column;gap:6px;margin-top:8px}
.dg-history-item{display:flex;align-items:center;gap:8px;padding:7px 12px;font-size:.78rem;
  background:var(--surface);border:1px solid var(--border);border-radius:8px;color:var(--text-dim);animation:dgHistoryIn .35s cubic-bezier(.34,1.56,.64,1) both}
.dg-history-round{flex-shrink:0;width:20px;height:20px;border-radius:50%;background:var(--surface2);color:var(--text);
  font-weight:700;font-size:.68rem;display:flex;align-items:center;justify-content:center}
.dg-history-text{overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
@keyframes dgHistoryIn{from{opacity:0;transform:translateX(-12px)}to{opacity:1;transform:translateX(0)}}
@media (prefers-reduced-motion: reduce){
  .dg-dice-scene.dg-glow,.dg-history-item,.dg-pill.is-done{animation:none}
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
        @include('partials.escalate-toggle')
        <button class="btn btn-gold btn-full" onclick="startGame()">{{ __('minigame.start_game') }}</button>
    </div>

    {{-- Game Phase --}}
    <div id="game-phase" style="display:none">
        <div class="dg-play">
            <div class="dg-turnbar">
                <div class="mg-round-badge" id="turn-badge"></div>
                <div class="mg-current-player" id="current-player"></div>
            </div>

            <div class="dg-stage">
                <div class="dg-dice-area" id="dice-area"></div>
                <div id="result-display" class="dg-result" style="display:none"></div>
                <div class="mg-action-btns">
                    <button class="btn btn-gold btn-xl" id="roll-btn" onclick="rollDice()">{{ __('minigame.dice_roll') }}</button>
                    <button class="btn btn-gold btn-xl" id="next-btn" style="display:none" onclick="nextTurn()">{{ __('minigame.next_turn') }}</button>
                    <button type="button" class="dg-reset" onclick="resetGame()">{{ __('minigame.reset_game') }}</button>
                </div>
            </div>

            {{-- 骰子設定:預設展開,玩起來之後可以收起來 --}}
            <details class="dg-settings" open>
                <summary>{{ __('minigame.dice_pick_tier') }}</summary>
                <div class="dg-picker-list" id="dice-select"></div>
                @auth
                    <a href="{{ route('dice.index') }}" class="dg-manage-link">{{ __('minigame.dice_manage') }} →</a>
                @endauth
            </details>

            {{-- 前幾輪:預設收起來,不跟這一輪的結果搶視線 --}}
            <details class="dg-hist" id="roll-history-box" hidden>
                <summary></summary>
                <div class="dg-history" id="roll-history"></div>
            </details>
        </div>
    </div>
</div>

    {{-- 常駐的「看廣告解鎖」提示。刻意不做成彈窗:一群人圍著一台裝置玩的時候,
         遊戲中跳出任何東西毀掉的是整場氣氛,不只是一個人的體驗。 --}}
    @include('partials.rewarded-unlock')
@endsection

@section('scripts')
{{-- 玩家頭像:自己盯著玩家列補上挑選器,各遊戲不用改自己的產生邏輯 --}}
<script src="{{ asset_v('js/player-avatar.js') }}" data-label="{{ __('ui.choose_avatar') }}"></script>
<script src="{{ asset_v('js/escalation.js') }}"></script>
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
        var box=document.getElementById('roll-history-box');
        if(box){ box.hidden=false; box.querySelector('summary').textContent=HISTORY_LABEL+'('+list.children.length+')'; }
    }
    function clearHistory(){
        var list = document.getElementById('roll-history');
        if(list) list.innerHTML = '';
        var box=document.getElementById('roll-history-box'); if(box) box.hidden=true;
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
    var CAT_ORDER = ['action','part','time','prop','play','twist','custom'];
    var CAT_LABELS = {
        action: @json(__('minigame.dice_label_action')),
        part:   @json(__('minigame.dice_label_part')),
        time:   @json(__('minigame.dice_label_time')),
        prop:   @json(__('minigame.dice_label_prop')),
        play:   @json(__('minigame.dice_label_play')),
        twist:  @json(__('minigame.dice_label_twist')),
        custom: @json(__('minigame.dice_my'))
    };
    var INT_LABELS = {
        gentle:   @json(__('minigame.dice_int_gentle')),
        bold:     @json(__('minigame.dice_int_bold')),
        wild:     @json(__('minigame.dice_int_wild')),
        standard: @json(__('minigame.dice_int_standard'))
    };
    var WILD_LOCKED_MSG = @json(__('minigame.dice_wild_locked'));
    var WHO_LABEL       = @json(__('minigame.dice_label_who'));
    var PLAY_ALONE_MSG  = @json(__('minigame.dice_play_alone'));
    var HISTORY_LABEL   = @json(__('minigame.dice_history'));
    var OFF_LABEL       = @json(__('minigame.dice_off'));
    var RULES           = @json($rules);
    var TARGET_TPL      = @json(__('minigame.dice_target', ['from' => '__FROM__', 'to' => '__TO__']));
    var TIMER_START     = @json(__('minigame.dice_timer_start', ['time' => '__T__']));
    var TIMER_STOP      = @json(__('minigame.dice_timer_stop'));
    var TIMER_DONE      = @json(__('minigame.dice_timer_done'));
    var NEED_ONE_MSG    = @json(__('minigame.dice_need_one'));

    var enabled = {};   // die id -> true
    function defaultEnabled(){
        enabled = {};
        // 轉折骰預設就上桌:第一輪就要有變化,不然只是「動作+部位+時間」的排列組合
        ['builtin_action_gentle','builtin_part_gentle','builtin_time','builtin_twist_bold'].forEach(function(id){
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
    /* 轉折骰的面是「短標|完整說明」:骰子上只放得下短標,結果卡片才顯示整句 */
    function shortOf(v){ var i=String(v).indexOf('|'); return i===-1 ? v : v.slice(0,i); }
    function longOf(v){ var i=String(v).indexOf('|'); return i===-1 ? v : v.slice(i+1); }
    function itemLabel(d){
        if(d.custom) return d.name;
        if(d.intensity) return INT_LABELS[d.intensity] || '';
        return INT_LABELS.standard;
    }
    // 骰子上方只寫類別:強度已經在骰子設定裡看得到,再寫一次只是多一行字
    function topLabelOf(d){
        if(d.custom) return d.name;
        return CAT_LABELS[d.cat];
    }
    var escalate=false;
    var INT_ORDER=['gentle','bold','wild'];

    /**
     * 這一回合實際上桌的骰子。
     *
     * 沒開升溫就是玩家勾了什麼用什麼。開了的話,玩家勾的那顆是**上限**:
     * 同一個類別先從最溫和的變體開始,每幾回合往上換一階,最多換到他勾的那階。
     * 拿不到的(付費未解鎖的 wild 帶 locked)一律排除,升溫就停在拿得到的最高階。
     */
    function activeDice(){
        return capTwist(rawActiveDice());
    }

    /* 狂野轉折(脫光、口交…)不配在只有溫柔骰子的桌上:轉折骰勾了狂野、但動作／部位／
       道具／玩法都還是溫柔的話,這一輪先用大膽版的轉折。等其他骰子升上來就自動換回。 */
    function capTwist(list){
        var heat=0;
        list.forEach(function(d){
            if(!d.custom && ['action','part','prop','play'].indexOf(d.cat)!==-1) heat=Math.max(heat, {gentle:1,bold:2,wild:3}[d.intensity]||1);
        });
        if(heat>=2) return list;
        return list.map(function(d){
            if(d.custom || d.cat!=='twist' || d.intensity!=='wild') return d;
            var bold=ALL.filter(function(x){return x.cat==='twist' && x.intensity==='bold' && !x.locked && !x.custom})[0];
            return bold||d;
        });
    }

    function rawActiveDice(){
        var picked=ALL.filter(function(d){return enabled[d.id] && !d.locked});
        if(!escalate) return picked;

        var seen={};
        var result=[];
        picked.forEach(function(d){
            if(d.custom || !d.cat || INT_ORDER.indexOf(d.intensity)===-1){
                result.push(d);   // 自訂骰與沒有分階的骰子(例如時間)照常上桌
                return;
            }
            if(seen[d.cat]) return;   // 同一個類別只留一顆
            seen[d.cat]=true;

            var cap=INT_ORDER.indexOf(d.intensity);
            var ladder=INT_ORDER.slice(0,cap+1).filter(function(level){
                return ALL.some(function(x){return x.cat===d.cat && x.intensity===level && !x.locked && !x.custom});
            });
            var want=Escalation.topTierFor(round, ladder, true)||d.intensity;
            var pick=ALL.filter(function(x){return x.cat===d.cat && x.intensity===want && !x.locked && !x.custom})[0];
            result.push(pick||d);
        });

        return result;
    }

    /* 骰子設定:每個類別一列,「關|溫柔|大膽|狂野」單選(同一類別只上一顆)。
       自訂骰子那一列是你自己的骰子,一樣單選。 */
    function renderDiceSelect(){
        var wrap=document.getElementById('dice-select');
        if(!wrap) return;
        wrap.innerHTML='';
        CAT_ORDER.forEach(function(cat){
            var items = ALL.filter(function(d){ return cat==='custom' ? d.custom : (d.cat===cat && !d.custom); });
            if(!items.length) return;
            var row=document.createElement('div');
            row.className='dg-row';
            row.innerHTML='<div class="dg-row-name"><span class="dg-picker-dot dg-picker-dot-'+cat+'"></span>'+escHtml(CAT_LABELS[cat]||cat)+'</div>';
            var seg=document.createElement('div');
            seg.className='dg-seg'; seg.setAttribute('role','radiogroup'); seg.setAttribute('aria-label', CAT_LABELS[cat]||cat);
            var anyOn=items.some(function(d){return enabled[d.id] && !d.locked});
            var off=document.createElement('button');
            off.type='button'; off.className='is-off'+(anyOn?'':' active'); off.textContent=OFF_LABEL;
            off.setAttribute('aria-pressed', anyOn?'false':'true');
            off.onclick=function(){ setGroupOff(cat) };
            seg.appendChild(off);
            items.forEach(function(d){
                var b=document.createElement('button');
                b.type='button';
                var on=enabled[d.id] && !d.locked;
                b.className=(on?'active':'')+(d.locked?' locked':'');
                b.setAttribute('aria-pressed', on?'true':'false');
                b.textContent=itemLabel(d)+(d.locked?' 🔒':'');
                b.onclick=function(){ if(!enabled[d.id]) toggleDie(d.id); };
                seg.appendChild(b);
            });
            row.appendChild(seg);
            wrap.appendChild(row);
        });
    }

    function setGroupOff(cat){
        if(rollAnimId) return;
        var inGroup=function(d){ return groupOf(d)===cat; };
        var remaining=rawActiveDice().filter(function(d){ return !inGroup(d); });
        if(!remaining.length){ showToast(NEED_ONE_MSG); return; }
        ALL.forEach(function(d){ if(inGroup(d)) enabled[d.id]=false; });
        afterPickerChange();
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
            /* 玩法骰單獨上桌:它本身就是一整件事(「後入抽插30下」),再配動作、部位、時間
               就變成同一輪要做三件事。開玩法就關掉那幾類,開那幾類就關掉玩法。 */
            var EXCL=RULES.play_excludes||[];
            if(!d.custom && d.cat==='play'){
                var closed=false;
                ALL.forEach(function(o){ if(!o.custom && EXCL.indexOf(o.cat)!==-1 && enabled[o.id]){ enabled[o.id]=false; closed=true; } });
                if(closed) showToast(PLAY_ALONE_MSG);
            } else if(!d.custom && EXCL.indexOf(d.cat)!==-1){
                ALL.forEach(function(o){ if(!o.custom && o.cat==='play') enabled[o.id]=false; });
            }
        }
        afterPickerChange();
    };

    // 換了骰子就讓這一位用新的組合重擲,不跳到下一位
    function afterPickerChange(){
        stopTimer();
        renderDiceSelect();
        buildDice();
        document.getElementById('result-display').style.display='none';
        document.getElementById('next-btn').style.display='none';
        document.getElementById('roll-btn').style.display='inline-flex';
    }

    /**
     * 這一輪每顆骰子落在哪一面 —— 只挑說得通的組合(規則見 DiceGameService::RULES)。
     *
     * 隨機抽一組、檢查、不合就重抽;抽 400 次都沒有全合的(例如勾了一堆互相排斥的骰子),
     * 就用違規最少的那一組 —— 寧可偶爾怪一點,也不能卡住不給結果。
     * 對不到規則的骰面(後台新增的、自訂骰子)一律當作什麼都配得上。
     */
    var TIER_RANK={gentle:1, bold:2, wild:3};
    function violations(dice, idx){
        var by={}, n=0;
        dice.forEach(function(b,i){ (by[b.catClass]=by[b.catClass]||[]).push({key:b.keys[idx[i]], tier:b.tier}); });
        var actions=(by.action||[]).map(function(x){return x.key});
        var has=function(list, v){ return list && list.indexOf(v)!==-1; };
        (by.part||[]).forEach(function(p){ actions.forEach(function(a){ if(has((RULES.part_deny||{})[p.key], a)) n++; }); });
        var playsK=(by.play||[]).map(function(x){return x.key});
        (by.prop||[]).forEach(function(p){
            actions.forEach(function(a){ if(has((RULES.prop_deny||{})[p.key], a)) n++; });
            if(has(RULES.prop_needs_play, p.key) && !playsK.length) n++;
            playsK.forEach(function(pl){ if(has((RULES.prop_play_deny||{})[p.key], pl)) n++; });
        });
        (by.time||[]).forEach(function(t){
            var sec=toSeconds(t.key);
            actions.forEach(function(a){ if(has(RULES.quick, a) && sec>(RULES.quick_max||30)) n++; });
        });
        // 桌上最「熱」的那一顆:狂野轉折不配在只有溫柔骰子的桌上
        var heat=0;
        ['action','part','prop','play'].forEach(function(c){ (by[c]||[]).forEach(function(x){ heat=Math.max(heat, TIER_RANK[x.tier]||1); }); });
        (by.twist||[]).forEach(function(t){
            if(t.tier==='wild' && heat<2) n++;
            var needs=(RULES.twist_needs||{})[t.key]||[];
            if(has(needs,'time') && !by.time) n++;
            if(has(needs,'part') && !by.part) n++;
            if(has(needs,'not_mouth') && actions.some(function(a){return has(RULES.mouth, a)})) n++;
            if(has(needs,'no_play') && playsK.length) n++;
        });
        return n;
    }
    function pickCombo(dice){
        var best=null, bestN=Infinity;
        for(var tries=0; tries<400 && bestN>0; tries++){
            var idx=dice.map(function(b){ return Math.floor(Math.random()*Math.min(b.values.length,6)); });
            var n=violations(dice, idx);
            if(n<bestN){ best=idx; bestN=n; }
        }
        return best||dice.map(function(){return 0});
    }

    /**
     * 把這一輪各顆骰子的結果組成一張卡片:誰對誰 → 做什麼(動作、部位、道具、玩法、時間)
     * → 轉折(完整說明)→ 有時間就給一顆計時按鈕。
     * 不硬組成一句話:四個語系的語序不一樣,詞與詞並排比較不會出錯。
     */
    function composeResult(picks){
        var from=players[turn];
        var who=picks.filter(function(p){return p.cat==='who'})[0];
        var to=who ? who.value : players.filter(function(_,i){return i!==turn})[0];
        /* 結果卡片只有一個主角:要做什麼(動作+部位,或玩法)。其他都是配角 ——
           誰對誰是上面一行小字,道具與時間是兩顆小標籤(時間那顆就是計時按鈕),
           轉折是下面一行。原本每一樣都一樣大、各自一個框,眼睛不知道看哪裡。 */
        var hero=picks.filter(function(p){return ['action','part','play','custom'].indexOf(p.cat)!==-1}).map(function(p){return p.value}).filter(Boolean);
        var props=picks.filter(function(p){return p.cat==='prop'}).map(function(p){return p.value});
        var twists=picks.filter(function(p){return p.cat==='twist'}).map(function(p){return longOf(p.value)});
        var timeTok=picks.filter(function(p){return p.cat==='time'})[0];
        var seconds=timeTok ? toSeconds(timeTok.value) : 0;
        // 轉折說「時間加倍」的話,計時器也要跟著加倍,不然按鈕跟卡片講的不一樣
        if(seconds && twists.some(function(t){return /加倍|×\s*2|2\s*倍|double/i.test(t)})) seconds*=2;

        var target=to ? TARGET_TPL.replace('__FROM__',from).replace('__TO__',to) : from;
        var html='<div class="dg-r-who">'+escHtml(target)+'</div>'+
            '<div class="mg-result-text dg-r-hero">'+escHtml(hero.join(' '))+'</div>';
        if(props.length || seconds || (timeTok && !seconds)){
            html+='<div class="dg-r-meta">';
            props.forEach(function(t){ html+='<span class="dg-pill dg-pill-prop">'+escHtml(t)+'</span>'; });
            if(seconds) html+='<button type="button" class="dg-pill dg-pill-time dg-timer-btn"><span class="dg-pill-fill"></span><span class="dg-pill-label"></span></button>';
            else if(timeTok) html+='<span class="dg-pill dg-pill-time">'+escHtml(timeTok.value)+'</span>';
            html+='</div>';
        }
        twists.forEach(function(t){ html+='<div class="dg-r-twist">🔀 '+escHtml(t)+'</div>'; });
        return {html:html, seconds:seconds,
                // 轉折是「再擲一次」的話,擲骰鍵要再出現,不然這一面沒辦法照做
                reroll: twists.some(function(t){return /再擲|再掷|roll again|もう一度振/i.test(t)}),
                history:target+'：'+hero.concat(props).join(' ')+(timeTok?' '+timeTok.value:'')+(twists.length?'｜'+twists.join('｜'):'')};
    }

    /* 「30秒」「1分鐘」「2 min」「45 sec」→ 秒數。後台改過的時間面對不到格式就不給計時。 */
    function toSeconds(v){
        var m=String(v).match(/(\d+(?:\.\d+)?)\s*(分鐘|分钟|分|min|minutes?|秒|秒間|s|sec|seconds?)/i);
        if(!m) return 0;
        var n=parseFloat(m[1]);
        return Math.round(/分|min/i.test(m[2]) ? n*60 : n);
    }
    function fmt(sec){ var m=Math.floor(sec/60), s=sec%60; return m+':'+(s<10?'0':'')+s; }

    var timerId=null;
    function stopTimer(){ if(timerId){clearInterval(timerId); timerId=null;} }
    function bindTimer(root, seconds){
        stopTimer();
        var btn=root.querySelector('.dg-timer-btn'); if(!btn) return;
        var label=btn.querySelector('.dg-pill-label'), fill=btn.querySelector('.dg-pill-fill');
        var idle='⏱ '+fmt(seconds)+' ▶';
        label.textContent=idle; btn.setAttribute('aria-label', TIMER_START.replace('__T__', fmt(seconds)));
        btn.addEventListener('click', function(){
            if(timerId){ stopTimer(); label.textContent=idle; fill.style.transform='scaleX(0)'; btn.classList.remove('is-running'); return; }
            btn.classList.remove('is-done'); btn.classList.add('is-running');
            var end=Date.now()+seconds*1000;
            var tick=function(){
                var left=Math.max(0, Math.ceil((end-Date.now())/1000));
                fill.style.transform='scaleX('+(1-left/seconds)+')';
                label.textContent='⏸ '+fmt(left);
                if(left<=0){
                    stopTimer(); btn.classList.remove('is-running'); btn.classList.add('is-done');
                    label.textContent=TIMER_DONE;
                    if(navigator.vibrate) navigator.vibrate([200,100,200]);
                }
            };
            tick(); timerId=setInterval(tick, 250);
        });
    }

    function buildDice(){
        var defs=activeDice();
        var area=document.getElementById('dice-area');
        area.innerHTML='';
        builtDice=[];
        /* 三人以上多一顆「對象骰」,骰面是其他玩家的名字 —— 擲到誰就對誰做。
           兩個人的時候不用擲,對象就是另一個人。 */
        var others=players.filter(function(_,i){return i!==turn});
        if(others.length>=2){
            var whoFaces=[];
            while(whoFaces.length<6) whoFaces=whoFaces.concat(shuffled(others));
            defs=[{who:true, faces:whoFaces.slice(0,6)}].concat(defs);
        }
        defs.forEach(function(d,di){
            if(d.who){
                builtDice.push({catClass:'who',topLabel:WHO_LABEL,values:d.faces,keys:d.faces,tier:null});
                var wf='';
                for(var k=0;k<d.faces.length;k++) wf+='<div class="dg-dice-face f'+(k+1)+'">'+escHtml(d.faces[k])+'</div>';
                var ww=document.createElement('div');
                ww.className='dg-dice-wrapper dg-die-who';
                ww.innerHTML='<div class="dg-dice-label">'+escHtml(WHO_LABEL)+'</div><div class="dg-dice-scene"><div class="dg-dice" id="dice-'+di+'">'+wf+'</div></div>';
                area.appendChild(ww);
                return;
            }
            // 骰面與它的繁中原文一起洗牌:組合規則用原文對(見 pickCombo)
            var faces=(d.faces&&d.faces.length)?d.faces:[''];
            var pairs=shuffled(faces.map(function(f,i){return {text:f, key:(d.keys&&d.keys[i])||f}})).slice(0,6);
            var values=pairs.map(function(p){return p.text});
            builtDice.push({catClass:catClassOf(d),topLabel:topLabelOf(d),values:values,
                            keys:pairs.map(function(p){return p.key}), tier:d.custom?null:d.intensity});
            var facesHtml='';
            for(var fi=0;fi<values.length;fi++){
                facesHtml+='<div class="dg-dice-face f'+(fi+1)+'">'+escHtml(shortOf(values[fi]))+'</div>';
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
            players.push(PlayerAvatar.displayName(r)||fallbackName);
        });
        if(players.length<2){showToast(@json(__('minigame.min_players_2')));return;}
        turn=0;round=1;defaultEnabled();
        escalate=Escalation.enabled();
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
        var indices=pickCombo(builtDice);
        var picks=builtDice.map(function(b,bi){ return {cat:b.catClass, value:b.values[indices[bi]], key:b.keys[indices[bi]]}; });
        var result=composeResult(picks);
        var resultText=result.history;

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
            rd.innerHTML=result.html;
            bindTimer(rd, result.seconds);
            if(result.reroll) document.getElementById('roll-btn').style.display='inline-flex';
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
        stopTimer();
        turn++;
        if(turn>=players.length){turn=0;round++;}
        if(round>6&&!IS_PREMIUM){
            // 兩條路並列:看廣告(現在就能繼續)與升級(一勞永逸)。
            // 這段 HTML 是動態產生的,所以按鈕走 onclick 呼叫全域函式,
            // rewarded-unlock 那份 partial 的 addEventListener 綁不到後生成的節點。
            document.getElementById('result-display').innerHTML=
                '<p style="color:var(--gold);margin:16px 0">'+escHtml(@json(__('minigame.dice_premium_gate')))+'</p>'+
                '<div class="mg-gate-actions">'+
                '<button type="button" class="btn btn-gold" onclick="window.rewardedUnlockOpen && rewardedUnlockOpen()">'+
                escHtml(@json(__('minigame.rewarded_cta', ['minutes' => \App\Support\PremiumAccess::rewardedMinutes()])))+'</button>'+
                '<a href="{{ route('premium.index') }}" class="btn btn-outline-gold">'+escHtml(@json(__('minigame.go_premium')))+'</a>'+
                '</div>';
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
