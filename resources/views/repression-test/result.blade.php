@extends('layouts.app')

@section('title', __('repression.seo.result_title', ['name' => $band['name'], 'label' => $band['label']]) . ' — ' . __('ui.site_name'))
@section('meta_description', __('repression.seo.result_description', ['line' => $band['line'], 'label' => $band['label']]))
@section('og_title', __('repression.seo.result_title', ['name' => $band['name'], 'label' => $band['label']]))
@section('og_description', $band['line'])
@section('canonical', route('repression-test.result', ['slug' => $band['slug']]))
@section('robots', $translated ? 'index,follow' : 'noindex,follow')

@section('schema')
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@graph' => [
        [
            '@type' => 'Article',
            'headline' => __('repression.seo.result_title', ['name' => $band['name'], 'label' => $band['label']]),
            'description' => $band['line'],
            'articleBody' => $band['long'],
            'url' => route('repression-test.result', ['slug' => $band['slug']]),
            'inLanguage' => str_replace('_', '-', app()->getLocale()),
            'isPartOf' => ['@type' => 'Quiz', 'name' => __('repression.title'), 'url' => route('repression-test.show')],
        ],
        [
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => __('ui.home'), 'item' => route('home')],
                ['@type' => 'ListItem', 'position' => 2, 'name' => __('repression.title'), 'item' => route('repression-test.show')],
                ['@type' => 'ListItem', 'position' => 3, 'name' => $band['name'], 'item' => route('repression-test.result', ['slug' => $band['slug']])],
            ],
        ],
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}
</script>
@endsection

@section('content')
<div class="container tt-page">
    <div class="tt-main">

        <article class="tt-verdict tt-c-{{ $band['colour'] }}">
            @if($result)
            <div class="tt-crown">{{ __('repression.result.crown') }}</div>
            <p class="rp-index">{{ $result['index'] }}<small>/100</small></p>
            @endif
            <h1 class="tt-name">{{ $band['name'] }}</h1>
            @if(! $result)
            <p class="tt-pct">{{ __('repression.result.crown') }} {{ $band['label'] }}</p>
            @endif
            <p class="tt-line">{{ $band['line'] }}</p>
            <p class="tt-long">{{ $band['long'] }}</p>
        </article>

        {{-- 五個級距的刻度尺。有分數的人看得到自己落在哪一格,沒分數的人看到的是
             這個級距在整條線上的位置 —— 順便是通往其他四頁的內部連結。 --}}
        <section class="tt-card">
            <h2>{{ __('repression.result.bands_title') }}</h2>
            <p class="tt-hint">{{ __('repression.result.scale_hint') }}</p>
            <ol class="rp-scale">
                @foreach($bands as $b)
                <li class="rp-step tt-c-{{ $b['colour'] }} {{ $b['key'] === $key ? 'is-current' : '' }}">
                    <a href="{{ route('repression-test.result', ['slug' => $b['slug']]) }}">
                        <span class="rp-step-label">{{ $b['label'] }}</span>
                        <span class="rp-step-name">{{ $b['name'] }}</span>
                    </a>
                </li>
                @endforeach
            </ol>
        </section>

        @if($result)
        <section class="tt-card">
            <h2>{{ __('repression.result.dimensions_title') }}</h2>
            <p class="tt-hint">{{ __('repression.result.dimensions_hint') }}</p>
            <div id="tt-bars">
                @foreach($result['dimensions'] as $d)
                <div class="tt-bar {{ $d['pct'] < 40 ? 'is-dim' : '' }}">
                    <span class="tt-bar-name">{{ $dimensions[$d['key']]['name'] }}</span>
                    <span class="tt-bar-track">
                        <span class="tt-bar-fill tt-c-{{ config('repression.dimensions.'.$d['key'].'.colour', 'gold') }}"
                              style="width:{{ $d['pct'] }}%"></span>
                    </span>
                    <span class="tt-bar-pct">{{ $d['pct'] }}%</span>
                </div>
                @endforeach
            </div>

            {{-- 最高與最低差距很小的時候講「你最明顯的是 X」會誤導 ——
                 五項都差不多本身就是一種結果,那就照實說。 --}}
            @php
                $top = $result['dimensions'][0];
                $spread = $top['pct'] - end($result['dimensions'])['pct'];
            @endphp
            <p class="tt-also">
                @if($spread >= 15)
                    {{ __('repression.result.top_dim') }}
                    <b>{{ $dimensions[$top['key']]['name'] }}</b> {{ $top['pct'] }}% —
                    {{ $dimensions[$top['key']]['note'] }}
                @else
                    {{ __('repression.result.flat') }}
                @endif
            </p>
        </section>
        @endif

        {{-- 深入解讀。鎖住的時候**完全不渲染**內容 —— 塞進 HTML 再用 CSS 遮起來,
             等於檢視原始碼就破解了,那跟沒有鎖一樣。 --}}
        <section class="tt-card tt-deep">
            <h2>{{ __('repression.result.deep_title') }}</h2>

            @if($unlocked)
                <div class="tt-sw tt-sw-plus">
                    <span class="tt-sw-tag">{{ __('repression.result.advice_title') }}</span>
                    <p>{{ $band['advice'] }}</p>
                </div>

                @if($reading)
                <h3 class="tt-deep-sub">{{ __('repression.result.reading_title') }}
                    <em>{{ __('repression.result.reading_personal') }}</em></h3>
                @foreach($reading as $r)
                <div class="tt-reading">
                    <div class="tt-reading-head">
                        <strong>{{ $r['name'] }}</strong>
                        <span>{{ $r['pct'] }}%</span>
                    </div>
                    <p>{{ $r['text'] }}</p>
                </div>
                @endforeach
                @endif

                <p class="tt-deep-note">{{ __('repression.result.deep_unlocked_note') }}</p>
            @else
                <p class="tt-deep-teaser">{{ __('repression.result.deep_locked') }}</p>
                @if(is_array(__('repression.result.deep_locked_list')))
                <ul class="tt-deep-list">
                    @foreach(__('repression.result.deep_locked_list') as $li)
                    <li>{{ $li }}</li>
                    @endforeach
                </ul>
                @endif
                <button type="button" class="btn btn-gold tt-deep-btn"
                        onclick="window.rewardedUnlockOpen && rewardedUnlockOpen()">
                    {{ __('minigame.rewarded_cta', ['minutes' => \App\Support\PremiumAccess::rewardedMinutes()]) }}
                </button>
            @endif
        </section>

        <p class="rp-disclaimer">{{ __('repression.disclaimer') }}</p>

        {{-- 結果讀完了再放。剛揭曉就插一個廣告是這一頁最傷的位置。 --}}
        @include('partials.ad-unit', ['zone' => 'home_banner'])

        <div class="tt-actions">
            <a href="{{ route('repression-test.show') }}" class="btn btn-primary btn-xl">
                {{ $result ? __('repression.retake') : __('repression.start') }}
            </a>
            <button type="button" class="btn btn-outline" id="tt-share">{{ __('repression.result.share') }}</button>
        </div>

        <section class="tt-faq">
            <h2>{{ __('repression.faq_title') }}</h2>
            @foreach(__('repression.faq') as $f)
            <details class="tt-faq-item">
                <summary>{{ $f['q'] }}</summary>
                <p>{{ $f['a'] }}</p>
            </details>
            @endforeach
        </section>

        <p class="rp-cross">
            {{ __('repression.result.other_test') }}
            <a href="{{ route('trait-test.show') }}">{{ __('traits.title') }}</a>
        </p>
    </div>

    <aside class="tt-rail">
        @include('partials.ad-unit', ['zone' => 'lobby_side'])
    </aside>
</div>

@include('partials.rewarded-unlock', ['barHidden' => true])
@endsection

@section('scripts')
<script>
(function () {
    var share = document.getElementById('tt-share');
    if (!share) return;

    share.addEventListener('click', function () {
        var url = @json(route('repression-test.result', ['slug' => $band['slug']]));
        // 手機有原生分享就用原生的,桌機退回複製連結
        if (navigator.share) {
            navigator.share({title: document.title, url: url}).catch(function () {});
            return;
        }
        navigator.clipboard.writeText(url).then(function () {
            share.textContent = @json(__('repression.result.copied'));
            setTimeout(function () { share.textContent = @json(__('repression.result.share')); }, 1800);
        });
    });
})();
</script>
@endsection
