@extends('layouts.app')
@section('title', $list->title . ' — ' . __('games.bl_h1'))
@section('meta_description', __('games.bl_room_meta'))
@section('robots', 'noindex,nofollow')

@section('styles')
<style>
.bl-show{max-width:680px;margin:0 auto;padding:24px 16px 48px}
.bl-header{margin-bottom:24px;text-align:center}
.bl-header h1{font-size:1.4rem;color:var(--gold);margin-bottom:8px;word-break:break-all}
.bl-role{display:inline-block;padding:4px 10px;border-radius:12px;font-size:.75rem;font-weight:600;margin-top:4px}
.bl-role.owner{background:#1e40af;color:#fff}
.bl-role.partner{background:#7c3aed;color:#fff}
.bl-role.viewer{background:#525252;color:#fff}

.bl-share{background:var(--surface);border:1px solid var(--border);border-radius:10px;padding:14px;margin-bottom:20px;font-size:.85rem}
.bl-share .label{color:var(--text-dim);margin-bottom:6px}
.bl-share .url{color:var(--gold);font-family:monospace;word-break:break-all;font-size:.8rem;line-height:1.5}
.bl-share button{margin-top:8px;padding:6px 12px;font-size:.8rem;background:var(--gold);color:var(--bg);border:none;border-radius:6px;cursor:pointer;font-weight:600}

.bl-add{background:var(--surface);border:1px solid var(--border);border-radius:10px;padding:16px;margin-bottom:20px}
.bl-add form{display:flex;gap:8px}
.bl-add input{flex:1;padding:10px 12px;background:var(--bg);border:1px solid var(--border);border-radius:6px;color:var(--text);font-size:.95rem}
.bl-add input:focus{outline:none;border-color:var(--gold)}
.bl-add button{padding:10px 18px;background:var(--gold);color:var(--bg);border:none;border-radius:6px;cursor:pointer;font-weight:600;white-space:nowrap}

.bl-tabs{display:flex;gap:0;margin-bottom:16px;border:1px solid var(--border);border-radius:8px;overflow:hidden;background:var(--bg)}
.bl-tab{flex:1;padding:10px;text-align:center;cursor:pointer;font-size:.85rem;color:var(--text-dim);background:transparent;border:none;transition:all .2s}
.bl-tab.active{background:var(--gold);color:var(--bg);font-weight:600}

.bl-list{display:flex;flex-direction:column;gap:10px}
.bl-item{background:var(--surface);border:1px solid var(--border);border-radius:10px;padding:14px}
.bl-item.agreed{border-color:#22c55e;background:rgba(34,197,94,.08)}
.bl-item.rejected{border-color:#ef4444;background:rgba(239,68,68,.06);opacity:.7}
.bl-item.maybe{border-color:#f59e0b;background:rgba(245,158,11,.06)}
.bl-item-content{font-size:.95rem;line-height:1.5;margin-bottom:8px;word-break:break-all}
.bl-item-meta{display:flex;gap:10px;align-items:center;flex-wrap:wrap;font-size:.78rem;color:var(--text-dim)}
.bl-item-status{padding:2px 8px;border-radius:10px;font-weight:600}
.bl-item-status.pending{background:#374151;color:#d1d5db}
.bl-item-status.agreed{background:#16a34a;color:#fff}
.bl-item-status.rejected{background:#dc2626;color:#fff}
.bl-item-status.maybe{background:#d97706;color:#fff}
.bl-vote-row{display:flex;gap:6px;margin-top:10px}
.bl-vote-btn{flex:1;padding:6px 4px;font-size:.8rem;border:1px solid var(--border);border-radius:6px;background:var(--bg);color:var(--text);cursor:pointer;transition:all .15s}
.bl-vote-btn:hover{border-color:var(--gold)}
.bl-vote-btn.voted-yes{background:#16a34a;color:#fff;border-color:#16a34a}
.bl-vote-btn.voted-no{background:#dc2626;color:#fff;border-color:#dc2626}
.bl-vote-btn.voted-maybe{background:#d97706;color:#fff;border-color:#d97706}
.bl-vote-btn:disabled{cursor:not-allowed;opacity:.5}
.bl-delete{background:none;border:none;color:#ef4444;cursor:pointer;font-size:.78rem;text-decoration:underline}

.bl-empty{text-align:center;padding:40px 20px;color:var(--text-dim)}
.bl-empty .icon{font-size:2.5rem;margin-bottom:8px}

.bl-progress{display:flex;justify-content:space-around;background:var(--surface);border:1px solid var(--border);border-radius:10px;padding:12px;margin-bottom:18px}
.bl-progress-cell{text-align:center}
.bl-progress-num{font-size:1.4rem;color:var(--gold);font-weight:700}
.bl-progress-lbl{font-size:.75rem;color:var(--text-dim);margin-top:2px}
</style>
@endsection

@section('content')
<div class="bl-show">
    <div class="bl-header">
        <h1>📋 {{ $list->title }}</h1>
        <span class="bl-role {{ $role }}">
            @if($role === 'owner') {{ __('games.bl_role_owner') }}
            @elseif($role === 'partner') {{ __('games.role_partner') }}
            @else {{ __('games.bl_role_viewer') }}
            @endif
        </span>
    </div>

    @if(in_array($role, ['owner', 'partner']))
        <div class="bl-share">
            <div class="label">📎 {{ __('games.share_link_label') }}</div>
            <div class="url" id="share-url">{{ url(route('bucket-list.show', ['shareCode' => $list->share_code])) }}</div>
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
                <input type="text" name="content" aria-label="{{ __('games.bl_add_btn') }}" placeholder="{{ __('games.bl_item_placeholder') }}" maxlength="200" required>
                <button type="submit">{{ __('games.bl_add_btn') }}</button>
            </form>
            @error('content') <div style="color:#ef4444;font-size:.8rem;margin-top:6px">{{ $message }}</div> @enderror
        </div>
    @endif

    @if($items->isEmpty())
        <div class="bl-empty">
            <div class="icon">✨</div>
            <div>{{ __('games.bl_empty') }}</div>
        </div>
    @else
        <div class="bl-list">
            @foreach($items as $item)
                @php $status = $item->status(); @endphp
                <div class="bl-item {{ $status }}">
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
</script>
@endsection
