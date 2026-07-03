@extends('layouts.app')
@section('title', $list->title . ' — ' . __('games.bl_h1'))
@section('meta_description', __('games.bl_room_meta'))
@section('robots', 'noindex,nofollow')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/minigames.css') }}">
<style>
.bl-add{background:var(--surface);border:1px solid var(--border);border-radius:10px;padding:16px;margin-bottom:20px}
.bl-add form{display:flex;gap:8px}
.bl-add button{padding:10px 18px;background:var(--gold);color:var(--bg);border:none;border-radius:6px;cursor:pointer;font-weight:600;white-space:nowrap;transition:opacity .15s}
.bl-add button:hover{opacity:.85}

.bl-list{display:flex;flex-direction:column;gap:10px}
.bl-item{background:var(--surface);border:1px solid var(--border);border-radius:10px;padding:14px}
.bl-item.agreed{border-color:var(--green,#4ade80);background:rgba(74,222,128,.08)}
.bl-item.rejected{border-color:var(--red,#f87171);background:rgba(248,113,113,.06);opacity:.7}
.bl-item.maybe{border-color:var(--yellow,#facc15);background:rgba(250,204,21,.06)}
.bl-item-content{font-size:.95rem;line-height:1.5;margin-bottom:8px;word-break:break-all}
.bl-item-meta{display:flex;gap:10px;align-items:center;flex-wrap:wrap;font-size:.78rem;color:var(--text-dim)}
.bl-item-status{padding:2px 8px;border-radius:10px;font-weight:600}
.bl-item-status.pending{background:var(--surface2,#1d2130);color:var(--text-dim)}
.bl-item-status.agreed{background:var(--green,#4ade80);color:#062b12}
.bl-item-status.rejected{background:var(--red,#f87171);color:#2a0a0a}
.bl-item-status.maybe{background:var(--yellow,#facc15);color:#422006}
.bl-vote-row{display:flex;gap:6px;margin-top:10px}
.bl-vote-btn{flex:1;padding:6px 4px;font-size:.8rem;border:1px solid var(--border);border-radius:6px;background:var(--bg);color:var(--text);cursor:pointer;transition:all .15s}
.bl-vote-btn:hover{border-color:var(--gold)}
.bl-vote-btn.voted-yes{background:var(--green,#4ade80);color:#062b12;border-color:var(--green,#4ade80)}
.bl-vote-btn.voted-no{background:var(--red,#f87171);color:#2a0a0a;border-color:var(--red,#f87171)}
.bl-vote-btn.voted-maybe{background:var(--yellow,#facc15);color:#422006;border-color:var(--yellow,#facc15)}
.bl-vote-btn:disabled{cursor:not-allowed;opacity:.5}
.bl-delete{background:none;border:none;color:var(--red,#f87171);cursor:pointer;font-size:.78rem;text-decoration:underline}

.bl-empty{text-align:center;padding:40px 20px;color:var(--text-dim)}
.bl-empty svg{width:40px;height:40px;margin:0 auto 8px;display:block;color:var(--gold)}

.bl-progress{display:flex;justify-content:space-around;background:var(--surface);border:1px solid var(--border);border-radius:10px;padding:12px;margin-bottom:18px}
.bl-progress-cell{text-align:center}
.bl-progress-num{font-size:1.4rem;color:var(--gold);font-weight:700}
.bl-progress-lbl{font-size:.75rem;color:var(--text-dim);margin-top:2px}

/* New item entrance — staggered via inline --i custom property */
@keyframes blItemIn{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:translateY(0)}}
.bl-item{animation:blItemIn .4s cubic-bezier(.16,1,.3,1) both;animation-delay:calc(var(--i,0) * 60ms)}

/* Vote button click feedback — quick pop before the form navigates away */
@keyframes blVotePop{0%{transform:scale(1)}40%{transform:scale(1.18)}100%{transform:scale(1)}}
.bl-vote-btn.bl-pop{animation:blVotePop .3s ease-out}

/* Both-sides-agreed celebration — plays once per item per browser */
@keyframes blCelebrate{
    0%{box-shadow:0 0 0 0 rgba(74,222,128,.5);transform:scale(1)}
    40%{box-shadow:0 0 0 8px rgba(74,222,128,0);transform:scale(1.015)}
    100%{box-shadow:0 0 0 0 rgba(74,222,128,0);transform:scale(1)}
}
.bl-item.bl-celebrate{animation:blCelebrate .9s ease-out}

@media (prefers-reduced-motion: reduce){
    .bl-item{animation:none}
    .bl-vote-btn.bl-pop{animation:none}
    .bl-item.bl-celebrate{animation:none}
}
</style>
@endsection

@section('content')
<div class="mg-show-page">
    <div class="mg-show-header">
        <h1>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25Z" /></svg>
            {{ $list->title }}
        </h1>
        <span class="mg-role-badge {{ $role }}">
            @if($role === 'owner') {{ __('games.bl_role_owner') }}
            @elseif($role === 'partner') {{ __('games.role_partner') }}
            @else {{ __('games.bl_role_viewer') }}
            @endif
        </span>
    </div>

    @if(in_array($role, ['owner', 'partner']))
        <div class="mg-share-box">
            <div class="mg-share-label">
                <svg style="width:14px;height:14px" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244" /></svg>
                {{ __('games.share_link_label') }}
            </div>
            <div class="mg-share-url" id="share-url">{{ url(route('bucket-list.show', ['shareCode' => $list->share_code])) }}</div>
            <button type="button" id="copy-btn">{{ __('games.copy_link') }}</button>
        </div>
    @endif

    @php
        $stats = ['total' => $items->count(), 'agreed' => 0, 'pending' => 0, 'rejected' => 0, 'maybe' => 0];
        foreach ($items as $it) { $stats[$it->status()]++; }
    @endphp

    <div class="bl-progress">
        <div class="bl-progress-cell"><div class="bl-progress-num">{{ $stats['total'] }}</div><div class="bl-progress-lbl">{{ __('games.bl_stat_total') }}</div></div>
        <div class="bl-progress-cell"><div class="bl-progress-num">{{ $stats['agreed'] }}</div><div class="bl-progress-lbl">{{ __('games.bl_stat_agreed') }}</div></div>
        <div class="bl-progress-cell"><div class="bl-progress-num">{{ $stats['pending'] }}</div><div class="bl-progress-lbl">{{ __('games.bl_stat_pending') }}</div></div>
        <div class="bl-progress-cell"><div class="bl-progress-num">{{ $stats['rejected'] }}</div><div class="bl-progress-lbl">{{ __('games.bl_stat_rejected') }}</div></div>
    </div>

    @if(in_array($role, ['owner', 'partner']))
        <div class="bl-add">
            <form method="POST" action="{{ route('bucket-list.items.add', ['shareCode' => $list->share_code]) }}">
                @csrf
                <input type="text" class="form-control" name="content" aria-label="{{ __('games.bl_add_btn') }}" placeholder="{{ __('games.bl_item_placeholder') }}" maxlength="200" required>
                <button type="submit">{{ __('games.bl_add_btn') }}</button>
            </form>
            @error('content') <div class="mg-error" style="margin-top:6px;margin-bottom:0">{{ $message }}</div> @enderror
        </div>
    @endif

    @if($items->isEmpty())
        <div class="bl-empty">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.456-2.456L14.25 6l1.035-.259a3.375 3.375 0 0 0 2.456-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456ZM16.894 20.567 16.5 21.75l-.394-1.183a2.25 2.25 0 0 0-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 0 0 1.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 0 0 1.423 1.423l1.183.394-1.183.394a2.25 2.25 0 0 0-1.423 1.423Z" /></svg>
            <div>{{ __('games.bl_empty') }}</div>
        </div>
    @else
        <div class="bl-list">
            @foreach($items as $item)
                @php $status = $item->status(); @endphp
                <div class="bl-item {{ $status }}" data-item-id="{{ $item->id }}" data-status="{{ $status }}" style="--i:{{ $loop->index }}">
                    <div class="bl-item-content">{{ $item->content }}</div>
                    <div class="bl-item-meta">
                        <span class="bl-item-status {{ $status }}">
                            @if($status === 'agreed') ✅ {{ __('games.bl_status_agreed') }}
                            @elseif($status === 'rejected') ❌ {{ __('games.bl_status_rejected') }}
                            @elseif($status === 'maybe') 💭 {{ __('games.bl_status_maybe') }}
                            @else ⏳ {{ __('games.bl_status_pending') }}
                            @endif
                        </span>
                        <span>{{ __('games.bl_proposed_by', ['name' => $item->proposer === 'owner' ? __('games.role_owner_short') : __('games.role_partner_short')]) }}</span>
                        @if(in_array($role, ['owner', 'partner']) && $item->proposer === $role)
                            <form method="POST" action="{{ route('bucket-list.items.delete', ['shareCode' => $list->share_code, 'itemId' => $item->id]) }}" style="display:inline;margin-left:auto" onsubmit="return confirm(@json(__('games.confirm_delete_item')))">
                                @csrf @method('DELETE')
                                <button type="submit" class="bl-delete">{{ __('games.delete_btn') }}</button>
                            </form>
                        @endif
                    </div>
                    @if(in_array($role, ['owner', 'partner']))
                        @php $myVote = $role === 'owner' ? $item->owner_vote : $item->partner_vote; @endphp
                        <form method="POST" action="{{ route('bucket-list.items.vote', ['shareCode' => $list->share_code, 'itemId' => $item->id]) }}">
                            @csrf
                            <div class="bl-vote-row">
                                <button type="submit" name="vote" value="yes"   class="bl-vote-btn @if($myVote==='yes')voted-yes @endif">👍 {{ __('games.bl_vote_yes') }}</button>
                                <button type="submit" name="vote" value="maybe" class="bl-vote-btn @if($myVote==='maybe')voted-maybe @endif">💭 {{ __('games.bl_vote_maybe') }}</button>
                                <button type="submit" name="vote" value="no"    class="bl-vote-btn @if($myVote==='no')voted-no @endif">👎 {{ __('games.bl_vote_no') }}</button>
                            </div>
                        </form>
                    @endif
                </div>
            @endforeach
        </div>
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
    // Quick pop feedback on vote click — the form still submits/reloads normally,
    // this just gives an instant tactile response while the request is in flight.
    document.querySelectorAll('.bl-vote-btn').forEach(function (b) {
        b.addEventListener('click', function () {
            b.classList.add('bl-pop');
        });
    });

    // Celebrate "both agreed" items — but only the first time this browser
    // sees each item reach that state, tracked via localStorage so a later
    // reload of an already-agreed item doesn't replay the highlight forever.
    try {
        var KEY = 'bl_celebrated_ids';
        var seen = JSON.parse(localStorage.getItem(KEY) || '[]');
        var agreedEls = document.querySelectorAll('.bl-item[data-status="agreed"]');
        var changed = false;
        agreedEls.forEach(function (el) {
            var id = el.getAttribute('data-item-id');
            if (seen.indexOf(id) === -1) {
                el.classList.add('bl-celebrate');
                seen.push(id);
                changed = true;
            }
        });
        if (changed) localStorage.setItem(KEY, JSON.stringify(seen));
    } catch (e) { /* localStorage unavailable — skip celebration, no functional impact */ }
})();
</script>
@endsection
