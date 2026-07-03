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
            <a href="{{ route('game-hall.index') }}" class="btn btn-gold btn-xl">{{ __('home.hero_cta_hall') }}</a>
            <a href="{{ route('games.lobby') }}" class="btn btn-outline-gold btn-xl">{{ __('home.hero_cta_chess') }}</a>
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
<section class="game-cards-section section reveal">
    <div class="container">
        <div class="text-center" style="margin-bottom:48px">
            <span class="section-label">{{ __('home.modes_label') }}</span>
            <h2 class="section-title">{{ __('home.modes_title') }}</h2>
            <p class="section-desc" style="max-width:520px;margin-left:auto;margin-right:auto">{{ __('home.modes_desc') }}</p>
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
                <span class="game-card-tag tag-online">{{ __('games.tag_online') }}</span>
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
                <span class="game-card-tag tag-online">{{ __('games.tag_online') }}</span>
                <a href="{{ route('truth-dare.lobby') }}" class="btn btn-gold btn-full">{{ __('home.mode_truth_cta') }}</a>
            </article>

            {{-- 抽卡 --}}
            <article class="game-card">
                <div class="game-card-icon" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:40px;height:40px">
                        <path d="M4 3a2 2 0 00-2 2v14a2 2 0 002 2h16a2 2 0 002-2V5a2 2 0 00-2-2H4zm1 2h2v2H5V5zm12 0h2v2h-2V5zM9.5 7.5a4.5 4.5 0 110 9 4.5 4.5 0 010-9zm5 0a4.5 4.5 0 110 9 4.5 4.5 0 010-9zM5 17h2v2H5v-2zm12 0h2v2h-2v-2z"/>
                    </svg>
                </div>
                <h3>{{ __('games.card_game') }}</h3>
                <p>{{ __('games.desc_card') }}</p>
                <span class="game-card-tag tag-party">{{ __('games.tag_party') }}</span>
                <a href="{{ route('card-game.show') }}" class="btn btn-gold btn-full">{{ __('games.start_game') }}</a>
            </article>

            {{-- 骰子 --}}
            <article class="game-card">
                <div class="game-card-icon" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:40px;height:40px">
                        <path d="M3 3h18a1 1 0 011 1v16a1 1 0 01-1 1H3a1 1 0 01-1-1V4a1 1 0 011-1zm4 4a1.5 1.5 0 100 3 1.5 1.5 0 000-3zm10 0a1.5 1.5 0 100 3 1.5 1.5 0 000-3zm-5 4a1.5 1.5 0 100 3 1.5 1.5 0 000-3zm-5 4a1.5 1.5 0 100 3 1.5 1.5 0 000-3zm10 0a1.5 1.5 0 100 3 1.5 1.5 0 000-3z"/>
                    </svg>
                </div>
                <h3>{{ __('games.dice_game') }}</h3>
                <p>{{ __('games.desc_dice') }}</p>
                <span class="game-card-tag tag-party">{{ __('games.tag_party') }}</span>
                <a href="{{ route('dice-game.show') }}" class="btn btn-gold btn-full">{{ __('games.start_game') }}</a>
            </article>

            {{-- 國王遊戲 --}}
            <article class="game-card">
                <div class="game-card-icon" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:40px;height:40px">
                        <path d="M4 3a2 2 0 00-2 2v14a2 2 0 002 2h16a2 2 0 002-2V5a2 2 0 00-2-2H4zm1 2h2v2H5V5zm12 0h2v2h-2V5zM9.5 7.5a4.5 4.5 0 110 9 4.5 4.5 0 010-9zm5 0a4.5 4.5 0 110 9 4.5 4.5 0 010-9zM5 17h2v2H5v-2zm12 0h2v2h-2v-2z"/>
                    </svg>
                </div>
                <h3>{{ __('games.king_game') }}</h3>
                <p>{{ __('games.desc_king') }}</p>
                <span class="game-card-tag tag-party">{{ __('games.tag_party') }}</span>
                <a href="{{ route('king-game.show') }}" class="btn btn-gold btn-full">{{ __('games.start_game') }}</a>
            </article>

            {{-- 命運轉盤 --}}
            <article class="game-card">
                <div class="game-card-icon" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:40px;height:40px">
                        <path fill-rule="evenodd" d="M4.755 10.059a7.5 7.5 0 0112.548-3.364l1.903 1.903h-3.183a.75.75 0 000 1.5h4.992a.75.75 0 00.75-.75V4.356a.75.75 0 00-1.5 0v3.18l-1.9-1.9A9 9 0 003.306 9.67a.75.75 0 101.45.388Zm15.408 3.882a.75.75 0 00-.163.577 7.5 7.5 0 01-12.548 3.364l-1.902-1.903h3.183a.75.75 0 000-1.5H3.74a.75.75 0 00-.75.75v4.992a.75.75 0 001.5 0v-3.18l1.9 1.9A9 9 0 0020.694 14.33a.75.75 0 00-1.45-.388.75.75 0 00.919 0Z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <h3>{{ __('games.wheel_game') }}</h3>
                <p>{{ __('games.desc_wheel') }}</p>
                <span class="game-card-tag tag-party">{{ __('games.tag_party') }}</span>
                <a href="{{ route('wheel-game.show') }}" class="btn btn-gold btn-full">{{ __('games.start_game') }}</a>
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

            {{-- 情侶清單 --}}
            <article class="game-card">
                <div class="game-card-icon" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:40px;height:40px">
                        <path fill-rule="evenodd" d="M2.625 6.75a1.125 1.125 0 112.25 0 1.125 1.125 0 01-2.25 0zm4.875 0A.75.75 0 018.25 6h12a.75.75 0 010 1.5h-12a.75.75 0 01-.75-.75zM2.625 12a1.125 1.125 0 112.25 0 1.125 1.125 0 01-2.25 0zM7.5 12a.75.75 0 01.75-.75h12a.75.75 0 010 1.5h-12A.75.75 0 017.5 12zm-4.875 5.25a1.125 1.125 0 112.25 0 1.125 1.125 0 01-2.25 0zm4.875 0a.75.75 0 01.75-.75h12a.75.75 0 010 1.5h-12a.75.75 0 01-.75-.75z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <h3>{{ __('games.bucket_list') }}</h3>
                <p>{{ __('games.desc_bucket') }}</p>
                <span class="game-card-tag tag-online">{{ __('games.tag_online') }}</span>
                <a href="{{ route('bucket-list.lobby') }}" class="btn btn-gold btn-full">{{ __('games.start_game') }}</a>
            </article>

            {{-- 時光膠囊 --}}
            <article class="game-card">
                <div class="game-card-icon" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:40px;height:40px">
                        <path d="M21.731 2.269a2.625 2.625 0 00-3.712 0l-1.157 1.157 3.712 3.712 1.157-1.157a2.625 2.625 0 000-3.712zM19.513 8.199l-3.712-3.712-12.15 12.15a5.25 5.25 0 00-1.32 2.214l-.8 2.685a.75.75 0 00.933.933l2.685-.8a5.25 5.25 0 002.214-1.32L19.513 8.2z"/>
                    </svg>
                </div>
                <h3>{{ __('games.time_capsule') }}</h3>
                <p>{{ __('games.desc_capsule') }}</p>
                <span class="game-card-tag tag-online">{{ __('games.tag_online') }}</span>
                <a href="{{ route('time-capsule.lobby') }}" class="btn btn-gold btn-full">{{ __('games.start_game') }}</a>
            </article>

            {{-- 社群棋盤 --}}
            <article class="game-card">
                <div class="game-card-icon" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:40px;height:40px">
                        <path d="M4.5 6.375a4.125 4.125 0 118.25 0 4.125 4.125 0 01-8.25 0ZM14.25 8.625a3.375 3.375 0 116.75 0 3.375 3.375 0 01-6.75 0ZM1.5 19.125a7.125 7.125 0 0114.25 0v.003l-.001.119a.75.75 0 01-.363.63 13.067 13.067 0 01-6.761 1.873c-2.472 0-4.786-.684-6.76-1.873a.75.75 0 01-.364-.63l-.001-.122ZM17.25 19.128l-.001.144a2.25 2.25 0 01-.233.96 10.088 10.088 0 005.06-1.01.75.75 0 00.42-.643 4.875 4.875 0 00-6.957-4.611 8.586 8.586 0 011.71 5.157v.003Z"/>
                    </svg>
                </div>
                <h3>{{ __('ui.community_boards') }}</h3>
                <p>{{ __('home.mode_community_desc') }}</p>
                <a href="{{ route('boards.community') }}" class="btn btn-gold btn-full">{{ __('home.view_all') }}</a>
            </article>
        </div>
    </div>
</section>

{{-- ======================================================
     How it works
     ====================================================== --}}
<section class="how-section reveal">
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
                        <path fill-rule="evenodd" d="M19.902 4.098a3.75 3.75 0 00-5.304 0l-4.5 4.5a3.75 3.75 0 001.035 6.037.75.75 0 01-.646 1.353 5.25 5.25 0 01-1.449-8.45l4.5-4.5a5.25 5.25 0 117.424 7.424l-1.757 1.757a.75.75 0 11-1.06-1.06l1.757-1.757a3.75 3.75 0 000-5.304zm-7.389 4.267a.75.75 0 011-.353 5.25 5.25 0 011.449 8.45l-4.5 4.5a5.25 5.25 0 11-7.424-7.424l1.757-1.757a.75.75 0 111.06 1.06l-1.757 1.757a3.75 3.75 0 105.304 5.304l4.5-4.5a3.75 3.75 0 00-1.035-6.037.75.75 0 01-.354-1z" clip-rule="evenodd"/>
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
     My Boards (authenticated users)
     ====================================================== --}}
@auth
<section class="boards-section container reveal">
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
<section class="features-section section reveal">
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
<section class="faq-section reveal">
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
<section class="closing-cta-section reveal">
    <div class="closing-cta-inner">
        <h2>{{ __('home.cta_close_title_pre') }}<span>{{ __('home.cta_close_title_high') }}</span>{{ __('home.cta_close_title_post') }}</h2>
        <p>{{ __('home.cta_close_sub') }}</p>
        <div class="closing-cta-btns">
            <a href="{{ route('game-hall.index') }}" class="btn btn-gold btn-xl">{{ __('home.cta_close_btn_play') }}</a>
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

// Scroll-reveal: one-time fade+rise per section when it enters the viewport.
// Below-the-fold only (hero/stats are excluded), no layout shift (opacity+transform
// only), and fully skipped for prefers-reduced-motion or missing IntersectionObserver.
(function () {
    var reveals = document.querySelectorAll('.reveal');
    if (!reveals.length) return;

    var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (reduceMotion || !('IntersectionObserver' in window)) {
        reveals.forEach(function (el) { el.classList.add('is-visible'); });
        return;
    }

    var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
                io.unobserve(entry.target);
            }
        });
    }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });

    reveals.forEach(function (el) { io.observe(el); });
})();
</script>
<noscript><style>.reveal{opacity:1!important;transform:none!important}</style></noscript>
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
