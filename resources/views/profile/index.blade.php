@extends('layouts.app')
@section('title', __('ui.profile') . ' — ' . __('ui.site_name'))
@section('meta_description', __('seo.profile_description'))
@section('robots','noindex,nofollow')
@section('content')
<div class="container" style="padding-top:40px;padding-bottom:60px">

    {{-- 帳號資訊 --}}
    <section style="margin-bottom:36px">
        <div class="section-head">
            <h1>{{ __('ui.profile') }}</h1>
        </div>
        <div style="background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:24px">
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px">
                <div>
                    <div style="font-size:.8rem;color:var(--text-dim);margin-bottom:2px">{{ __('ui.username_label') }}</div>
                    <div style="font-weight:600">{{ $user->name }}</div>
                </div>
                <div>
                    <div style="font-size:.8rem;color:var(--text-dim);margin-bottom:2px">{{ __('auth.email_label') }}</div>
                    <div style="font-weight:600">{{ $user->email }}</div>
                </div>
                <div>
                    <div style="font-size:.8rem;color:var(--text-dim);margin-bottom:2px">{{ __('ui.member_status') }}</div>
                    <div>
                        @if($user->isPremium())
                            <span class="badge-premium" style="font-size:.8rem;padding:3px 10px;border-radius:8px">Premium</span>
                            <span style="font-size:.8rem;color:var(--text-dim);margin-left:4px">{{ __('ui.expires_at_short', ['date' => $user->premium_expires_at->format('Y/m/d')]) }}</span>
                        @else
                            <span class="badge-free" style="font-size:.8rem;padding:3px 10px;border-radius:8px">{{ __('ui.free_member') }}</span>
                            <a href="{{ route('premium.index') }}" style="font-size:.8rem;color:var(--gold);margin-left:6px">{{ __('ui.upgrade_premium') }}</a>
                        @endif
                    </div>
                </div>
                <div>
                    <div style="font-size:.8rem;color:var(--text-dim);margin-bottom:2px">{{ __('ui.register_date') }}</div>
                    <div style="font-weight:600">{{ $user->created_at->format('Y/m/d') }}</div>
                </div>
            </div>
        </div>
    </section>

    {{-- 我的棋盤 --}}
    <section style="margin-bottom:36px">
        <div class="section-head">
            <h2>{{ __('ui.my_boards') }}</h2>
            <a href="{{ route('boards.create') }}" class="btn btn-gold">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5 inline-block">
                    <path fill-rule="evenodd" d="M12 3.75a.75.75 0 0 1 .75.75v6.75h6.75a.75.75 0 0 1 0 1.5h-6.75v6.75a.75.75 0 0 1-1.5 0v-6.75H4.5a.75.75 0 0 1 0-1.5h6.75V4.5a.75.75 0 0 1 .75-.75Z" clip-rule="evenodd"/>
                </svg>
                {{ __('ui.new_board') }}
            </a>
        </div>
        <div class="boards-grid">
            @forelse($boards as $board)
            <article class="board-card">
                <div class="board-card-body">
                    <h3>{{ $board->name }}</h3>
                    @if($board->description)<p>{{ $board->description }}</p>@endif
                    <span class="badge-squares">{{ __('ui.square_count', ['n' => $board->squares_count]) }}</span>
                    @if($board->share_code)
                    <span class="share-code-badge" title="{{ __('ui.share_code_tip') }}"
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
                        {{ __('ui.play') }}
                    </a>
                    <a href="{{ route('boards.edit', $board) }}" class="btn btn-sm btn-outline">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5 inline-block">
                            <path d="M21.731 2.269a2.625 2.625 0 0 0-3.712 0l-1.157 1.157 3.712 3.712 1.157-1.157a2.625 2.625 0 0 0 0-3.712ZM19.513 8.199l-3.712-3.712-8.4 8.4a5.25 5.25 0 0 0-1.32 2.214l-.8 2.685a.75.75 0 0 0 .933.933l2.685-.8a5.25 5.25 0 0 0 2.214-1.32l8.4-8.4Z"/>
                        </svg>
                        {{ __('ui.edit') }}
                    </a>
                    <form action="{{ route('boards.destroy', $board) }}" method="POST" onsubmit="return confirm('{{ __('ui.confirm_delete') }}')">
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
                {!! __('ui.no_boards_html', ['link' => '<a href="'.route('boards.create').'" style="color:var(--gold)">'.e(__('ui.create_one_now')).'</a>']) !!}
            </div>
            @endforelse
        </div>
    </section>

    {{-- 自訂骰子與轉盤。功能本來就有(/my-dice、/my-wheels),但個人資料頁一直
         沒有入口 —— 使用者只能靠記住網址進去,等於等於沒有。 --}}
    <section style="margin-bottom:36px">
        <div class="section-head">
            <h2>{{ __('ui.my_dice') }} / {{ __('ui.my_wheels') }}</h2>
        </div>
        <div class="profile-tools">
            <a href="{{ route('dice.index') }}" class="profile-tool">
                <span class="profile-tool-icon" aria-hidden="true">🎲</span>
                <span class="profile-tool-text">
                    <strong>{{ __('ui.my_dice') }}</strong>
                    <em>{{ __('ui.my_dice_desc') }}</em>
                </span>
            </a>
            <a href="{{ route('custom-wheel.page') }}" class="profile-tool">
                <span class="profile-tool-icon" aria-hidden="true">🎡</span>
                <span class="profile-tool-text">
                    <strong>{{ __('ui.my_wheels') }}</strong>
                    <em>{{ __('ui.my_wheels_desc') }}</em>
                </span>
            </a>
        </div>
    </section>

    {{-- 我的屬性 --}}
    @include('profile._traits')

    {{-- 遊玩紀錄 --}}
    <section>
        <div class="section-head">
            <h2>{{ __('ui.play_history') }}</h2>
            @if($totalPlays > 0)
            <span class="history-total">{{ __('ui.history_total', ['total' => $totalPlays]) }}</span>
            @endif
        </div>
        @if($playHistory->isEmpty())
        <div class="empty-notice">{{ __('ui.no_play_history') }}</div>

        @elseif($isPremium)
        {{-- 付費會員:依日期分組的時間軸。同一天玩的幾場收在一個節點下,
             比一長串沒有斷點的清單容易讀出「那天玩了什麼」。 --}}
        <div class="history-timeline">
            @foreach($timeline as $day => $entries)
            <div class="tl-group">
                <div class="tl-date">
                    <span class="tl-dot"></span>
                    {{ \Illuminate\Support\Carbon::parse($day)->isoFormat('LL') }}
                    <span class="tl-count">{{ __('ui.history_total', ['total' => $entries->count()]) }}</span>
                </div>
                <div class="tl-items">
                    @foreach($entries as $entry)
                    @include('partials.history-entry', ['entry' => $entry])
                    @endforeach
                </div>
            </div>
            @endforeach
        </div>

        @else
        {{-- 免費會員:最近 N 場的平面清單 --}}
        <div class="history-list">
            @foreach($playHistory as $entry)
            @include('partials.history-entry', ['entry' => $entry])
            @endforeach
        </div>

        @if($hiddenPlays > 0)
        {{-- 只在真的有東西被鎖住時才出現。玩過的場次還沒超過免費額度時
             顯示升級提示,只會讓人覺得莫名其妙。 --}}
        <div class="history-locked">
            <p>{{ __('ui.history_locked', ['count' => $freeLimit, 'hidden' => $hiddenPlays]) }}</p>
            <a href="{{ route('premium.index') }}" class="btn btn-gold btn-sm">
                {{ __('ui.history_upgrade_cta') }}
            </a>
        </div>
        @endif
        @endif
    </section>

</div>
@endsection

@section('scripts')
<script>
function copyShareCode(el) {
    const code = el.dataset.code;
    navigator.clipboard.writeText(code).then(() => {
        const orig = el.textContent;
        el.textContent = @json(__('ui.copied_excl'));
        setTimeout(() => { el.textContent = orig; }, 1500);
    });
}
</script>
@endsection
