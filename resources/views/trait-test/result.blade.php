@extends('layouts.app')

@section('title', __('traits.seo.result_title', ['name' => $item['name']]) . ' — ' . __('ui.site_name'))
@section('meta_description', __('traits.seo.result_description', ['name' => $item['name'], 'line' => $item['line']]))
@section('og_title', __('traits.seo.result_title', ['name' => $item['name']]))
@section('og_description', $item['line'])
@section('canonical', route('trait-test.result', ['slug' => $item['slug']]))
@section('robots', $translated ? 'index,follow' : 'noindex,follow')

@section('schema')
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@graph' => [
        [
            '@type' => 'Article',
            'headline' => __('traits.seo.result_title', ['name' => $item['name']]),
            'description' => $item['line'],
            'articleBody' => $item['long'],
            'url' => route('trait-test.result', ['slug' => $item['slug']]),
            'inLanguage' => str_replace('_', '-', app()->getLocale()),
            'isPartOf' => ['@type' => 'Quiz', 'name' => __('traits.title'), 'url' => route('trait-test.show')],
        ],
        [
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => __('ui.home'), 'item' => route('home')],
                ['@type' => 'ListItem', 'position' => 2, 'name' => __('traits.title'), 'item' => route('trait-test.show')],
                ['@type' => 'ListItem', 'position' => 3, 'name' => $item['name'], 'item' => route('trait-test.result', ['slug' => $item['slug']])],
            ],
        ],
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
</script>
@endsection

@section('content')
<div class="container tt-page">
    <div class="tt-main">

        <article class="tt-verdict tt-c-{{ $item['colour'] }}">
            @if($result)
            <div class="tt-crown">{{ __('traits.result.crown') }}</div>
            @endif
            <h1 class="tt-name">{{ $item['name'] }}</h1>
            @if($result)
            <p class="tt-pct">{{ $result['traits'][0]['pct'] }}%</p>
            @endif
            <p class="tt-line">{{ $item['line'] }}</p>
            <p class="tt-long">{{ $item['long'] }}</p>

            @if($result)
                @php $also = collect($result['traits'])->slice(1, 3)->filter(fn ($t) => $t['pct'] >= 50); @endphp
                <p class="tt-also">
                    @if($also->isNotEmpty())
                        {{ __('traits.result.also') }}
                        @foreach($also as $t)
                            <b>{{ $items[$t['key']]['name'] }}</b> {{ $t['pct'] }}%@if(! $loop->last)、@endif
                        @endforeach
                    @else
                        {{ __('traits.result.concentrated') }}
                    @endif
                </p>
            @endif
        </article>

        @if($result)
        <section class="tt-card">
            <h2>{{ __('traits.result.distribution') }}</h2>
            <p class="tt-hint">{{ __('traits.result.distribution_hint') }}</p>
            <div id="tt-bars">
                @foreach($result['traits'] as $i => $t)
                <div class="tt-bar {{ $i >= 8 ? 'tt-bar-extra' : '' }} {{ $t['pct'] < 40 ? 'is-dim' : '' }}"
                     {{ $i >= 8 ? 'hidden' : '' }}>
                    <a class="tt-bar-name" href="{{ route('trait-test.result', ['slug' => $items[$t['key']]['slug']]) }}">{{ $items[$t['key']]['name'] }}</a>
                    <span class="tt-bar-track">
                        <span class="tt-bar-fill tt-c-{{ config('traits.traits.'.$t['key'].'.colour', 'gold') }}"
                              style="width:{{ $t['pct'] }}%"></span>
                    </span>
                    <span class="tt-bar-pct">{{ $t['pct'] }}%</span>
                </div>
                @endforeach
            </div>
            <button type="button" class="tt-more" id="tt-toggle">{{ __('traits.result.show_all') }}</button>
        </section>

        <section class="tt-card">
            <h2>{{ __('traits.result.spectrums') }}</h2>
            <p class="tt-hint">{{ __('traits.result.spectrums_hint') }}</p>
            @foreach($axes as $id => $a)
                @php
                    $v = $result['axes'][$id] ?? 0;
                    $p = round(($v + 8) / 16 * 100);
                    $leans = $p >= 50;
                @endphp
                <div class="tt-axis">
                    <div class="tt-axis-head">
                        <span>{{ $a['note'] }}</span>
                        <span class="tt-axis-lead">{{ $leans ? $a['left'] : $a['right'] }} {{ round(abs($p - 50) * 2) }}%</span>
                    </div>
                    <div class="tt-axis-track">
                        <span class="tt-axis-fill" style="{{ $leans ? 'left:'.(100 - $p).'%;right:50%' : 'left:50%;right:'.$p.'%' }}"></span>
                        <span class="tt-axis-mid"></span>
                    </div>
                    <div class="tt-axis-foot"><span>{{ $a['left'] }}</span><span>{{ $a['right'] }}</span></div>
                </div>
            @endforeach
        </section>

        <div class="tt-save">
            @auth
                <p>{{ __('traits.result.saved') }}</p>
                <a href="{{ route('profile.index') }}" class="btn btn-sm btn-outline-gold">{{ __('traits.profile.heading') }}</a>
            @else
                <p>{{ __('traits.result.save_prompt') }}</p>
                <a href="{{ route('login') }}" class="btn btn-sm btn-outline-gold">{{ __('traits.result.login_to_save') }}</a>
            @endauth
        </div>
        @endif


        {{-- 深入解讀。鎖住的時候**完全不渲染**內容 —— 塞進 HTML 再用 CSS 遮起來,
             等於檢視原始碼就破解了,那跟沒有鎖一樣。 --}}
        <section class="tt-card tt-deep">
            <h2>{{ __('traits.result.deep_title') }}</h2>

            @if($unlocked)
                <p class="tt-deep-body">{{ $item['deep'] }}</p>

                @if($axisReading)
                <h3 class="tt-deep-sub">{{ __('traits.result.axis_reading_title') }}
                    <em>{{ __('traits.result.axis_personal') }}</em></h3>
                @foreach($axisReading as $r)
                <div class="tt-reading">
                    <div class="tt-reading-head">
                        <strong>{{ $r['label'] }}</strong>
                        <span>{{ $r['lean'] ? $r['lean'].' '.$r['strength'].'%' : __('traits.result.balanced') }}</span>
                    </div>
                    <p>{{ $r['text'] }}</p>
                </div>
                @endforeach
                @endif

                <p class="tt-deep-note">{{ __('traits.result.deep_unlocked_note') }}</p>
            @else
                <p class="tt-deep-teaser">{{ __('traits.result.deep_locked') }}</p>
                <button type="button" class="btn btn-gold tt-deep-btn"
                        onclick="window.rewardedUnlockOpen && rewardedUnlockOpen()">
                    {{ __('minigame.rewarded_cta', ['minutes' => \App\Support\PremiumAccess::rewardedMinutes()]) }}
                </button>
            @endif
        </section>

        {{-- 結果讀完了再放。剛揭曉就插一個廣告是這一頁最傷的位置。 --}}
        @include('partials.ad-unit', ['zone' => 'home_banner'])

        <div class="tt-actions">
            <a href="{{ route('trait-test.show') }}" class="btn btn-gold btn-xl">
                {{ $result ? __('traits.retake') : __('traits.start') }}
            </a>
            <button type="button" class="btn btn-outline" id="tt-share">{{ __('traits.result.share') }}</button>
        </div>

        {{-- 20 種屬性互相連結。對搜尋引擎是內部連結網,對讀者是「還有哪些型」。 --}}
        <section class="tt-card">
            <h2>{{ __('traits.result.all_traits') }}</h2>
            <div class="tt-all">
                @foreach($items as $k => $other)
                <a href="{{ route('trait-test.result', ['slug' => $other['slug']]) }}"
                   class="tt-chip tt-c-{{ config('traits.traits.'.$k.'.colour', 'gold') }} {{ $k === $key ? 'is-current' : '' }}">
                    {{ $other['name'] }}
                </a>
                @endforeach
            </div>
        </section>

        <section class="tt-faq">
            <h2>{{ __('traits.faq_title') }}</h2>
            @foreach(__('traits.faq') as $f)
            <details class="tt-faq-item">
                <summary>{{ $f['q'] }}</summary>
                <p>{{ $f['a'] }}</p>
            </details>
            @endforeach
        </section>
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
    var toggle = document.getElementById('tt-toggle');
    if (toggle) {
        var open = false;
        toggle.addEventListener('click', function () {
            open = !open;
            document.querySelectorAll('.tt-bar-extra').forEach(function (el) { el.hidden = !open; });
            toggle.textContent = open ? @json(__('traits.result.show_top')) : @json(__('traits.result.show_all'));
        });
    }

    var share = document.getElementById('tt-share');
    if (share) {
        share.addEventListener('click', function () {
            var url = @json(route('trait-test.result', ['slug' => $item['slug']]));
            // 手機有原生分享就用原生的,桌機退回複製連結
            if (navigator.share) {
                navigator.share({title: document.title, url: url}).catch(function () {});
                return;
            }
            navigator.clipboard.writeText(url).then(function () {
                share.textContent = @json(__('traits.result.copied'));
                setTimeout(function () { share.textContent = @json(__('traits.result.share')); }, 1800);
            });
        });
    }
})();
</script>
@endsection
