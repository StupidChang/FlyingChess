@extends('layouts.app')
@section('title', __('seo.lobby_title') . ' — ' . __('ui.site_name'))
@section('meta_description', __('seo.lobby_description'))
@section('og_title', __('seo.lobby_title') . ' — ' . __('ui.site_name'))
@section('og_description', __('seo.lobby_description'))
@section('canonical', route('game-hall.index'))
@section('content')

<section class="game-cards-section section">
    <div class="container">
        <div class="text-center" style="margin-bottom:48px">
            <span class="section-label">{{ __('games.lobby') }}</span>
            <h1 class="section-title">{{ __('seo.lobby_title') }}</h1>
            <p class="section-desc" style="max-width:520px;margin-left:auto;margin-right:auto">{{ __('seo.lobby_description') }}</p>
        </div>
        <div class="game-cards-grid">

            {{-- 飛行棋 --}}
            <article class="game-card">
                <div class="game-card-icon" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:40px;height:40px">
                        <path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25ZM12.75 6a.75.75 0 00-1.5 0v6c0 .414.336.75.75.75h4.5a.75.75 0 000-1.5h-3.75V6Z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <h3>{{ __('games.flying_chess') }}</h3>
                <p>{{ __('games.desc_flying_chess') }}</p>
                <span class="game-card-tag tag-couple">{{ __('games.tag_couple') }}</span>
                <a href="{{ route('games.lobby') }}" class="btn btn-gold btn-full">{{ __('games.start_game') }}</a>
            </article>

            {{-- 真心話大冒險 --}}
            <article class="game-card">
                <div class="game-card-icon" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:40px;height:40px">
                        <path d="M11.645 20.91l-.007-.003-.022-.012a15.247 15.247 0 01-.383-.218 25.18 25.18 0 01-4.244-3.17C4.688 15.36 2.25 12.174 2.25 8.25 2.25 5.322 4.714 3 7.688 3A5.5 5.5 0 0112 5.052 5.5 5.5 0 0116.313 3c2.973 0 5.437 2.322 5.437 5.25 0 3.925-2.438 7.111-4.739 9.256a25.175 25.175 0 01-4.244 3.17 15.247 15.247 0 01-.383.219l-.022.012-.007.004-.003.001a.752.752 0 01-.704 0l-.003-.001Z"/>
                    </svg>
                </div>
                <h3>{{ __('games.truth_dare') }}</h3>
                <p>{{ __('games.desc_truth_dare') }}</p>
                <span class="game-card-tag tag-couple">{{ __('games.tag_couple') }}</span>
                <a href="{{ route('truth-dare.lobby') }}" class="btn btn-gold btn-full">{{ __('games.start_game') }}</a>
            </article>

            {{-- 情侶撲克牌 --}}
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

            {{-- 骰子挑戰 --}}
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

            {{-- 誰最有可能 --}}
            <article class="game-card">
                <div class="game-card-icon" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:40px;height:40px">
                        <path d="M4.5 6.375a4.125 4.125 0 118.25 0 4.125 4.125 0 01-8.25 0zM14.25 8.625a3.375 3.375 0 116.75 0 3.375 3.375 0 01-6.75 0zM1.5 19.125a7.125 7.125 0 0114.25 0v.003l-.001.119a.75.75 0 01-.363.63 13.067 13.067 0 01-6.761 1.873c-2.472 0-4.786-.684-6.76-1.873a.75.75 0 01-.364-.63l-.001-.122zM17.25 19.128l-.001.144a2.25 2.25 0 01-.233.96 10.088 10.088 0 005.06-1.01.75.75 0 00.42-.643 4.875 4.875 0 00-6.957-4.611 8.586 8.586 0 011.71 5.157v.003z"/>
                    </svg>
                </div>
                <h3>{{ __('games.who_most_likely') }}</h3>
                <p>{{ __('games.desc_wml') }}</p>
                <span class="game-card-tag tag-party">{{ __('games.tag_party') }}</span>
                <a href="{{ route('who-most-likely.show') }}" class="btn btn-gold btn-full">{{ __('games.start_game') }}</a>
            </article>

            {{-- 共同清單 / 時間膠囊 暫時隱藏（保留程式碼，日後可還原：移除下面 @if(false)/@endif 即可） --}}
            @if(false)
            {{-- 共同清單 --}}
            <article class="game-card">
                <div class="game-card-icon" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:40px;height:40px">
                        <path fill-rule="evenodd" d="M2.625 6.75a1.125 1.125 0 112.25 0 1.125 1.125 0 01-2.25 0zm4.875 0A.75.75 0 018.25 6h12a.75.75 0 010 1.5h-12a.75.75 0 01-.75-.75zM2.625 12a1.125 1.125 0 112.25 0 1.125 1.125 0 01-2.25 0zM7.5 12a.75.75 0 01.75-.75h12a.75.75 0 010 1.5h-12A.75.75 0 017.5 12zm-4.875 5.25a1.125 1.125 0 112.25 0 1.125 1.125 0 01-2.25 0zm4.875 0a.75.75 0 01.75-.75h12a.75.75 0 010 1.5h-12a.75.75 0 01-.75-.75z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <h3>{{ __('games.bucket_list') }}</h3>
                <p>{{ __('games.desc_bucket') }}</p>
                <span class="game-card-tag tag-couple">{{ __('games.tag_couple') }}</span>
                <a href="{{ route('bucket-list.lobby') }}" class="btn btn-gold btn-full">{{ __('games.start_game') }}</a>
            </article>

            {{-- 時間膠囊 --}}
            <article class="game-card">
                <div class="game-card-icon" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:40px;height:40px">
                        <path d="M21.731 2.269a2.625 2.625 0 00-3.712 0l-1.157 1.157 3.712 3.712 1.157-1.157a2.625 2.625 0 000-3.712zM19.513 8.199l-3.712-3.712-12.15 12.15a5.25 5.25 0 00-1.32 2.214l-.8 2.685a.75.75 0 00.933.933l2.685-.8a5.25 5.25 0 002.214-1.32L19.513 8.2z"/>
                    </svg>
                </div>
                <h3>{{ __('games.time_capsule') }}</h3>
                <p>{{ __('games.desc_capsule') }}</p>
                <span class="game-card-tag tag-couple">{{ __('games.tag_couple') }}</span>
                <a href="{{ route('time-capsule.lobby') }}" class="btn btn-gold btn-full">{{ __('games.start_game') }}</a>
            </article>
            @endif

        </div>
    </div>
</section>

@endsection
