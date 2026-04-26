@extends('layouts.app')
@section('title', '真心話大冒險 — 線上派對遊戲')
@section('meta_description', '真心話大冒險線上版，輸入暱稱即刻開始！情侶升溫、朋友聚會必玩，免費暢玩。')
@section('robots', 'noindex,nofollow')

@section('styles')
<style>
.td-start-page{max-width:520px;margin:0 auto;padding:40px 16px;min-height:calc(100vh - 56px);display:flex;flex-direction:column;align-items:center;justify-content:center}
.td-start-hero{text-align:center;margin-bottom:32px}
.td-start-hero h1{font-size:1.6rem;color:var(--gold);margin-bottom:8px}
.td-start-hero p{color:var(--text-dim);font-size:.9rem;line-height:1.6}
.td-start-form{background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:24px;width:100%}
.td-start-form h2{color:var(--gold);font-size:1.1rem;margin-bottom:16px;text-align:center}
.td-start-form .form-group{margin-bottom:16px}
.td-start-form .btn-submit{width:100%;font-size:1.1rem;padding:12px}
.td-categories-preview{display:grid;grid-template-columns:repeat(2,1fr);gap:12px;margin-bottom:24px}
.td-cat-preview{background:var(--bg);border:1px solid var(--border);border-radius:8px;padding:12px;text-align:center}
.td-cat-preview .icon{font-size:1.5rem;margin-bottom:4px}
.td-cat-preview .label{font-size:.85rem;color:var(--text)}

.td-mode-toggle{display:flex;gap:0;margin-bottom:20px;border:1px solid var(--border);border-radius:8px;overflow:hidden}
.td-mode-toggle label{flex:1;text-align:center;padding:10px 12px;cursor:pointer;font-size:.9rem;color:var(--text-dim);background:var(--bg);transition:background .2s,color .2s;user-select:none}
.td-mode-toggle input{display:none}
.td-mode-toggle input:checked + label{background:var(--gold);color:var(--bg);font-weight:600}
.td-mode-toggle input#mode-adult:checked + label{background:#dc2626;color:#fff}
.td-mode-desc{font-size:.8rem;color:var(--text-dim);text-align:center;margin-top:-12px;margin-bottom:16px;min-height:1.2em}
.td-mode-desc.adult-desc{color:#f87171}
</style>
@endsection

@section('content')
<div class="td-start-page">
    <div class="td-start-hero">
        <h1>💬 真心話大冒險</h1>
        <p>選擇版本，立即開始！</p>
    </div>

    <div class="td-start-form">
        <div class="td-categories-preview">
            <div class="td-cat-preview">
                <div class="icon">💬</div>
                <div class="label">真心話</div>
            </div>
            <div class="td-cat-preview">
                <div class="icon">🎯</div>
                <div class="label">大冒險</div>
            </div>
            <div class="td-cat-preview">
                <div class="icon">💕</div>
                <div class="label">情侶題</div>
            </div>
            <div class="td-cat-preview">
                <div class="icon">🎉</div>
                <div class="label">派對題</div>
            </div>
        </div>

        <form action="{{ route('truth-dare.create') }}" method="POST" id="td-create-form">
            @csrf
            <input type="hidden" name="tab_id" id="td-create-tab-id">
            <input type="hidden" name="player_name" value="玩家">

            {{-- 18+ Mode Toggle --}}
            <div class="td-mode-toggle">
                <input type="radio" name="is_adult" id="mode-normal" value="0" checked>
                <label for="mode-normal">🌸 一般版</label>
                <input type="radio" name="is_adult" id="mode-adult" value="1"
                       {{ old('is_adult') == '1' ? 'checked' : '' }}>
                <label for="mode-adult">🔞 18禁版</label>
            </div>
            <p class="td-mode-desc" id="mode-desc">適合朋友聚會的輕鬆題目</p>

            <button type="submit" class="btn btn-gold btn-submit">🎲 開始遊戲</button>
        </form>

        @if($errors->any())
        <div style="color:#f87171;font-size:.85rem;margin-top:10px;text-align:center">
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
document.querySelectorAll('input[name="is_adult"]').forEach(function(radio) {
    radio.addEventListener('change', function() {
        var desc = document.getElementById('mode-desc');
        var isAdult = this.value === '1';
        desc.textContent = isAdult
            ? '包含情趣挑戰、親密互動題目，僅限 18 歲以上玩家'
            : '適合朋友聚會的輕鬆題目';
        desc.classList.toggle('adult-desc', isAdult);
        // Update category previews
        var labels = document.querySelectorAll('.td-cat-preview .label');
        var icons = document.querySelectorAll('.td-cat-preview .icon');
        if (isAdult) {
            labels[0].textContent = '私密真心話';
            labels[1].textContent = '大膽挑戰';
            labels[2].textContent = '情趣互動';
            labels[3].textContent = '限制級派對';
            icons[0].textContent = '🔥';
            icons[1].textContent = '😈';
            icons[2].textContent = '💋';
            icons[3].textContent = '🍷';
        } else {
            labels[0].textContent = '真心話';
            labels[1].textContent = '大冒險';
            labels[2].textContent = '情侶題';
            labels[3].textContent = '派對題';
            icons[0].textContent = '💬';
            icons[1].textContent = '🎯';
            icons[2].textContent = '💕';
            icons[3].textContent = '🎉';
        }
    });
});
</script>
@endsection
