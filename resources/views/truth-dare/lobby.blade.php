@extends('layouts.app')
@section('title', __('seo.truth_dare_title') . ' — ' . __('ui.site_name'))
@section('meta_description', __('seo.truth_dare_description'))
@section('og_title', __('seo.truth_dare_title') . ' — ' . __('ui.site_name'))
@section('og_description', __('seo.truth_dare_description'))
@section('canonical', route('truth-dare.lobby'))

@section('schema')
    @include('partials.game-schema', [
        'gameName' => __('games.truth_dare'),
        'gameDescription' => __('games.desc_truth_dare'),
        'gamePath' => 'truth-dare',
        'minPlayers' => 1,
        'maxPlayers' => 6,
    ])
    @include('partials.game-faq-schema', ['faqKey' => 'truth-dare'])
@endsection

@section('faq')
    @include('partials.game-faq', ['faqKey' => 'truth-dare'])
@endsection

@section('styles')
<link rel="stylesheet" href="{{ asset_v('css/minigames.css') }}">
<style>
.td-start-page{max-width:520px;margin:0 auto;padding:40px 16px;min-height:calc(100vh - 56px);display:flex;flex-direction:column;align-items:center;justify-content:center}
.td-start-hero{text-align:center;margin-bottom:32px}
.td-start-hero h1{font-size:1.6rem;color:var(--gold);margin-bottom:8px;display:flex;align-items:center;justify-content:center;gap:8px}
.td-start-hero h1 svg{width:24px;height:24px;flex-shrink:0}
.td-start-hero p{color:var(--text-dim);font-size:.9rem;line-height:1.6}
.td-start-form{background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:24px;width:100%}
.td-start-form h2{color:var(--gold);font-size:1.1rem;margin-bottom:16px;text-align:center}
.td-start-form .form-group{margin-bottom:16px}
.td-start-form .btn-submit{width:100%;font-size:1.1rem;padding:12px}

/* 開局前的兩則提示。人數那則會跟著玩家列增減改字,付費那則只在還沒解鎖時出現。 */
.td-hint{font-size:.8rem;line-height:1.6;color:var(--text-dim);margin:14px 0}
.td-hint-mode{padding:10px 12px;border-radius:8px;background:var(--bg);border:1px solid var(--border)}
.td-hint-tier{padding:12px;border-radius:8px;
  background:rgba(217,164,65,.07);border:1px solid rgba(217,164,65,.35)}
.td-hint-tier p{margin-bottom:10px;color:var(--text)}
.td-hint-tier .btn{width:100%}
.td-hint-unlocked{color:var(--gold);text-align:center}

.td-mode-desc{font-size:.8rem;color:var(--text-dim);text-align:center;margin-top:-12px;margin-bottom:16px;min-height:1.2em}
.td-mode-desc.adult-desc{color:var(--red,#f87171)}
</style>
@endsection

@section('content')
<div class="td-start-page">
    <div class="td-start-hero">
        <h1>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3-3c-1.354 0-2.694-.055-4.02-.163a2.115 2.115 0 0 1-.825-.242m9.345-8.334a2.126 2.126 0 0 0-.476-.095 48.64 48.64 0 0 0-8.048 0c-1.131.094-1.976 1.057-1.976 2.192v4.286c0 .837.46 1.58 1.155 1.951m9.345-8.334V6.637c0-1.621-1.152-3.026-2.76-3.235A48.455 48.455 0 0 0 11.25 3c-2.115 0-4.198.137-6.24.402-1.608.209-2.76 1.614-2.76 3.235v6.226c0 1.621 1.152 3.024 2.76 3.234.577.075 1.157.14 1.74.194V21l4.155-4.155" />
            </svg>
            {{ __('games.truth_dare') }}
        </h1>
        <p>{{ __('games.td_start_intro') }}</p>
    </div>

    <div class="td-start-form">
        <div class="mg-cat-preview-grid">
            <div class="mg-cat-preview">
                <svg class="mg-cat-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3-3c-1.354 0-2.694-.055-4.02-.163a2.115 2.115 0 0 1-.825-.242m9.345-8.334a2.126 2.126 0 0 0-.476-.095 48.64 48.64 0 0 0-8.048 0c-1.131.094-1.976 1.057-1.976 2.192v4.286c0 .837.46 1.58 1.155 1.951m9.345-8.334V6.637c0-1.621-1.152-3.026-2.76-3.235A48.455 48.455 0 0 0 11.25 3c-2.115 0-4.198.137-6.24.402-1.608.209-2.76 1.614-2.76 3.235v6.226c0 1.621 1.152 3.024 2.76 3.234.577.075 1.157.14 1.74.194V21l4.155-4.155" /></svg>
                <div class="label">{{ __('games.td_cat_truth_adult') }}</div>
            </div>
            <div class="mg-cat-preview">
                <svg class="mg-cat-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.362 5.214A8.252 8.252 0 0 1 12 21 8.25 8.25 0 0 1 6.038 7.047 8.287 8.287 0 0 0 9 9.601a8.983 8.983 0 0 1 3.361-6.867 8.21 8.21 0 0 0 3 2.48Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M12 18a3.75 3.75 0 0 0 .495-7.467 5.99 5.99 0 0 0-1.925 3.546 5.974 5.974 0 0 1-2.133-1.001A3.75 3.75 0 0 0 12 18Z" /></svg>
                <div class="label">{{ __('games.td_cat_dare_adult') }}</div>
            </div>
        </div>

        <form action="{{ route('truth-dare.create') }}" method="POST" id="td-create-form">
            @csrf
            <input type="hidden" name="tab_id" id="td-create-tab-id">

            {{-- Same-device play: enter everyone taking turns on this one device --}}
            <div class="mg-setup" style="background:none;border:none;padding:0;margin:0">
                <h2 class="mg-setup-heading">{{ __('minigame.players_setup') }}</h2>
                <div id="td-players-list">
                    <div class="mg-player-row">
                        <input type="text" name="players[]" class="form-control p-name" value="{{ __('minigame.player_default', ['n' => 1]) }}" maxlength="18">
                    </div>
                    <div class="mg-player-row">
                        <input type="text" name="players[]" class="form-control p-name" value="{{ __('minigame.player_default', ['n' => 2]) }}" maxlength="18">
                    </div>
                </div>
                <button type="button" class="btn btn-sm btn-outline mg-add-player" id="td-add-player" onclick="tdAddPlayer()">{{ __('minigame.add_player') }}</button>
            </div>

            {{-- 場合不另外問 —— 玩家名字本來就要一個一個加,人數已經知道了。
                 這裡只提示人數會影響題目,不然使用者不會知道加一個人題目就換了一套。 --}}
            <p class="td-hint td-hint-mode">{{ __('games.td_mode_hint') }}</p>

            @include('partials.escalate-toggle')

            {{-- 免費與付費的差別。放在開局前而不是抽到鎖住的題目才說 ——
                 玩到一半被擋下來,毀掉的是整場氣氛。 --}}
            @if(! \App\Support\PremiumAccess::content(auth()->user()))
            <div class="td-hint td-hint-tier">
                <p>{{ __('games.td_tier_hint') }}</p>
                <button type="button" class="btn btn-sm btn-gold"
                        onclick="window.rewardedUnlockOpen && rewardedUnlockOpen()">
                    {{ __('minigame.rewarded_cta', ['minutes' => \App\Support\PremiumAccess::rewardedMinutes()]) }}
                </button>
            </div>
            @else
            <p class="td-hint td-hint-unlocked">{{ __('games.td_tier_unlocked') }}</p>
            @endif

            {{-- 18+ only — normal mode removed; the whole site is adults-only --}}
            <p class="td-mode-desc adult-desc" id="mode-desc">{{ __('games.td_mode_adult_desc') }}</p>

            <button type="submit" class="btn btn-gold btn-submit">{{ __('games.td_start_button') }}</button>
        </form>

        @if($errors->any())
        <div class="mg-error" style="text-align:center">
            @foreach($errors->all() as $e)<p>{{ $e }}</p>@endforeach
        </div>
        @endif
    </div>
</div>

@include('partials.ad-unit', ['zone' => 'lobby_side'])
@include('partials.rewarded-unlock', ['barHidden' => true])
@endsection

@section('scripts')
{{-- 玩家頭像:自己盯著玩家列補上挑選器,各遊戲不用改自己的產生邏輯 --}}
<script src="{{ asset_v('js/player-avatar.js') }}"></script>
<script>
(function() {
    if (!sessionStorage.getItem('tab_id')) {
        sessionStorage.setItem('tab_id', Math.random().toString(36).slice(2, 11));
    }
    var el = document.getElementById('td-create-tab-id');
    if (el) el.value = sessionStorage.getItem('tab_id');
})();

// Same-device player rows (2–6)
var tdPlayerCount = 2;
function escHtmlTd(s){var d=document.createElement('div');d.appendChild(document.createTextNode(s));return d.innerHTML}
window.tdAddPlayer = function(){
    if (tdPlayerCount >= 6) return;
    tdPlayerCount++;
    var row = document.createElement('div');
    row.className = 'mg-player-row';
    var name = @json(__('minigame.player_default', ['n' => '__N__'])).replace('__N__', tdPlayerCount);
    row.innerHTML = '<input type="text" name="players[]" class="form-control p-name" value="'+escHtmlTd(name)+'" maxlength="18">'+
        '<button type="button" class="mg-player-remove" onclick="tdRemovePlayer(this)">✕</button>';
    document.getElementById('td-players-list').appendChild(row);
    if (tdPlayerCount >= 6) document.getElementById('td-add-player').style.display = 'none';
};
window.tdRemovePlayer = function(btn){
    btn.closest('.mg-player-row').remove();
    tdPlayerCount--;
    document.getElementById('td-add-player').style.display = 'inline-block';
};
</script>
@endsection
