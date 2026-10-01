@extends('layouts.app')

{{-- fc_lobby_seo_title 只用在 meta;頁面上的 section-label 仍是 fc_lobby_title。 --}}
@section('title', __('games.fc_lobby_seo_title') . ' — ' . __('ui.site_name'))
@section('meta_description', __('games.fc_lobby_meta'))
@section('og_title', __('games.fc_lobby_seo_title') . ' — ' . __('ui.site_name'))
@section('og_description', __('games.fc_lobby_meta'))
{{-- 社群分頁不進索引:內容是會員發佈的棋盤,跟 /community 重複,讓 /community 當那一頁 --}}
@section('canonical', $tab === 'community' ? route('games.lobby', ['tab' => 'community']) : route('games.lobby'))
@if($tab === 'community')
    @section('robots', 'noindex,follow')
@endif

@section('schema')
    @include('partials.game-schema', [
        'gameName' => __('games.flying_chess'),
        'gameDescription' => __('games.desc_flying_chess'),
        'gamePath' => 'games',
        'minPlayers' => 2,
        'maxPlayers' => 4,
    ])
    @include('partials.game-faq-schema', ['faqKey' => 'games'])
@endsection

@section('faq')
    @include('partials.game-faq', ['faqKey' => 'games'])
@endsection

@section('content')
<div class="container" style="padding-top:32px;padding-bottom:32px;min-height:calc(100vh - 56px)">
    <div style="text-align:center;margin-bottom:32px">
        <span class="section-label">{{ __('games.fc_lobby_title') }}</span>
        <h1 class="section-title">{{ __('games.fc_lobby_h1') }}</h1>
        <p class="section-desc" style="max-width:480px;margin:0 auto">{{ __('games.fc_lobby_desc') }}</p>
    </div>

    {{-- 網站棋盤／社群棋盤切換。用連結不是 JS 分頁:兩邊各自分頁,重新整理也停在同一邊 --}}
    <nav class="lobby-tabs" aria-label="{{ __('games.lobby_tabs_aria') }}">
        <a href="{{ route('games.lobby') }}" @class(['is-on' => $tab === 'site']) @if($tab === 'site') aria-current="page" @endif>{{ __('games.lobby_tab_site') }}</a>
        <a href="{{ route('games.lobby', ['tab' => 'community']) }}" @class(['is-on' => $tab === 'community']) @if($tab === 'community') aria-current="page" @endif>{{ __('games.lobby_tab_community') }}</a>
    </nav>

    {{-- Board Grid --}}
    @if($boards->isEmpty() && $tab === 'community')
        <div class="lobby-empty">
            <h2>{{ __('games.lobby_community_empty_title') }}</h2>
            <p>{{ __('games.lobby_community_empty_desc') }}</p>
            <a href="{{ auth()->check() ? route('boards.create') : route('register') }}" class="btn btn-outline">{{ __('games.lobby_community_cta') }}</a>
        </div>
    @elseif($boards->isEmpty())
        <div class="empty-notice" style="text-align:center;padding:40px">
            <p>{{ __('games.no_boards') }}</p>
        </div>
    @else
        <div class="boards-grid">
            @foreach($boards as $board)
            <article class="board-card">
                <div class="board-card-body">
                    @php
                        $shape = 'cross';
                        if ($board->canvas_rows == 7 && $board->canvas_cols == 7) $shape = 'square';
                        elseif ($board->canvas_rows == 5 && $board->canvas_cols == 9) $shape = 'rect';
                    @endphp
                    <div class="board-mini-preview shape-{{ $shape }}">
                        @foreach($board->squares as $sq)
                            <div class="board-dot{{ $sq->position === 0 ? ' dot-start' : '' }}{{ $sq->position === $board->squares->count() - 1 ? ' dot-end' : '' }}"
                                 style="grid-row:{{ $sq->grid_row }};grid-column:{{ $sq->grid_col }}"></div>
                        @endforeach
                    </div>
                    <h3>{{ $board->name }}</h3>
                    @if($tab === 'community' && $board->user)<span class="lobby-by">{{ __('games.lobby_by', ['name' => $board->user->name]) }}</span>@endif
                    @if($board->description)<p>{{ $board->description }}</p>@endif
                    <div style="display:flex;gap:4px;flex-wrap:wrap;margin-top:4px">
                        <span class="badge-squares">{{ __('games.badge_squares', ['n' => $board->squares_count]) }}</span>
                        @include('partials.board-players-badge')
                        @if($board->is_default)<span class="badge-default">{{ __('games.badge_default') }}</span>@endif
                        @if($board->is_premium_template)<span class="badge-premium">Premium</span>@endif
                        @if($board->is_template && !$board->is_premium_template)<span class="badge-free">{{ __('games.badge_free') }}</span>@endif
                    </div>
                </div>
                <div class="board-card-foot">
                    {{-- 快速預覽:不離開大廳就看得到每一格寫什麼(付費棋盤只開 8 格,見 GameController::boardPreview) --}}
                    <button type="button" class="btn btn-sm btn-outline qp-open"
                            data-preview-url="{{ route('games.board-preview', $board) }}">{{ __('games.quick_preview') }}</button>
                    {{-- 付費範本:先看內容,或直接升級。「看廣告解鎖」只放在預覽頁 ——
                         卡片這麼小塞三顆會換行,而且要求人在還沒看過內容前就看廣告,
                         換到的多半是一次跳過。見 BoardController::templatePreview。 --}}
                    @if($board->is_premium_template && ! \App\Support\PremiumAccess::content(auth()->user()))
                        <a href="{{ route('boards.template.preview', $board) }}" class="btn btn-sm btn-gold">{{ __('games.preview_short') }}</a>
                        {{-- 只有真的收得到錢的時候才放「升級解鎖」。沒有金流時它會把人
                             帶到一頁寫著「目前沒有付款方式」的畫面 —— 而預覽頁上就有
                             「看廣告解鎖」,那是現在唯一走得通的路。 --}}
                        @if(app(\App\Support\Payments\PaymentGateway::class)->isLive())
                        <a href="{{ route('premium.index') }}" class="btn btn-sm btn-outline" title="Premium">{{ __('games.unlock_premium') }}</a>
                        @endif
                    @else
                        {{-- canonical 網址(有 share_code 的走 /play/share/{code})。
                             大廳是公開頁,連數字網址等於叫爬蟲多爬一份重複內容。 --}}
                        <a href="{{ $board->canonicalPlayUrl() }}" class="btn btn-sm btn-gold">{{ __('games.start_game') }}</a>
                    @endif
                </div>
            </article>
            @endforeach
        </div>

        {{-- 分頁:上下都要留白,不然會貼著卡片和下面的分隔線 --}}
        <div class="lobby-pagination">
            {{ $boards->links() }}
        </div>
    @endif

    {{-- 快速預覽視窗:內容由下方的 script 向 games.board-preview 取 --}}
    <div id="qp-modal" class="modal qp-modal" role="dialog" aria-modal="true" aria-labelledby="qp-title">
        <div class="modal-overlay" data-qp-close></div>
        <div class="modal-box qp-box">
            <button type="button" class="modal-close" data-qp-close aria-label="{{ __('games.quick_preview_close') }}">✕</button>
            <h2 id="qp-title" class="qp-title"></h2>
            <p class="qp-meta"></p>
            <p class="qp-lock" hidden>{{ __('games.quick_preview_locked', ['n' => \App\Models\Board::PREVIEW_OPEN_SQUARES]) }}</p>
            <ol class="qp-list" aria-live="polite"></ol>
            <div class="qp-actions">
                <a class="btn btn-gold qp-play" hidden>{{ __('games.start_game') }}</a>
                <a class="btn btn-outline qp-full" hidden>{{ __('games.quick_preview_full') }}</a>
            </div>
        </div>
    </div>

    {{-- Other Games --}}
    <hr class="section-divider">
    <div style="text-align:center;margin-bottom:24px">
        <h2 class="section-title" style="font-size:1.2rem">{{ __('games.more_games') }}</h2>
    </div>
    <div class="game-cards-grid is-compact">
        <article class="game-card">
            <div class="game-card-icon" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:40px;height:40px">
                    <path d="M4 3a2 2 0 00-2 2v14a2 2 0 002 2h16a2 2 0 002-2V5a2 2 0 00-2-2H4zm1 2h2v2H5V5zm12 0h2v2h-2V5zM9.5 7.5a4.5 4.5 0 110 9 4.5 4.5 0 010-9zm5 0a4.5 4.5 0 110 9 4.5 4.5 0 010-9zM5 17h2v2H5v-2zm12 0h2v2h-2v-2z"/>
                </svg>
            </div>
            <h3>{{ __('games.card_game') }}</h3>
            <p>{{ __('games.desc_card_short') }}</p>
            <a href="{{ route('card-game.show') }}" class="btn btn-gold btn-full">{{ __('games.play_short') }}</a>
        </article>

        <article class="game-card">
            <div class="game-card-icon" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:40px;height:40px">
                    <path d="M11.645 20.91l-.007-.003-.022-.012a15.247 15.247 0 01-.383-.218 25.18 25.18 0 01-4.244-3.17C4.688 15.36 2.25 12.174 2.25 8.25 2.25 5.322 4.714 3 7.688 3A5.5 5.5 0 0112 5.052 5.5 5.5 0 0116.313 3c2.973 0 5.437 2.322 5.437 5.25 0 3.925-2.438 7.111-4.739 9.256a25.175 25.175 0 01-4.244 3.17 15.247 15.247 0 01-.383.219l-.022.012-.007.004-.003.001a.752.752 0 01-.704 0l-.003-.001Z"/>
                </svg>
            </div>
            <h3>{{ __('games.truth_dare') }}</h3>
            <p>{{ __('games.desc_truth_short') }}</p>
            <a href="{{ route('truth-dare.lobby') }}" class="btn btn-gold btn-full">{{ __('games.play_short') }}</a>
        </article>

        @auth
        <article class="game-card">
            <div class="game-card-icon" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:40px;height:40px">
                    <path d="M21.731 2.269a2.625 2.625 0 00-3.712 0l-1.157 1.157 3.712 3.712 1.157-1.157a2.625 2.625 0 000-3.712ZM19.513 8.199l-3.712-3.712-8.4 8.4a5.25 5.25 0 00-1.32 2.214l-.8 2.685a.75.75 0 00.933.933l2.685-.8a5.25 5.25 0 002.214-1.32l8.4-8.4Z"/>
                </svg>
            </div>
            <h3>{{ __('games.my_boards_short') }}</h3>
            <p>{{ __('games.my_boards_desc') }}</p>
            <a href="{{ route('boards.index') }}" class="btn btn-gold btn-full">{{ __('games.manage_boards') }}</a>
        </article>
        @endauth
    </div>
</div>

{{-- Lobby sidebar ad --}}
<div class="ad-sidebar-wrap" style="margin-top:24px">
    @include('partials.ad-unit', ['zone' => 'lobby_side'])
</div>

@endsection

@section('scripts')
@php
    $qpText = [
        'loading' => __('games.quick_preview_loading'),
        'failed' => __('games.quick_preview_failed'),
        'locked' => __('games.quick_preview_locked_item'),
        'squares' => __('games.badge_squares', ['n' => '__N__']),
    ];
@endphp
<script src="{{ asset_v('js/sq-icons.js') }}"></script>
<script>
/* 大廳的快速預覽。點卡片(或「快速預覽」)就開,照路線順序列出每一格。 */
(function () {
    var modal = document.getElementById('qp-modal');
    if (!modal) return;
    var list = modal.querySelector('.qp-list'), title = modal.querySelector('.qp-title'),
        meta = modal.querySelector('.qp-meta'), lock = modal.querySelector('.qp-lock'),
        play = modal.querySelector('.qp-play'), full = modal.querySelector('.qp-full');
    var T = @json($qpText);
    var lastFocus = null, seq = 0;

    function close() {
        modal.classList.remove('open');
        document.body.style.overflow = '';
        if (lastFocus) lastFocus.focus();
    }
    function item(sq) {
        var li = document.createElement('li');
        li.className = 'qp-item qp-' + sq.color + (sq.locked ? ' is-locked' : '');
        var num = document.createElement('span'); num.className = 'qp-num'; num.textContent = sq.step;
        var icon = document.createElement('span'); icon.className = 'qp-icon'; icon.setAttribute('aria-hidden', 'true');
        icon.innerHTML = typeof sqTypeIconHtml === 'function' ? sqTypeIconHtml(sq.color) : '';
        var text = document.createElement('span'); text.className = 'qp-text';
        text.textContent = sq.locked ? T.locked : (sq.text || '');
        li.append(num, icon, text);
        return li;
    }
    function open(url, trigger) {
        lastFocus = trigger;
        var mine = ++seq;
        title.textContent = ''; meta.textContent = T.loading; lock.hidden = true;
        list.innerHTML = ''; play.hidden = true; full.hidden = true;
        modal.classList.add('open');
        document.body.style.overflow = 'hidden';
        modal.querySelector('.modal-close').focus();
        fetch(url, {headers: {'Accept': 'application/json'}, credentials: 'same-origin'})
            .then(function (r) { if (!r.ok) throw new Error(r.status); return r.json(); })
            .then(function (d) {
                if (mine !== seq) return;   // 連點兩張卡時,只顯示最後一張
                title.textContent = d.name;
                meta.textContent = d.audience + ' · ' + T.squares.replace('__N__', d.squares.length);
                lock.hidden = !d.locked;
                d.squares.forEach(function (sq) { list.appendChild(item(sq)); });
                if (d.play_url) { play.href = d.play_url; play.hidden = false; }
                if (d.locked && d.preview_url) { full.href = d.preview_url; full.hidden = false; }
            })
            .catch(function () { if (mine === seq) meta.textContent = T.failed; });
    }

    document.addEventListener('click', function (e) {
        if (e.target.closest('[data-qp-close]')) { close(); return; }
        var btn = e.target.closest('.qp-open');
        // 點卡片本身也開預覽(按鈕、連結照原本的行為)
        if (!btn) {
            var card = e.target.closest('.board-card');
            if (!card || e.target.closest('a, button, form')) return;
            btn = card.querySelector('.qp-open');
        }
        if (btn) { e.preventDefault(); open(btn.dataset.previewUrl, btn); }
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && modal.classList.contains('open')) close();
    });
})();
</script>
@endsection
