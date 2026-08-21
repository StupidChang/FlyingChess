@extends('layouts.app')

@section('title', __('repression.seo.result_title', ['name' => $band['name'], 'label' => $band['label']]) . ' — ' . __('ui.site_name'))
@section('meta_description', __('repression.seo.result_description', ['line' => $band['line'], 'label' => $band['label']]))
@section('og_title', __('repression.seo.result_title', ['name' => $band['name'], 'label' => $band['label']]))
@section('og_description', $band['line'])
@section('canonical', route('repression-test.result', ['slug' => $band['slug']]))
@section('og_image', $ogImage)
@section('og_image_alt', __('repression.seo.result_title', ['name' => $band['name'], 'label' => $band['label']]))
@section('robots', $translated ? 'index,follow' : 'noindex,follow')

@section('schema')
{{-- articleBody 要對得上畫面上真的看得到的內容 —— 只放免費那幾段,鎖住的深入
     解讀不進來。結構化資料寫了頁面上沒有的東西,是 cloaking。 --}}
@php
    $articleBody = implode("\n", array_filter(array_merge(
        [$band['long']],
        (array) ($band['signals'] ?? []),
        (array) ($band['stuck'] ?? []),
        [$band['bedroom'] ?? null, $band['misread'] ?? null],
    )));
@endphp
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@graph' => [
        [
            '@type' => 'Article',
            'headline' => __('repression.seo.result_title', ['name' => $band['name'], 'label' => $band['label']]),
            'description' => $band['line'],
            'articleBody' => $articleBody,
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


        {{-- 免費區。從搜尋或分享連結進來的人沒有分數,這幾段就是他讀到的全部。
             語氣刻意直白 —— 講「實際會發生什麼」比講「你的心理狀態」有用,而且
             「性冷感」這種誤解正是搜尋的人真正在找的東西。 --}}
        @if(!empty($band['signals']))
        <section class="tt-card">
            <h2>{{ __('repression.result.signals', ['name' => $band['name']]) }}</h2>
            <p class="tt-hint">{{ __('repression.result.signals_hint') }}</p>
            <ul class="tt-signals">
                @foreach($band['signals'] as $signal)
                <li>{{ $signal }}</li>
                @endforeach
            </ul>
        </section>
        @endif

        @if(!empty($band['bedroom']))
        <section class="tt-card">
            <h2>{{ __('repression.result.bedroom') }}</h2>
            <p class="tt-body">{{ $band['bedroom'] }}</p>
        </section>
        @endif

        @if(!empty($band['stuck']))
        <section class="tt-card">
            <h2>{{ __('repression.result.stuck') }}</h2>
            <p class="tt-hint">{{ __('repression.result.stuck_hint') }}</p>
            <ul class="tt-signals">
                @foreach($band['stuck'] as $item)
                <li>{{ $item }}</li>
                @endforeach
            </ul>
        </section>
        @endif

        @if(!empty($band['misread']))
        <section class="tt-card">
            <h2>{{ __('repression.result.misread') }}</h2>
            <p class="tt-body">{{ $band['misread'] }}</p>
        </section>
        @endif

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

            {{-- 形狀先看,數字後看:全面偏高和只有一項突出是兩種狀態,
                 長條圖要一條一條比才看得出來 --}}
            @include('partials.repression-radar')

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

                @if(!empty($band['steps']))
                <h3 class="tt-deep-sub">{{ __('repression.result.steps_title') }}</h3>
                <ol class="rp-steps">
                    @foreach($band['steps'] as $step)
                    <li>{{ $step }}</li>
                    @endforeach
                </ol>
                @endif

                {{-- 給對方看的一段話。這種事自己解釋半天,常常不如一句寫好的話。 --}}
                @if(!empty($band['partner']))
                <div class="tt-partner">
                    <div class="tt-partner-head">
                        <span class="tt-partner-title">{{ __('repression.result.partner_title') }}</span>
                        <span class="tt-partner-hint">{{ __('repression.result.partner_hint') }}</span>
                    </div>
                    <blockquote class="tt-partner-quote">{{ $band['partner'] }}</blockquote>
                </div>
                @endif

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

        {{-- 依據。**不上鎖** —— 免費的人至少要知道這個數字怎麼來的。數字全部從
             config 現算,題目或反向題一改,這裡跟著改。 --}}
        <section class="tt-card tt-basis">
            <h2>{{ __('repression.result.basis_title') }}</h2>
            <p class="tt-hint">{{ __('repression.result.basis_hint') }}</p>

            <dl class="tt-basis-grid">
                <div>
                    <dt>{{ __('repression.result.basis_total') }}</dt>
                    <dd>{{ __('repression.result.basis_total_v', ['n' => $basis['total']]) }}</dd>
                </div>
                <div>
                    <dt>{{ __('repression.result.basis_range') }}</dt>
                    <dd>{{ __('repression.result.basis_range_v', ['min' => $range['min'], 'max' => $range['max']]) }}</dd>
                </div>
                <div>
                    <dt>{{ __('repression.result.basis_symmetry') }}</dt>
                    <dd>{{ $basis['symmetric']
                        ? __('repression.result.basis_symmetry_ok')
                        : __('repression.result.basis_symmetry_off') }}</dd>
                </div>
                @foreach($basis['dimensions'] as $d)
                <div>
                    <dt>{{ $d['name'] }}</dt>
                    <dd>{{ __('repression.result.basis_dim_v', [
                        'count' => $d['count'], 'forward' => $d['forward'], 'reverse' => $d['reverse']]) }}</dd>
                </div>
                @endforeach
            </dl>

            {{-- 有分數的人多一段。62 分和 59 分會被分到不同的頁、拿到不同的解讀,
                 但那三分之差在一份自陳量表裡沒有意義 —— 不講的話,這一頁看起來
                 比它實際上更確定。 --}}
            @if($confidence)
            <h3 class="tt-deep-sub">{{ __('repression.result.basis_your_title') }}</h3>
            <ul class="tt-basis-list">
                <li>{{ __('repression.result.basis_index', [
                    'index' => $confidence['index'], 'name' => $band['name'],
                    'min' => $confidence['range']['min'], 'max' => $confidence['range']['max']]) }}</li>
                @if($confidence['near_edge'])
                <li>{{ __('repression.result.basis_edge', [
                    'name' => $confidence['edge']['name'], 'dist' => $confidence['edge']['dist'],
                    'total' => $basis['total']]) }}</li>
                @endif
                <li>{{ $confidence['spread'] >= 15
                    ? __('repression.result.basis_spread', [
                        'top' => $confidence['top'], 'top_pct' => $confidence['top_pct'],
                        'low' => $confidence['low'], 'low_pct' => $confidence['low_pct'],
                        'spread' => $confidence['spread']])
                    : __('repression.result.basis_flat', ['spread' => $confidence['spread']]) }}</li>
                @if(!empty($confidence['meta']))
                <li>{{ __('repression.result.basis_answers', [
                    'decisive' => $confidence['meta']['decisive'], 'neutral' => $confidence['meta']['neutral']]) }}</li>
                @endif
            </ul>
            @endif

            <h3 class="tt-deep-sub">{{ __('repression.result.basis_formula_title') }}</h3>
            <p class="tt-basis-p">{{ __('repression.result.basis_formula') }}</p>

            <h3 class="tt-deep-sub">{{ __('repression.result.basis_limits_title') }}</h3>
            <p class="tt-basis-p">{{ __('repression.result.basis_limits', ['total' => $basis['total']]) }}</p>
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
