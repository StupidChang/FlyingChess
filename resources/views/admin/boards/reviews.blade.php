@extends('layouts.app')
@section('title', '發佈審核 — 管理後台')
@section('robots', 'noindex,nofollow')
@section('content')
@include('admin._nav')
<div class="container" style="padding-top:24px;padding-bottom:48px">
    <div class="section-head">
        <h1>發佈審核</h1>
        <span class="badge-squares">{{ $boards->total() }} 筆待審</span>
    </div>

    @forelse($boards as $board)
    <article class="admin-card" style="margin-bottom:16px;padding:20px;background:var(--surface);border:1px solid var(--border);border-radius:var(--radius)">
        <div style="display:flex;justify-content:space-between;gap:16px;flex-wrap:wrap;align-items:flex-start">
            <div style="min-width:240px;flex:1">
                <h3 style="margin-bottom:4px">{{ $board->name }}</h3>
                @if($board->description)<p style="color:var(--text-dim);font-size:.9rem">{{ $board->description }}</p>@endif
                <p style="color:var(--text-dim);font-size:.82rem;margin-top:6px">
                    {{ $board->squares_count }} 格 ·
                    作者：{{ $board->user?->name ?? '—' }} ({{ $board->user?->email ?? '—' }}) ·
                    送審於 {{ $board->updated_at->format('Y-m-d H:i') }}
                </p>
            </div>
            <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
                <a href="{{ route('play.board', $board) }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline">預覽遊玩</a>
                <a href="{{ route('admin.boards.edit', $board) }}" class="btn btn-sm btn-outline">編輯</a>
                <form action="{{ route('admin.boards.approve', $board) }}" method="POST">
                    @csrf
                    <button class="btn btn-sm btn-gold">核准上架</button>
                </form>
                <form action="{{ route('admin.boards.reject', $board) }}" method="POST" style="display:flex;gap:6px">
                    @csrf
                    <input type="text" name="publish_note" maxlength="200" placeholder="退回原因（選填）"
                           style="padding:6px 10px;border-radius:var(--radius);border:1px solid var(--border);background:var(--surface2);color:var(--text);font-size:.85rem">
                    <button class="btn btn-sm btn-danger">退回</button>
                </form>
            </div>
        </div>
    </article>
    @empty
    <div class="empty-notice">目前沒有待審核的棋盤。</div>
    @endforelse

    <div style="margin-top:24px">
        {{ $boards->links() }}
    </div>
</div>
@endsection
