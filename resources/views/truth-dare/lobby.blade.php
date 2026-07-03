@extends('layouts.app')
@section('title', __('seo.truth_dare_title') . ' — ' . __('ui.site_name'))
@section('meta_description', __('seo.truth_dare_description'))
@section('og_title', __('seo.truth_dare_title') . ' — ' . __('ui.site_name'))
@section('og_description', __('seo.truth_dare_description'))
@section('canonical', route('truth-dare.lobby'))

@section('styles')
<link rel="stylesheet" href="{{ asset('css/minigames.css') }}">
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

.td-mode-toggle{display:flex;gap:0;margin-bottom:20px;border:1px solid var(--border);border-radius:8px;overflow:hidden}
.td-mode-toggle label{flex:1;text-align:center;padding:10px 12px;cursor:pointer;font-size:.9rem;color:var(--text-dim);background:var(--bg);transition:background .2s,color .2s;user-select:none}
.td-mode-toggle input{display:none}
.td-mode-toggle input:checked + label{background:var(--gold);color:var(--bg);font-weight:600}
.td-mode-toggle input#mode-adult:checked + label{background:var(--red,#f87171);color:#2a0a0a}
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
                <div class="label">{{ __('games.td_cat_truth') }}</div>
            </div>
            <div class="mg-cat-preview">
                <svg class="mg-cat-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.362 5.214A8.252 8.252 0 0 1 12 21 8.25 8.25 0 0 1 6.038 7.047 8.287 8.287 0 0 0 9 9.601a8.983 8.983 0 0 1 3.361-6.867 8.21 8.21 0 0 0 3 2.48Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M12 18a3.75 3.75 0 0 0 .495-7.467 5.99 5.99 0 0 0-1.925 3.546 5.974 5.974 0 0 1-2.133-1.001A3.75 3.75 0 0 0 12 18Z" /></svg>
                <div class="label">{{ __('games.td_cat_dare') }}</div>
            </div>
            <div class="mg-cat-preview">
                <svg class="mg-cat-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" /></svg>
                <div class="label">{{ __('games.td_cat_couple') }}</div>
            </div>
            <div class="mg-cat-preview">
                <svg class="mg-cat-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.456-2.456L14.25 6l1.035-.259a3.375 3.375 0 0 0 2.456-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456ZM16.894 20.567 16.5 21.75l-.394-1.183a2.25 2.25 0 0 0-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 0 0 1.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 0 0 1.423 1.423l1.183.394-1.183.394a2.25 2.25 0 0 0-1.423 1.423Z" /></svg>
                <div class="label">{{ __('games.td_cat_party') }}</div>
            </div>
        </div>

        <form action="{{ route('truth-dare.create') }}" method="POST" id="td-create-form">
            @csrf
            <input type="hidden" name="tab_id" id="td-create-tab-id">
            <input type="hidden" name="player_name" value="{{ __('games.td_player_default') }}">

            {{-- 18+ Mode Toggle --}}
            <div class="td-mode-toggle">
                <input type="radio" name="is_adult" id="mode-normal" value="0" checked>
                <label for="mode-normal">{{ __('games.td_mode_normal') }}</label>
                <input type="radio" name="is_adult" id="mode-adult" value="1"
                       {{ old('is_adult') == '1' ? 'checked' : '' }}>
                <label for="mode-adult">{{ __('games.td_mode_adult') }}</label>
            </div>
            <p class="td-mode-desc" id="mode-desc">{{ __('games.td_mode_normal_desc') }}</p>

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
@endsection

@section('scripts')
<script>
(function() {
    if (!sessionStorage.getItem('tab_id')) {
        sessionStorage.setItem('tab_id', Math.random().toString(36).slice(2, 11));
    }
    var el = document.getElementById('td-create-tab-id');
    if (el) el.value = sessionStorage.getItem('tab_id');
})();
var TD_LABELS = {
    normal: {
        desc: @json(__('games.td_mode_normal_desc')),
        labels: [
            @json(__('games.td_cat_truth')),
            @json(__('games.td_cat_dare')),
            @json(__('games.td_cat_couple')),
            @json(__('games.td_cat_party')),
        ],
    },
    adult: {
        desc: @json(__('games.td_mode_adult_desc')),
        labels: [
            @json(__('games.td_cat_truth_adult')),
            @json(__('games.td_cat_dare_adult')),
            @json(__('games.td_cat_couple_adult')),
            @json(__('games.td_cat_party_adult')),
        ],
    },
};
document.querySelectorAll('input[name="is_adult"]').forEach(function(radio) {
    radio.addEventListener('change', function() {
        var desc = document.getElementById('mode-desc');
        var isAdult = this.value === '1';
        var pack = isAdult ? TD_LABELS.adult : TD_LABELS.normal;
        desc.textContent = pack.desc;
        desc.classList.toggle('adult-desc', isAdult);
        // Icons stay fixed per category (truth/dare/couple/party); only the
        // wording swaps between normal and 18+ phrasing.
        var labels = document.querySelectorAll('.mg-cat-preview .label');
        labels.forEach(function(el, i) { el.textContent = pack.labels[i]; });
    });
});
</script>
@endsection
