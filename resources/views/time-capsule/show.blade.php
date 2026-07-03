@extends('layouts.app')
@section('title', $capsule->title . ' — ' . __('games.tc_h1'))
@section('meta_description', __('games.tc_room_meta'))
@section('robots', 'noindex,nofollow')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/minigames.css') }}">
<style>
.tc-state{background:var(--surface);border:1px solid var(--border);border-radius:10px;padding:14px;margin-bottom:18px;text-align:center;font-size:.9rem}
.tc-state.sealed{border-color:#7c3aed;background:rgba(124,58,237,.08)}
.tc-state.locked{border-color:var(--yellow,#facc15);background:rgba(250,204,21,.08)}
.tc-state.open{border-color:var(--green,#4ade80);background:rgba(74,222,128,.08)}
.tc-state .big{font-size:1.1rem;color:var(--gold);font-weight:700;margin-bottom:4px;display:flex;align-items:center;justify-content:center;gap:6px}
.tc-state .big svg{width:20px;height:20px;flex-shrink:0}

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

.tc-locked-msg{padding:40px 20px;text-align:center;color:var(--text-dim)}
.tc-locked-msg svg{width:48px;height:48px;margin:0 auto 12px;display:block;color:var(--gold)}
.tc-locked-msg .countdown{font-size:1.6rem;color:var(--gold);margin:8px 0}

/* Flip-card style countdown digits — decorative companion to the
   translated "N days left" text above it (kept for i18n/a11y) */
.tc-flip-row{display:flex;gap:4px;justify-content:center;margin:10px 0}
.tc-flip-digit{
    display:inline-flex;align-items:center;justify-content:center;
    width:32px;height:42px;background:var(--bg,#0d0f16);border:1px solid var(--border,#2a2f42);
    border-radius:6px;font-size:1.3rem;font-weight:700;color:var(--gold,#d9a441);
    transform-origin:50% 50%;perspective:100px;
    animation:tcFlipIn .5s cubic-bezier(.34,1.56,.64,1) both;
}
@keyframes tcFlipIn{from{opacity:0;transform:rotateX(-90deg)}to{opacity:1;transform:rotateX(0)}}

/* Lock overlay shown briefly while sealing */
.tc-lock-overlay{
    position:fixed;inset:0;z-index:9999;display:flex;align-items:center;justify-content:center;
    flex-direction:column;gap:14px;background:rgba(8,9,14,.82);backdrop-filter:blur(2px);
    opacity:0;animation:tcOverlayIn .2s ease-out forwards;
}
@keyframes tcOverlayIn{to{opacity:1}}
.tc-lock-icon{width:56px;height:56px;color:#a855f7}
.tc-lock-shackle{transform-origin:12px 8px;animation:tcShackleClose .55s .1s cubic-bezier(.34,1.56,.64,1) both}
@keyframes tcShackleClose{from{transform:translateY(-3px) rotate(-14deg)}to{transform:translateY(0) rotate(0deg)}}
.tc-lock-text{color:#c4b5fd;font-size:.9rem;font-weight:600}

@media (prefers-reduced-motion: reduce){
    .tc-flip-digit{animation:none}
    .tc-lock-overlay{animation:none;opacity:1}
    .tc-lock-shackle{animation:none}
}
</style>
@endsection

@section('content')
<div class="mg-show-page">
    <div class="mg-show-header">
        <h1>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" /></svg>
            {{ $capsule->title }}
        </h1>
        <div class="mg-show-meta">{{ __('games.tc_open_date', ['date' => $capsule->open_at->format('Y-m-d')]) }}</div>
        <span class="mg-role-badge {{ $role }}">
            @if($role === 'owner') {{ __('games.tc_role_owner') }}
            @elseif($role === 'partner') {{ __('games.role_partner') }}
            @else {{ __('games.tc_role_viewer') }}
            @endif
        </span>
    </div>

    @if(session('success'))
        <div class="mg-flash success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="mg-flash error">{{ $errors->first() }}</div>
    @endif

    {{-- State banner --}}
    @if(!$capsule->isSealed())
        <div class="tc-state">
            <div class="big">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" /></svg>
                {{ __('games.tc_state_editing') }}
            </div>
            <div>{{ __('games.tc_state_editing_desc', ['date' => $capsule->open_at->format('Y-m-d')]) }}</div>
        </div>
    @elseif(!$capsule->isOpenable())
        @php
            $days = (int) abs(\Carbon\Carbon::today()->diffInDays($capsule->open_at, false));
        @endphp
        <div class="tc-state locked">
            <div class="big">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" /></svg>
                {{ __('games.tc_state_sealed', ['days' => $days]) }}
            </div>
            <div>{{ __('games.tc_state_sealed_desc', ['date' => $capsule->open_at->format('Y-m-d')]) }}</div>
        </div>
    @else
        <div class="tc-state open">
            <div class="big">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.456-2.456L14.25 6l1.035-.259a3.375 3.375 0 0 0 2.456-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456ZM16.894 20.567 16.5 21.75l-.394-1.183a2.25 2.25 0 0 0-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 0 0 1.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 0 0 1.423 1.423l1.183.394-1.183.394a2.25 2.25 0 0 0-1.423 1.423Z" /></svg>
                {{ __('games.tc_state_open') }}
            </div>
            <div>{{ __('games.tc_state_open_desc', ['date' => $capsule->opened_at?->format('Y-m-d') ?? $capsule->open_at->format('Y-m-d')]) }}</div>
        </div>
    @endif

    {{-- Share link (only before seal, for owner/partner) --}}
    @if(!$capsule->isSealed() && in_array($role, ['owner', 'partner']))
        <div class="mg-share-box">
            <div class="mg-share-label">
                <svg style="width:14px;height:14px" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244" /></svg>
                {{ __('games.share_link_label') }}
            </div>
            <div class="mg-share-url" id="share-url">{{ url(route('time-capsule.show', ['shareCode' => $capsule->share_code])) }}</div>
            <button type="button" id="copy-btn">{{ __('games.copy_link') }}</button>
        </div>
    @endif

    {{-- Body: questions --}}
    @if($capsule->isSealed() && !$capsule->isOpenable())
        {{-- Sealed but not yet openable: hide content --}}
        <div class="tc-locked-msg">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" /></svg>
            <div>{{ __('games.tc_locked_hidden') }}</div>
            <div class="countdown">
                @php
                    $days = (int) abs(\Carbon\Carbon::today()->diffInDays($capsule->open_at, false));
                @endphp
                {{ __('games.tc_days_left', ['days' => $days]) }}
            </div>
            <div class="tc-flip-row" aria-hidden="true">
                @foreach(str_split((string) $days) as $i => $digit)
                    <span class="tc-flip-digit" style="animation-delay:{{ $i * 80 }}ms">{{ $digit }}</span>
                @endforeach
            </div>
            <div>{{ __('games.tc_unlock_on', ['date' => $capsule->open_at->format('Y-m-d')]) }}</div>
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
                            <textarea name="answers[{{ $q->id }}]" aria-label="{{ $q->question }}" placeholder="{{ __('games.tc_answer_placeholder') }}" maxlength="1000">{{ old('answers.' . $q->id, $myAnswer ?? '') }}</textarea>
                        </div>
                    </div>
                @endforeach

                <div class="tc-actions">
                    <button type="submit" class="btn btn-gold">
                        <svg style="width:16px;height:16px" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                        {{ __('games.tc_save_btn') }}
                    </button>
                </div>
            </form>

            {{-- Seal button (owner only, separate form) --}}
            @if($role === 'owner')
                <form method="POST" action="{{ route('time-capsule.seal', ['shareCode' => $capsule->share_code]) }}" style="margin-top:12px" id="tc-seal-form" data-confirm="{{ __('games.tc_seal_confirm', ['date' => $capsule->open_at->format('Y-m-d')]) }}" data-sealing-text="{{ __('ui.loading') }}">
                    @csrf
                    <button type="submit" class="btn btn-outline" style="width:100%;padding:12px;border:1px solid #7c3aed;color:#a855f7;background:transparent">
                        <svg style="width:16px;height:16px" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" /></svg>
                        {{ __('games.tc_seal_btn') }}
                    </button>
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
                            <span class="who">{{ __('games.role_owner_short') }}</span>
                            {{ $answerMap[$q->id]['owner'] }}
                        </div>
                    @endif
                    @if(($answerMap[$q->id]['partner'] ?? null))
                        <div class="tc-answered partner">
                            <span class="who">{{ __('games.role_partner_short') }}</span>
                            {{ $answerMap[$q->id]['partner'] }}
                        </div>
                    @endif
                    @if(!($answerMap[$q->id]['owner'] ?? null) && !($answerMap[$q->id]['partner'] ?? null))
                        <div class="tc-q-meta">{{ __('games.tc_no_answers') }}</div>
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
                btn.textContent = @json(__('games.copied'));
                setTimeout(function () { btn.textContent = @json(__('games.copy_link')); }, 2000);
            });
        }
    });
})();

(function () {
    // Seal button: keep the existing confirm() gate, but once confirmed,
    // play a brief "locking" overlay before the form actually navigates away.
    var form = document.getElementById('tc-seal-form');
    if (!form) return;
    var confirmed = false;
    form.addEventListener('submit', function (e) {
        if (confirmed) return;
        e.preventDefault();
        if (!window.confirm(form.getAttribute('data-confirm'))) return;

        var reduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        if (reduced) {
            confirmed = true;
            form.submit();
            return;
        }

        var overlay = document.createElement('div');
        overlay.className = 'tc-lock-overlay';
        overlay.innerHTML =
            '<svg class="tc-lock-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">' +
            '<path class="tc-lock-shackle" stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75" />' +
            '<path stroke-linecap="round" stroke-linejoin="round" d="M5.25 10.5h10.5A2.25 2.25 0 0 1 18 12.75v6.75A2.25 2.25 0 0 1 15.75 21.75H5.25A2.25 2.25 0 0 1 3 19.5v-6.75A2.25 2.25 0 0 1 5.25 10.5Z" />' +
            '</svg>' +
            '<div class="tc-lock-text">' + (form.getAttribute('data-sealing-text') || '') + '</div>';
        document.body.appendChild(overlay);

        setTimeout(function () {
            confirmed = true;
            form.submit();
        }, 700);
    });
})();
</script>
@endsection
