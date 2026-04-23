@extends('layouts.app')
@section('title','棋盤管理')
@section('content')
<div class="container" style="padding-top:40px">
    <div class="section-head">
        <h1>我的棋盤</h1>
        <a href="{{ route('boards.create') }}" class="btn btn-gold">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5 inline-block">
                <path fill-rule="evenodd" d="M12 3.75a.75.75 0 0 1 .75.75v6.75h6.75a.75.75 0 0 1 0 1.5h-6.75v6.75a.75.75 0 0 1-1.5 0v-6.75H4.5a.75.75 0 0 1 0-1.5h6.75V4.5a.75.75 0 0 1 .75-.75Z" clip-rule="evenodd"/>
            </svg>
            新建棋盤
        </a>
    </div>
    <div class="boards-grid">
        @forelse($boards as $board)
        <article class="board-card">
            <div class="board-card-body">
                <h3>{{ $board->name }}</h3>
                @if($board->description)<p>{{ $board->description }}</p>@endif
                <span class="badge-squares">{{ $board->squares_count }} 格</span>
                @if($board->share_code)
                <span class="share-code-badge" title="分享碼（點擊複製）"
                      data-code="{{ $board->share_code }}"
                      onclick="copyShareCode(this)" style="cursor:pointer">
                    {{ $board->share_code }}
                </span>
                @endif
            </div>
            <div class="board-card-foot">
                <a href="{{ route('play.board', $board) }}" class="btn btn-sm btn-gold">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5 inline-block">
                        <path fill-rule="evenodd" d="M4.5 5.653c0-1.427 1.529-2.33 2.779-1.643l11.54 6.347c1.295.712 1.295 2.573 0 3.286L7.28 19.99c-1.25.687-2.779-.217-2.779-1.643V5.653Z" clip-rule="evenodd"/>
                    </svg>
                    玩
                </a>
                <a href="{{ route('boards.edit', $board) }}" class="btn btn-sm btn-outline">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5 inline-block">
                        <path d="M21.731 2.269a2.625 2.625 0 0 0-3.712 0l-1.157 1.157 3.712 3.712 1.157-1.157a2.625 2.625 0 0 0 0-3.712ZM19.513 8.199l-3.712-3.712-8.4 8.4a5.25 5.25 0 0 0-1.32 2.214l-.8 2.685a.75.75 0 0 0 .933.933l2.685-.8a5.25 5.25 0 0 0 2.214-1.32l8.4-8.4Z"/>
                    </svg>
                    編輯
                </a>
                <form action="{{ route('boards.destroy', $board) }}" method="POST" onsubmit="return confirm('確定刪除？')">
                    @csrf @method('DELETE')
                    <button class="btn btn-sm btn-danger">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5 inline-block">
                            <path fill-rule="evenodd" d="M5.47 5.47a.75.75 0 0 1 1.06 0L12 10.94l5.47-5.47a.75.75 0 1 1 1.06 1.06L13.06 12l5.47 5.47a.75.75 0 1 1-1.06 1.06L12 13.06l-5.47 5.47a.75.75 0 0 1-1.06-1.06L10.94 12 5.47 6.53a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd"/>
                        </svg>
                    </button>
                </form>
            </div>
        </article>
        @empty
        <div class="empty-notice">
            還沒有棋盤，<a href="{{ route('boards.create') }}" style="color:var(--gold)">立即建立一個</a>
        </div>
        @endforelse
    </div>
</div>
@endsection

@section('scripts')
<script>
function copyShareCode(el) {
    const code = el.dataset.code;
    navigator.clipboard.writeText(code).then(() => {
        const orig = el.textContent;
        el.textContent = '已複製！';
        setTimeout(() => { el.textContent = orig; }, 1500);
    });
}
</script>
@endsection
