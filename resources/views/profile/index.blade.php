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

    {{-- 遊玩紀錄 --}}
    <section>
        <div class="section-head">
            <h2>{{ __('ui.play_history') }}</h2>
        </div>
        @if($playHistory->isEmpty())
        <div class="empty-notice">{{ __('ui.no_play_history') }}</div>
        @else
        <div style="background:var(--surface);border:1px solid var(--border);border-radius:12px;overflow:hidden">
            @foreach($playHistory as $entry)
            <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;padding:12px 20px;flex-wrap:wrap;{{ $loop->last ? '' : 'border-bottom:1px solid var(--border);' }}">
                <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
                    <strong>{{ $entry->game->game_type === 'truth_or_dare' ? __('games.truth_dare') : __('games.flying_chess') }}</strong>
                    <span style="font-size:.82rem;color:var(--text-dim)">#{{ $entry->game->code }}</span>
                    @if($entry->is_host)
                    <span class="badge-squares">{{ __('ui.history_as_host') }}</span>
                    @endif
                </div>
                <div style="display:flex;align-items:center;gap:12px">
                    <span style="font-size:.82rem;color:var(--text-dim)">{{ $entry->created_at->format('Y/m/d H:i') }}</span>
                    @if($entry->game->status !== 'finished')
                    <a href="{{ $entry->game->game_type === 'truth_or_dare' ? route('truth-dare.show', $entry->game->code) : route('games.show', $entry->game->code) }}"
                       class="btn btn-sm btn-outline">{{ __('ui.play') }}</a>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
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
