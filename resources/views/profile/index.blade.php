@extends('layouts.app')
@section('title','個人頁 — 情侶飛行棋')
@section('meta_description','管理你的帳號、棋盤與自訂遊戲內容')
@section('robots','noindex,nofollow')
@section('content')
<div class="container" style="padding-top:40px;padding-bottom:60px">

    {{-- 帳號資訊 --}}
    <section style="margin-bottom:36px">
        <div class="section-head">
            <h1>個人頁</h1>
        </div>
        <div style="background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:24px">
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px">
                <div>
                    <div style="font-size:.8rem;color:var(--text-dim);margin-bottom:2px">使用者名稱</div>
                    <div style="font-weight:600">{{ $user->name }}</div>
                </div>
                <div>
                    <div style="font-size:.8rem;color:var(--text-dim);margin-bottom:2px">Email</div>
                    <div style="font-weight:600">{{ $user->email }}</div>
                </div>
                <div>
                    <div style="font-size:.8rem;color:var(--text-dim);margin-bottom:2px">會員狀態</div>
                    <div>
                        @if($user->isPremium())
                            <span class="badge-premium" style="font-size:.8rem;padding:3px 10px;border-radius:8px">Premium</span>
                            <span style="font-size:.8rem;color:var(--text-dim);margin-left:4px">到期：{{ $user->premium_expires_at->format('Y/m/d') }}</span>
                        @else
                            <span class="badge-free" style="font-size:.8rem;padding:3px 10px;border-radius:8px">免費會員</span>
                            <a href="{{ route('premium.index') }}" style="font-size:.8rem;color:var(--gold);margin-left:6px">升級 Premium →</a>
                        @endif
                    </div>
                </div>
                <div>
                    <div style="font-size:.8rem;color:var(--text-dim);margin-bottom:2px">註冊日期</div>
                    <div style="font-weight:600">{{ $user->created_at->format('Y/m/d') }}</div>
                </div>
            </div>
        </div>
    </section>

    {{-- 我的棋盤 --}}
    <section style="margin-bottom:36px">
        <div class="section-head">
            <h2>我的棋盤</h2>
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
    </section>

    {{-- 自訂遊戲（即將推出） --}}
    <section>
        <div class="section-head">
            <h2>自訂遊戲</h2>
        </div>
        <div class="game-cards-grid">
            <div class="game-card" style="opacity:.55;pointer-events:none">
                <div class="game-card-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6">
                        <path d="M11.584 2.376a.75.75 0 0 1 .832 0l9 6a.75.75 0 1 1-.832 1.248L12 3.901 3.416 9.624a.75.75 0 0 1-.832-1.248l9-6Z"/>
                        <path fill-rule="evenodd" d="M20.25 10.332v9.918H21a.75.75 0 0 1 0 1.5H3a.75.75 0 0 1 0-1.5h.75v-9.918a.75.75 0 0 1 .634-.74A49.109 49.109 0 0 1 12 9c2.59 0 5.134.202 7.616.592a.75.75 0 0 1 .634.74Zm-7.5 2.418a.75.75 0 0 0-1.5 0v6.75a.75.75 0 0 0 1.5 0v-6.75Zm3-.75a.75.75 0 0 1 .75.75v6.75a.75.75 0 0 1-1.5 0v-6.75a.75.75 0 0 1 .75-.75ZM9 12.75a.75.75 0 0 0-1.5 0v6.75a.75.75 0 0 0 1.5 0v-6.75Z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <h3>自訂骰子</h3>
                <p>即將推出</p>
            </div>
            <div class="game-card" style="opacity:.55;pointer-events:none">
                <div class="game-card-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6">
                        <path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25ZM12.75 6a.75.75 0 0 0-1.5 0v6c0 .414.336.75.75.75h4.5a.75.75 0 0 0 0-1.5h-3.75V6Z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <h3>自訂轉盤</h3>
                <p>即將推出</p>
            </div>
            <div class="game-card" style="opacity:.55;pointer-events:none">
                <div class="game-card-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6">
                        <path fill-rule="evenodd" d="M9.401 3.003c1.155-2 4.043-2 5.197 0l7.355 12.748c1.154 2-.29 4.5-2.599 4.5H4.645c-2.309 0-3.752-2.5-2.598-4.5L9.4 3.003ZM12 8.25a.75.75 0 0 1 .75.75v3.75a.75.75 0 0 1-1.5 0V9a.75.75 0 0 1 .75-.75Zm0 8.25a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <h3>自訂大冒險</h3>
                <p>即將推出</p>
            </div>
        </div>
    </section>

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
