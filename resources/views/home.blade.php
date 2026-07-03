@php use App\Support\LocaleHelper; @endphp
@extends('layouts.app')
@section('title', __('home.meta_title'))
@section('meta_description', __('home.meta_description'))
@section('og_title', __('home.meta_title'))
@section('og_description', __('home.og_description'))
@section('canonical', route('home'))
@section('content')

{{-- ======================================================
     Hero
     ====================================================== --}}
<section class="hero-section">
    <div class="hero-inner">
        <span class="hero-eyebrow">{{ __('home.hero_eyebrow') }}</span>
        <h1 class="hero-title">{{ __('home.hero_title_pre') }}<span>{{ __('home.hero_title_high') }}</span></h1>
        <p class="hero-sub">{{ __('home.hero_sub') }}</p>
        <div class="hero-btns">
            <a href="{{ route('games.lobby') }}" class="btn btn-gold btn-xl">{{ __('home.hero_cta_chess') }}</a>
            <a href="{{ route('truth-dare.lobby') }}" class="btn btn-outline-gold btn-xl">{{ __('home.hero_cta_truth') }}</a>
        </div>
        <div class="hero-trust">
            <span class="hero-trust-item">{{ __('home.hero_trust_1') }}</span>
            <span class="hero-trust-item">{{ __('home.hero_trust_2') }}</span>
            <span class="hero-trust-item">{{ __('home.hero_trust_3') }}</span>
        </div>
    </div>
</section>

{{-- ======================================================
     Stats Band
     ====================================================== --}}
<div class="stats-band" aria-hidden="true">
    <div class="stats-band-inner">
        <div class="stats-item">
            <span class="stats-num">{{ __('home.stats_modes_num') }}</span>
            <span class="stats-label">{{ __('home.stats_modes_label') }}</span>
        </div>
        <div class="stats-item">
            <span class="stats-num">{{ __('home.stats_install_num') }}</span>
            <span class="stats-label">{{ __('home.stats_install_label') }}</span>
        </div>
        <div class="stats-item">
            <span class="stats-num">{{ __('home.stats_share_num') }}</span>
            <span class="stats-label">{{ __('home.stats_share_label') }}</span>
        </div>
        <div class="stats-item">
            <span class="stats-num">{{ __('home.stats_free_num') }}</span>
            <span class="stats-label">{{ __('home.stats_free_label') }}</span>
        </div>
    </div>
</div>

@include('partials.ad-unit', ['zone' => 'home_banner'])

{{-- ======================================================
     Game Modes
     ====================================================== --}}
<section class="game-cards-section section">
    <div class="container">
        <div class="text-center" style="margin-bottom:48px">
            <span class="section-label">{{ __('home.modes_label') }}</span>
            <h2 class="section-title">{{ __('home.modes_title') }}</h2>
            <p class="section-desc" style="max-width:480px;margin-left:auto;margin-right:auto">{{ __('home.modes_desc') }}</p>
        </div>
        <div class="game-cards-grid">
            {{-- 飛行棋 --}}
            <article class="game-card">
                <div class="game-card-icon" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:40px;height:40px">
                        <path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25ZM12.75 6a.75.75 0 00-1.5 0v6c0 .414.336.75.75.75h4.5a.75.75 0 000-1.5h-3.75V6Z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <h3>{{ __('home.mode_chess_title') }}</h3>
                <p>{{ __('home.mode_chess_desc') }}</p>
                <a href="{{ route('games.lobby') }}" class="btn btn-gold btn-full">{{ __('home.mode_chess_cta') }}</a>
            </article>

            {{-- 真心話大冒險 --}}
            <article class="game-card">
                <div class="game-card-icon" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:40px;height:40px">
                        <path d="M11.645 20.91l-.007-.003-.022-.012a15.247 15.247 0 01-.383-.218 25.18 25.18 0 01-4.244-3.17C4.688 15.36 2.25 12.174 2.25 8.25 2.25 5.322 4.714 3 7.688 3A5.5 5.5 0 0112 5.052 5.5 5.5 0 0116.313 3c2.973 0 5.437 2.322 5.437 5.25 0 3.925-2.438 7.111-4.739 9.256a25.175 25.175 0 01-4.244 3.17 15.247 15.247 0 01-.383.219l-.022.012-.007.004-.003.001a.752.752 0 01-.704 0l-.003-.001Z"/>
                    </svg>
                </div>
                <h3>{{ __('home.mode_truth_title') }}</h3>
                <p>{{ __('home.mode_truth_desc') }}</p>
                <a href="{{ route('truth-dare.lobby') }}" class="btn btn-gold btn-full">{{ __('home.mode_truth_cta') }}</a>
            </article>

            {{-- 情侶撲克牌 --}}
            <article class="game-card">
                <div class="game-card-icon" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:40px;height:40px">
                        <path d="M4 3a2 2 0 00-2 2v14a2 2 0 002 2h16a2 2 0 002-2V5a2 2 0 00-2-2H4zm1 2h2v2H5V5zm12 0h2v2h-2V5zM9.5 7.5a4.5 4.5 0 110 9 4.5 4.5 0 010-9zm5 0a4.5 4.5 0 110 9 4.5 4.5 0 010-9zM5 17h2v2H5v-2zm12 0h2v2h-2v-2z"/>
                    </svg>
                </div>
                <h3>{{ __('home.mode_card_title') }}</h3>
                <p>{{ __('home.mode_card_desc') }}</p>
                <a href="{{ route('card-game.show') }}" class="btn btn-gold btn-full">{{ __('home.mode_truth_cta') }}</a>
            </article>

            {{-- 自訂棋盤 --}}
            <article class="game-card">
                <div class="game-card-icon" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:40px;height:40px">
                        <path fill-rule="evenodd" d="M3 6a3 3 0 013-3h2.25a3 3 0 013 3v2.25a3 3 0 01-3 3H6a3 3 0 01-3-3V6Zm9.75 0a3 3 0 013-3H18a3 3 0 013 3v2.25a3 3 0 01-3 3h-2.25a3 3 0 01-3-3V6ZM3 15.75a3 3 0 013-3h2.25a3 3 0 013 3V18a3 3 0 01-3 3H6a3 3 0 01-3-3v-2.25Zm9.75 0a3 3 0 013-3H18a3 3 0 013 3V18a3 3 0 01-3 3h-2.25a3 3 0 01-3-3v-2.25Z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <h3>{{ __('home.mode_play_title') }}</h3>
                <p>{{ __('home.mode_play_desc') }}</p>
                @if($default)
                <a href="{{ route('play.board', $default) }}" class="btn btn-gold btn-full">{{ __('home.mode_play_cta') }}</a>
                @else
                <a href="{{ route('play') }}" class="btn btn-gold btn-full">{{ __('home.mode_play_cta') }}</a>
                @endif
            </article>

            {{-- 棋盤編輯器 --}}
            <article class="game-card">
                <div class="game-card-icon" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:40px;height:40px">
                        <path d="M21.731 2.269a2.625 2.625 0 00-3.712 0l-1.157 1.157 3.712 3.712 1.157-1.157a2.625 2.625 0 000-3.712ZM19.513 8.199l-3.712-3.712-8.4 8.4a5.25 5.25 0 00-1.32 2.214l-.8 2.685a.75.75 0 00.933.933l2.685-.8a5.25 5.25 0 002.214-1.32l8.4-8.4Z"/>
                    </svg>
                </div>
                <h3>{{ __('home.mode_editor_title') }}</h3>
                <p>{{ __('home.mode_editor_desc') }}</p>
                @auth
                <a href="{{ route('boards.index') }}" class="btn btn-gold btn-full">{{ __('home.mode_editor_cta_my') }}</a>
                @else
                <a href="{{ route('register') }}" class="btn btn-outline-gold btn-full">{{ __('home.mode_editor_cta_register') }}</a>
                @endauth
            </article>
        </div>
    </div>
</section>

{{-- ======================================================
     How it works
     ====================================================== --}}
<section class="how-section">
    <div class="container">
        <div class="text-center" style="margin-bottom:48px">
            <span class="section-label">{{ __('home.steps_label') }}</span>
            <h2 class="section-title">{{ __('home.steps_title') }}</h2>
            <p class="section-desc" style="max-width:440px;margin-left:auto;margin-right:auto">{{ __('home.steps_desc') }}</p>
        </div>
        <div class="how-grid">
            <div class="how-step">
                <div class="how-step-num">1</div>
                <div class="how-step-icon" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:32px;height:32px">
                        <path fill-rule="evenodd" d="M3 6a3 3 0 013-3h2.25a3 3 0 013 3v2.25a3 3 0 01-3 3H6a3 3 0 01-3-3V6Zm9.75 0a3 3 0 013-3H18a3 3 0 013 3v2.25a3 3 0 01-3 3h-2.25a3 3 0 01-3-3V6ZM3 15.75a3 3 0 013-3h2.25a3 3 0 013 3V18a3 3 0 01-3 3H6a3 3 0 01-3-3v-2.25Zm9.75 0a3 3 0 013-3H18a3 3 0 013 3V18a3 3 0 01-3 3h-2.25a3 3 0 01-3-3v-2.25Z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <h3>{{ __('home.steps_1_title') }}</h3>
                <p>{{ __('home.steps_1_desc') }}</p>
            </div>
            <div class="how-step">
                <div class="how-step-num">2</div>
                <div class="how-step-icon" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:32px;height:32px">
                        <path d="M7.5 4.5a3 3 0 113 3h-3m-3 3a3 3 0 100 6h.75m.75-3a3 3 0 113 3v-3m3 0a3 3 0 100-6h-.75m-.75 3V12m-3-3h3m-3 0V6m0 3H6"/>
                        <path fill-rule="evenodd" d="M3 4.5A1.5 1.5 0 014.5 3h15A1.5 1.5 0 0121 4.5v15a1.5 1.5 0 01-1.5 1.5h-15A1.5 1.5 0 013 19.5v-15Zm16.5 0h-15v15h15v-15Z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <h3>{{ __('home.steps_2_title') }}</h3>
                <p>{{ __('home.steps_2_desc') }}</p>
            </div>
            <div class="how-step">
                <div class="how-step-num">3</div>
                <div class="how-step-icon" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:32px;height:32px">
                        <path d="M11.645 20.91l-.007-.003-.022-.012a15.247 15.247 0 01-.383-.218 25.18 25.18 0 01-4.244-3.17C4.688 15.36 2.25 12.174 2.25 8.25 2.25 5.322 4.714 3 7.688 3A5.5 5.5 0 0112 5.052 5.5 5.5 0 0116.313 3c2.973 0 5.437 2.322 5.437 5.25 0 3.925-2.438 7.111-4.739 9.256a25.175 25.175 0 01-4.244 3.17 15.247 15.247 0 01-.383.219l-.022.012-.007.004-.003.001a.752.752 0 01-.704 0l-.003-.001Z"/>
                    </svg>
                </div>
                <h3>{{ __('home.steps_3_title') }}</h3>
                <p>{{ __('home.steps_3_desc') }}</p>
            </div>
        </div>
    </div>
</section>

<hr class="section-divider">

@include('partials.ad-unit', ['zone' => 'home_mid'])

<hr class="section-divider">

{{-- ======================================================
     Board Templates
     ====================================================== --}}
<section class="boards-section container">
    <div class="section-head">
        <h2>{{ __('home.templates_title') }}</h2>
        <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
            <form action="" method="GET" id="share-join-form" style="display:flex;gap:6px">
                <input type="text" id="share-code-input" name="code" class="form-control"
                       placeholder="{{ __('home.share_code_input') }}" maxlength="10"
                       style="width:120px;text-transform:uppercase;padding:5px 10px;font-size:.82rem">
                <button type="submit" class="btn btn-sm btn-outline-gold">{{ __('home.share_code_open') }}</button>
            </form>
            <a href="{{ route('boards.templates') }}" class="btn btn-sm btn-outline-gold">{{ __('home.view_all') }}</a>
        </div>
    </div>
    <div class="boards-grid">
        @forelse($presetBoards as $board)
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
                @if($board->description)<p>{{ $board->description }}</p>@endif
                <div style="display:flex;gap:4px;flex-wrap:wrap;margin-top:4px">
                    <span class="badge-squares">{{ __('home.squares_unit', ['n' => $board->squares_count]) }}</span>
                    @if($board->is_default)<span class="badge-default">{{ __('home.badge_default') }}</span>@endif
                    @if($board->is_premium_template)<span class="badge-premium">Premium</span>@endif
                    @if($board->is_template && !$board->is_premium_template)<span class="badge-free">{{ __('home.badge_free') }}</span>@endif
                </div>
            </div>
            <div class="board-card-foot">
                @if($board->is_premium_template && (!auth()->check() || !auth()->user()->isPremium()))
                    <a href="{{ route('premium.index') }}" class="btn btn-sm btn-outline" title="Premium">{{ __('home.unlock_premium') }}</a>
                @else
                    <a href="{{ route('play.board', $board) }}" class="btn btn-sm btn-gold">{{ __('home.play_now') }}</a>
                @endif
            </div>
        </article>
        @empty
        <div class="empty-notice">{{ __('home.no_template') }}</div>
        @endforelse
    </div>
</section>

@include('partials.ad-unit', ['zone' => 'home_mid'])

{{-- ======================================================
     My Boards (authenticated users)
     ====================================================== --}}
@auth
<section class="boards-section container" style="padding-top:0">
    <div class="section-head">
        <h2>{{ __('home.my_boards') }}</h2>
        <a href="{{ route('boards.create') }}" class="btn btn-sm btn-outline-gold">{{ __('home.create_board') }}</a>
    </div>
    <div class="boards-grid">
        @forelse($myBoards as $board)
        <article class="board-card">
            <div class="board-card-body">
                <h3>{{ $board->name }}</h3>
                @if($board->description)<p>{{ $board->description }}</p>@endif
                <span class="badge-squares">{{ __('home.squares_unit', ['n' => $board->squares_count]) }}</span>
                <span class="share-code-badge" title="{{ __('home.share_code_input') }}" data-code="{{ $board->share_code }}"
                      onclick="copyShareCode(this)" style="cursor:pointer">
                    {{ $board->share_code }}
                </span>
            </div>
            <div class="board-card-foot">
                <a href="{{ route('play.board', $board) }}" class="btn btn-sm btn-gold">{{ __('home.play') }}</a>
                <a href="{{ route('boards.edit', $board) }}" class="btn btn-sm btn-outline">{{ __('home.edit') }}</a>
                <form action="{{ route('boards.destroy', $board) }}" method="POST"
                      onsubmit="return confirm('{{ __('home.confirm_delete') }}')">
                    @csrf @method('DELETE')
                    <button class="btn btn-sm btn-danger">&times;</button>
                </form>
            </div>
        </article>
        @empty
        <div class="empty-notice">
            {!! __('home.no_boards_html', ['link' => '<a href="'.route('boards.create').'" style="color:var(--gold)">'.e(__('home.create_one_now')).'</a>']) !!}
        </div>
        @endforelse
    </div>
</section>
@endauth

<hr class="section-divider">

{{-- ======================================================
     Features
     ====================================================== --}}
<section class="features-section section">
    <div class="container">
        <div class="text-center" style="margin-bottom:48px">
            <span class="section-label">{{ __('home.features_label') }}</span>
            <h2 class="section-title">{{ __('home.features_title') }}</h2>
            <p class="section-desc" style="max-width:440px;margin-left:auto;margin-right:auto">{{ __('home.features_desc') }}</p>
        </div>
        <div class="features-grid">
            <div class="feature">
                <div class="f-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:36px;height:36px;color:var(--gold)">
                        <path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12Zm13.36-1.814a.75.75 0 10-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 00-1.06 1.06l2.25 2.25a.75.75 0 001.14-.094l3.75-5.25Z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <h3>{{ __('home.feat_1_title') }}</h3>
                <p>{{ __('home.feat_1_desc') }}</p>
            </div>
            <div class="feature">
                <div class="f-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:36px;height:36px;color:var(--gold)">
                        <path d="m11.645 20.91-.007-.003-.022-.012a15.247 15.247 0 01-.383-.218 25.18 25.18 0 01-4.244-3.17C4.688 15.36 2.25 12.174 2.25 8.25 2.25 5.322 4.714 3 7.688 3A5.5 5.5 0 0112 5.052 5.5 5.5 0 0116.313 3c2.973 0 5.437 2.322 5.437 5.25 0 3.925-2.438 7.111-4.739 9.256a25.175 25.175 0 01-4.244 3.17 15.247 15.247 0 01-.383.219l-.022.012-.007.004-.003.001a.752.752 0 01-.704 0l-.003-.001Z"/>
                    </svg>
                </div>
                <h3>{{ __('home.feat_2_title') }}</h3>
                <p>{{ __('home.feat_2_desc') }}</p>
            </div>
            <div class="feature">
                <div class="f-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:36px;height:36px;color:var(--gold)">
                        <path d="M21.731 2.269a2.625 2.625 0 00-3.712 0l-1.157 1.157 3.712 3.712 1.157-1.157a2.625 2.625 0 000-3.712ZM19.513 8.199l-3.712-3.712-8.4 8.4a5.25 5.25 0 00-1.32 2.214l-.8 2.685a.75.75 0 00.933.933l2.685-.8a5.25 5.25 0 002.214-1.32l8.4-8.4Z"/>
                    </svg>
                </div>
                <h3>{{ __('home.feat_3_title') }}</h3>
                <p>{{ __('home.feat_3_desc') }}</p>
            </div>
        </div>
    </div>
</section>

<hr class="section-divider">

{{-- ======================================================
     FAQ
     ====================================================== --}}
<section class="faq-section">
    <div class="faq-inner">
        <div class="text-center" style="margin-bottom:40px">
            <span class="section-label">{{ __('home.faq_label') }}</span>
            <h2 class="section-title">{{ __('home.faq_title') }}</h2>
        </div>
        <div class="faq-list">
            <details class="faq-item" open>
                <summary class="faq-question">{{ __('home.faq_q1') }}</summary>
                <div class="faq-answer"><p>{{ __('home.faq_a1') }}</p></div>
            </details>
            <details class="faq-item">
                <summary class="faq-question">{{ __('home.faq_q2') }}</summary>
                <div class="faq-answer"><p>{{ __('home.faq_a2', ['price' => config('premium.price')]) }}</p></div>
            </details>
            <details class="faq-item">
                <summary class="faq-question">{{ __('home.faq_q3') }}</summary>
                <div class="faq-answer"><p>{{ __('home.faq_a3') }}</p></div>
            </details>
            <details class="faq-item">
                <summary class="faq-question">{{ __('home.faq_q4') }}</summary>
                <div class="faq-answer"><p>{{ __('home.faq_a4') }}</p></div>
            </details>
        </div>
    </div>
</section>

{{-- ======================================================
     Closing CTA
     ====================================================== --}}
<section class="closing-cta-section">
    <div class="closing-cta-inner">
        <h2>{{ __('home.cta_close_title_pre') }}<span>{{ __('home.cta_close_title_high') }}</span>{{ __('home.cta_close_title_post') }}</h2>
        <p>{{ __('home.cta_close_sub') }}</p>
        <div class="closing-cta-btns">
            <a href="{{ route('games.lobby') }}" class="btn btn-gold btn-xl">{{ __('home.cta_close_btn_play') }}</a>
            @guest
            <a href="{{ route('register') }}" class="btn btn-outline-gold btn-xl">{{ __('home.cta_close_btn_register') }}</a>
            @endguest
        </div>
    </div>
</section>

@endsection

@section('scripts')
<script>
function copyShareCode(el) {
    var code = el.dataset.code;
    navigator.clipboard.writeText(code).then(function() {
        var orig = el.textContent;
        el.textContent = @json(__('ui.copied') . '！');
        setTimeout(function() { el.textContent = orig; }, 1500);
    });
}

document.getElementById('share-join-form').addEventListener('submit', function(e) {
    e.preventDefault();
    var code = document.getElementById('share-code-input').value.trim().toUpperCase();
    if (code.length < 4) return;
    window.location.href = @json(rtrim(LocaleHelper::localizedUrl(app()->getLocale(), 'play/share'), '/').'/') + code;
});
</script>
<script type="application/ld+json">
{
  "@@context": "https://schema.org",
  "@@type": "WebSite",
  "name": @json(__('ui.site_name')),
  "url": @json(LocaleHelper::localizedUrl(app()->getLocale(), '')),
  "description": @json(__('home.schema_site_desc')),
  "inLanguage": @json(LocaleHelper::hreflang(app()->getLocale())),
  "sameAs": []
}
</script>
<script type="application/ld+json">
{
  "@@context": "https://schema.org",
  "@@type": "FAQPage",
  "inLanguage": @json(LocaleHelper::hreflang(app()->getLocale())),
  "mainEntity": [
    { "@@type": "Question", "name": @json(__('home.faq_q1')),
      "acceptedAnswer": { "@@type": "Answer", "text": @json(__('home.faq_a1')) } },
    { "@@type": "Question", "name": @json(__('home.faq_q2')),
      "acceptedAnswer": { "@@type": "Answer", "text": @json(__('home.faq_a2_short')) } },
    { "@@type": "Question", "name": @json(__('home.faq_q3')),
      "acceptedAnswer": { "@@type": "Answer", "text": @json(__('home.faq_a3_short')) } },
    { "@@type": "Question", "name": @json(__('home.faq_q4')),
      "acceptedAnswer": { "@@type": "Answer", "text": @json(__('home.faq_a4_short')) } }
  ]
}
</script>
@endsection
