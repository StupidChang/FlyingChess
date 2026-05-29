@extends('layouts.app')
@section('title', $capsule->title . ' — 情侶時間膠囊')
@section('meta_description', '情侶時間膠囊')
@section('robots', 'noindex,nofollow')

@section('styles')
<style>
.tc-show{max-width:680px;margin:0 auto;padding:24px 16px 48px}
.tc-header{margin-bottom:20px;text-align:center}
.tc-header h1{font-size:1.4rem;color:var(--gold);margin-bottom:8px;word-break:break-all}
.tc-meta{color:var(--text-dim);font-size:.85rem;margin-top:6px}
.tc-role{display:inline-block;padding:4px 10px;border-radius:12px;font-size:.75rem;font-weight:600;margin-top:6px}
.tc-role.owner{background:#1e40af;color:#fff}
.tc-role.partner{background:#7c3aed;color:#fff}
.tc-role.viewer{background:#525252;color:#fff}

.tc-state{background:var(--surface);border:1px solid var(--border);border-radius:10px;padding:14px;margin-bottom:18px;text-align:center;font-size:.9rem}
.tc-state.sealed{border-color:#7c3aed;background:rgba(124,58,237,.08)}
.tc-state.locked{border-color:#f59e0b;background:rgba(245,158,11,.08)}
.tc-state.open{border-color:#22c55e;background:rgba(34,197,94,.08)}
.tc-state .big{font-size:1.1rem;color:var(--gold);font-weight:700;margin-bottom:4px}

.tc-share{background:var(--surface);border:1px solid var(--border);border-radius:10px;padding:14px;margin-bottom:18px;font-size:.85rem}
.tc-share .label{color:var(--text-dim);margin-bottom:6px}
.tc-share .url{color:var(--gold);font-family:monospace;word-break:break-all;font-size:.78rem;line-height:1.5}
.tc-share button{margin-top:8px;padding:6px 12px;font-size:.8rem;background:var(--gold);color:var(--bg);border:none;border-radius:6px;cursor:pointer;font-weight:600}

.tc-question{background:var(--surface);border:1px solid var(--border);border-radius:10px;padding:18px;margin-bottom:14px}
.tc-q-num{display:inline-block;background:var(--gold);color:var(--bg);width:28px;height:28px;line-height:28px;text-align:center;border-radius:50%;font-weight:700;font-size:.85rem;margin-right:8px}
.tc-q-text{display:inline;font-size:.95rem;color:var(--text);line-height:1.5}
.tc-q-meta{margin-top:6px;color:var(--text-dim);font-size:.8rem;margin-left:36px}
.tc-answer{margin-top:12px}
.tc-answer textarea{width:100%;padding:10px;background:var(--bg);border:1px solid var(--border);border-radius:6px;color:var(--text);font-size:.9rem;font-family:inherit;resize:vertical;min-height:64px}
.tc-answer textarea:focus{outline:none;border-color:var(--gold)}
.tc-answered{margin-top:10px;padding:10px;background:var(--bg);border-left:3px solid var(--gold);border-radius:4px;font-size:.9rem;line-height:1.5;white-space:pre-wrap;word-break:break-all;color:var(--text)}
.tc-answered.partner{border-left-color:#a855f7}
.tc-answered .who{display:block;font-size:.75rem;color:var(--text-dim);margin-bottom:4px;font-weight:600}

.tc-actions{position:sticky;bottom:0;background:var(--bg);padding:16px 0;border-top:1px solid var(--border);margin-top:20px;display:flex;gap:10px}
.tc-actions button{flex:1;padding:12px;font-size:.95rem}

.tc-flash{background:#1e3a8a;color:#bfdbfe;padding:10px 14px;border-radius:8px;margin-bottom:16px;font-size:.9rem}
.tc-flash.success{background:#14532d;color:#bbf7d0}
.tc-flash.error{background:#7f1d1d;color:#fecaca}

.tc-locked-msg{padding:40px 20px;text-align:center;color:var(--text-dim)}
.tc-locked-msg .icon{font-size:3rem;margin-bottom:12px}
.tc-locked-msg .countdown{font-size:1.6rem;color:var(--gold);margin:8px 0}
</style>
@endsection

@section('content')
<div class="tc-show">
    <div class="tc-header">
        <h1>📦 {{ $capsule->title }}</h1>
        <div class="tc-meta">開封日：{{ $capsule->open_at->format('Y-m-d') }}</div>
        <span class="tc-role {{ $role }}">
            @if($role === 'owner') 你是膠囊創建者
            @elseif($role === 'partner') 你是夥伴
            @else 訪客模式
            @endif
        </span>
    </div>

    @if(session('success'))
        <div class="tc-flash success">✓ {{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="tc-flash error">⚠ {{ $errors->first() }}</div>
    @endif

    {{-- State banner --}}
    @if(!$capsule->isSealed())
        <div class="tc-state">
            <div class="big">📝 編輯中</div>
            <div>填寫回答後請創建者按「封存」鎖定。封存後直到 {{ $capsule->open_at->format('Y-m-d') }} 才能再次查看</div>
        </div>
    @elseif(!$capsule->isOpenable())
        @php
            $days = (int) abs(\Carbon\Carbon::today()->diffInDays($capsule->open_at, false));
        @endphp
        <div class="tc-state locked">
            <div class="big">🔒 已封存，倒數 {{ $days }} 天</div>
            <div>於 {{ $capsule->open_at->format('Y-m-d') }} 開封</div>
        </div>
    @else
        <div class="tc-state open">
            <div class="big">🎉 已開封</div>
            <div>{{ $capsule->opened_at?->format('Y-m-d') ?? $capsule->open_at->format('Y-m-d') }} 已開啟</div>
        </div>
    @endif

    {{-- Share link (only before seal, for owner/partner) --}}
    @if(!$capsule->isSealed() && in_array($role, ['owner', 'partner']))
        <div class="tc-share">
            <div class="label">📎 分享連結（傳給另一半）</div>
            <div class="url" id="share-url">{{ url(route('time-capsule.show', ['shareCode' => $capsule->share_code])) }}</div>
            <button type="button" id="copy-btn">複製連結</button>
        </div>
    @endif

    {{-- Body: questions --}}
    @if($capsule->isSealed() && !$capsule->isOpenable())
        {{-- Sealed but not yet openable: hide content --}}
        <div class="tc-locked-msg">
            <div class="icon">🔐</div>
            <div>膠囊已封存，內容隱藏中</div>
            <div class="countdown">
                @php
                    $days = (int) abs(\Carbon\Carbon::today()->diffInDays($capsule->open_at, false));
                @endphp
                還有 {{ $days }} 天
            </div>
            <div>到 {{ $capsule->open_at->format('Y-m-d') }} 才會解鎖</div>
        </div>
    @else
        {{-- Editable form (before seal) OR opened display (after open_at) --}}
        @if(!$capsule->isSealed() && in_array($role, ['owner', 'partner']))
            <form method="POST" action="{{ route('time-capsule.answers', ['shareCode' => $capsule->share_code]) }}">
                @csrf
                @foreach($questions as $i => $q)
                    @php
                        $myAnswer = $answerMap[$q->id][$role] ?? null;
                        $otherRole = $role === 'owner' ? 'partner' : 'owner';
                    @endphp
                    <div class="tc-question">
                        <div>
                            <span class="tc-q-num">{{ $i + 1 }}</span>
                            <span class="tc-q-text">{{ $q->question }}</span>
                        </div>
                        <div class="tc-answer">
                            <textarea name="answers[{{ $q->id }}]" placeholder="寫下你的回答..." maxlength="1000">{{ old('answers.' . $q->id, $myAnswer ?? '') }}</textarea>
                        </div>
                    </div>
                @endforeach

                <div class="tc-actions">
                    <button type="submit" class="btn btn-gold">💾 儲存回答</button>
                </div>
            </form>

            {{-- Seal button (owner only, separate form) --}}
            @if($role === 'owner')
                <form method="POST" action="{{ route('time-capsule.seal', ['shareCode' => $capsule->share_code]) }}" style="margin-top:12px" onsubmit="return confirm('封存後不能再修改，直到 {{ $capsule->open_at->format('Y-m-d') }} 才能查看內容。確定要封存嗎？')">
                    @csrf
                    <button type="submit" class="btn btn-outline" style="width:100%;padding:12px;border:1px solid #7c3aed;color:#a855f7;background:transparent">🔒 封存膠囊</button>
                </form>
            @endif
        @else
            {{-- Read-only display (opened or viewer) --}}
            @foreach($questions as $i => $q)
                <div class="tc-question">
                    <div>
                        <span class="tc-q-num">{{ $i + 1 }}</span>
                        <span class="tc-q-text">{{ $q->question }}</span>
                    </div>
                    @if(($answerMap[$q->id]['owner'] ?? null))
                        <div class="tc-answered">
                            <span class="who">創建者</span>
                            {{ $answerMap[$q->id]['owner'] }}
                        </div>
                    @endif
                    @if(($answerMap[$q->id]['partner'] ?? null))
                        <div class="tc-answered partner">
                            <span class="who">夥伴</span>
                            {{ $answerMap[$q->id]['partner'] }}
                        </div>
                    @endif
                    @if(!($answerMap[$q->id]['owner'] ?? null) && !($answerMap[$q->id]['partner'] ?? null))
                        <div class="tc-q-meta">（兩人都未作答）</div>
                    @endif
                </div>
            @endforeach
        @endif
    @endif
</div>

<script>
(function () {
    var btn = document.getElementById('copy-btn');
    if (!btn) return;
    btn.addEventListener('click', function () {
        var url = document.getElementById('share-url').textContent.trim();
        if (navigator.clipboard) {
            navigator.clipboard.writeText(url).then(function () {
                btn.textContent = '已複製 ✓';
                setTimeout(function () { btn.textContent = '複製連結'; }, 2000);
            });
        }
    });
})();
</script>
@endsection
