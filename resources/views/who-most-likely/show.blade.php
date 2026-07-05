@extends('layouts.app')
@section('title', __('minigame.wml_title') . ' — ' . __('ui.site_name'))
@section('meta_description', __('minigame.wml_meta'))
@section('canonical', route('who-most-likely.show'))

@section('styles')
<link rel="stylesheet" href="{{ asset_v('css/minigames.css') }}">
<style>
/* ── Prompt card ── */
.wml-prompt{margin-top:8px;animation:wmlReveal .45s cubic-bezier(.34,1.56,.64,1) both}
.wml-prompt .wml-lead{font-size:.95rem;color:var(--text-dim);text-align:center;margin-bottom:4px}
.wml-prompt .wml-question{font-size:1.5rem;font-weight:800;color:var(--text);text-align:center;line-height:1.4}
@keyframes wmlReveal{0%{opacity:0;transform:translateY(14px) scale(.96)}100%{opacity:1;transform:translateY(0) scale(1)}}

/* ── Vote buttons ── */
.wml-vote-tip{text-align:center;color:var(--text-dim);font-size:.88rem;margin:18px 0 12px}
.wml-vote-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:10px}
.wml-vote-btn{
  display:flex;align-items:center;justify-content:space-between;gap:8px;
  padding:14px 16px;border-radius:var(--radius);cursor:pointer;
  background:var(--surface2);border:1px solid var(--border);color:var(--text);
  font-size:1rem;font-weight:600;transition:background .15s,border-color .15s,transform .1s;
}
.wml-vote-btn:hover{background:var(--border);border-color:var(--accent)}
.wml-vote-btn:active{transform:scale(.97)}
.wml-vote-btn .wml-vote-score{font-size:.8rem;color:var(--text-dim);font-weight:700;background:var(--bg);padding:2px 9px;border-radius:999px;min-width:26px;text-align:center}
.wml-vote-btn.chosen{background:rgba(244,63,94,.15);border-color:var(--accent);color:var(--accent);animation:wmlPick .4s cubic-bezier(.34,1.56,.64,1)}
.wml-vote-btn.chosen .wml-vote-score{color:var(--accent)}
.wml-vote-grid.locked .wml-vote-btn:not(.chosen){opacity:.5}
.wml-vote-grid.locked .wml-vote-btn{cursor:default;pointer-events:none}
@keyframes wmlPick{0%{transform:scale(1)}50%{transform:scale(1.06)}100%{transform:scale(1)}}

/* ── Scoreboard ── */
.wml-board{margin-top:24px;border-top:1px solid var(--border);padding-top:16px}
.wml-board h3{font-size:.9rem;color:var(--text-dim);text-align:center;margin-bottom:12px;font-weight:600;letter-spacing:.5px}
.wml-board-row{display:flex;align-items:center;gap:10px;padding:7px 12px;border-radius:8px}
.wml-board-row.lead{background:rgba(217,164,65,.1)}
.wml-board-row .wml-rank{width:22px;text-align:center;font-weight:800;color:var(--text-dim)}
.wml-board-row.lead .wml-rank{color:var(--gold)}
.wml-board-row .wml-nm{flex:1;font-weight:600;color:var(--text);overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.wml-board-row .wml-sc{font-weight:800;color:var(--accent)}

@media(prefers-reduced-motion:reduce){
  .wml-prompt,.wml-vote-btn.chosen{animation:none !important}
}
</style>
@endsection

@section('content')
<div class="mg-page mg-page--md mg-page--center" id="mg-page-root">
    <h1 class="mg-title">{{ __('minigame.wml_title') }}</h1>
    <p class="mg-subtitle">{{ __('minigame.wml_subtitle') }}</p>

    {{-- Setup Phase --}}
    <div id="setup-phase" class="mg-setup">
        <h2 class="mg-setup-heading">{{ __('minigame.wml_setup') }}</h2>
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

    {{-- Round Phase --}}
    <div id="round-phase" style="display:none">
        <div class="mg-round-badge" id="round-badge"></div>

        <div class="mg-content-card wml-prompt" id="prompt-card">
            <div class="mg-content-card-category"><span class="mg-tag" id="prompt-tier-tag"></span></div>
            <div class="wml-lead">{{ __('minigame.wml_lead') }}</div>
            <div class="wml-question" id="prompt-text"></div>
        </div>

        <p class="wml-vote-tip">{{ __('minigame.wml_vote_tip') }}</p>
        <div class="wml-vote-grid" id="vote-grid"></div>

        <div class="wml-board" id="scoreboard" style="display:none">
            <h3>{{ __('minigame.wml_scoreboard') }}</h3>
            <div id="scoreboard-rows"></div>
        </div>

        <div class="mg-action-btns">
            <button class="btn btn-gold btn-xl" id="next-round-btn" style="display:none" onclick="nextRound()">{{ __('minigame.wml_next') }}</button>
            <button class="btn btn-outline" id="reset-btn" style="display:none" onclick="resetGame()">{{ __('minigame.reset_game') }}</button>
        </div>
        <div id="upgrade-notice" style="display:none;text-align:center;margin-top:12px">
            <p style="color:var(--gold);margin-bottom:8px">{{ __('minigame.wml_premium_gate') }}</p>
            <a href="{{ route('premium.index') }}" class="btn btn-outline-gold">{{ __('minigame.go_premium') }}</a>
        </div>
    </div>
</div>

@include('partials.ad-unit', ['zone' => 'lobby_side'])
@endsection

@section('scripts')
<script>
(function(){
    var IS_PREMIUM = {{ $isPremium ? 'true' : 'false' }};
    var PROMPTS = @json($prompts);
    var FREE_ROUNDS = 6;
    var TIER_TAG_CLASS = {mild:'mg-tag-mild',medium:'mg-tag-medium',intense:'mg-tag-intense'};
    var TIER_LABELS = {
        mild: @json(__('minigame.tier_mild')),
        medium: @json(__('minigame.tier_medium')),
        intense: @json(__('minigame.tier_intense'))
    };
    var players=[];   // [{name, score}]
    var round=0;
    var recentPrompts=[];

    function escHtml(s){var d=document.createElement('div');d.appendChild(document.createTextNode(s));return d.innerHTML}
    function showToast(msg){
        var old=document.querySelector('.mg-toast');
        if(old) old.remove();
        var t=document.createElement('div');
        t.className='mg-toast';t.textContent=msg;
        document.body.appendChild(t);
        setTimeout(function(){t.remove()},3200);
    }

    /* Setup */
    var playerCount=3;
    window.addPlayer=function(){
        if(playerCount>=8) return;
        playerCount++;
        var row=document.createElement('div');
        row.className='mg-player-row';
        var defaultName=@json(__('minigame.player_default', ['n' => '__N__'])).replace('__N__', playerCount);
        row.innerHTML='<input type="text" class="form-control p-name" value="'+escHtml(defaultName)+'" maxlength="12">'+
            '<button class="mg-player-remove" onclick="removePlayer(this)">✕</button>';
        document.getElementById('players-list').appendChild(row);
        if(playerCount>=8) document.getElementById('add-player-btn').style.display='none';
    };
    window.removePlayer=function(btn){
        btn.closest('.mg-player-row').remove();
        playerCount--;
        document.getElementById('add-player-btn').style.display='inline-block';
    };

    window.startGame=function(){
        var rows=document.querySelectorAll('.mg-player-row');
        players=[];
        var fallbackName=@json(__('minigame.player_default_short'));
        rows.forEach(function(r,i){
            var nm=r.querySelector('.p-name').value.trim()||(fallbackName+(i+1));
            players.push({name:nm,score:0});
        });
        if(players.length<2){showToast(@json(__('minigame.min_players_2')));return;}
        round=1;
        recentPrompts=[];
        document.getElementById('setup-phase').style.display='none';
        document.getElementById('round-phase').style.display='block';
        newRound();
    };

    function pickPrompt(){
        var tiers=Object.keys(PROMPTS);
        // Avoid immediate repeats within the last few rounds.
        for(var tries=0;tries<12;tries++){
            var tier=tiers[Math.floor(Math.random()*tiers.length)];
            var pool=PROMPTS[tier];
            var text=pool[Math.floor(Math.random()*pool.length)];
            if(recentPrompts.indexOf(text)===-1){
                recentPrompts.push(text);
                if(recentPrompts.length>6) recentPrompts.shift();
                return {tier:tier,text:text};
            }
        }
        var t=tiers[Math.floor(Math.random()*tiers.length)];
        return {tier:t,text:PROMPTS[t][Math.floor(Math.random()*PROMPTS[t].length)]};
    }

    function renderScoreboard(){
        var board=document.getElementById('scoreboard');
        board.style.display='block';
        var sorted=players.slice().sort(function(a,b){return b.score-a.score});
        var top=sorted[0]?sorted[0].score:0;
        var html='';
        sorted.forEach(function(p,i){
            var lead=(top>0&&p.score===top)?' lead':'';
            html+='<div class="wml-board-row'+lead+'">'+
                '<span class="wml-rank">'+(i+1)+'</span>'+
                '<span class="wml-nm">'+escHtml(p.name)+'</span>'+
                '<span class="wml-sc">'+p.score+'</span></div>';
        });
        document.getElementById('scoreboard-rows').innerHTML=html;
    }

    window.voteFor=function(idx){
        players[idx].score++;
        var grid=document.getElementById('vote-grid');
        grid.classList.add('locked');
        var btns=grid.querySelectorAll('.wml-vote-btn');
        btns.forEach(function(b,i){
            if(i===idx) b.classList.add('chosen');
            var sc=b.querySelector('.wml-vote-score');
            if(sc) sc.textContent=players[i].score;
        });
        renderScoreboard();
        document.getElementById('reset-btn').style.display='inline-flex';
        if(round>=FREE_ROUNDS && !IS_PREMIUM){
            document.getElementById('upgrade-notice').style.display='block';
        } else {
            document.getElementById('next-round-btn').style.display='inline-flex';
        }
    };

    function newRound(){
        document.getElementById('next-round-btn').style.display='none';
        document.getElementById('reset-btn').style.display='none';
        document.getElementById('upgrade-notice').style.display='none';

        var roundLabel=@json(__('minigame.round_n', ['n' => '__N__'])).replace('__N__', round);
        document.getElementById('round-badge').textContent=roundLabel;

        var p=pickPrompt();
        var tag=document.getElementById('prompt-tier-tag');
        tag.className='mg-tag '+(TIER_TAG_CLASS[p.tier]||'mg-tag-mild');
        tag.textContent=TIER_LABELS[p.tier]||'';
        document.getElementById('prompt-text').textContent=p.text;

        // Re-trigger reveal animation.
        var card=document.getElementById('prompt-card');
        card.style.animation='none';
        void card.offsetWidth;
        card.style.animation='';

        var grid=document.getElementById('vote-grid');
        grid.classList.remove('locked');
        grid.innerHTML='';
        players.forEach(function(p,i){
            var b=document.createElement('button');
            b.type='button';
            b.className='wml-vote-btn';
            b.onclick=function(){voteFor(i)};
            b.innerHTML='<span>'+escHtml(p.name)+'</span><span class="wml-vote-score">'+p.score+'</span>';
            grid.appendChild(b);
        });
    }

    window.nextRound=function(){
        round++;
        newRound();
        var reduceMotion=window.matchMedia&&window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        document.getElementById('round-badge').scrollIntoView({behavior:reduceMotion?'auto':'smooth',block:'start'});
    };
    window.resetGame=function(){
        document.getElementById('round-phase').style.display='none';
        document.getElementById('scoreboard').style.display='none';
        round=0;
        document.getElementById('setup-phase').style.display='block';
    };
})();
</script>
@endsection
