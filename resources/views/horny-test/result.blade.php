@extends('layouts.app')

@section('title', __('horny.seo.result_title', ['name' => $quad['name'], 'label' => $quad['label']]) . ' — ' . __('ui.site_name'))
@section('meta_description', __('horny.seo.result_description', ['line' => $quad['line'], 'label' => $quad['label']]))
@section('og_title', __('horny.seo.result_title', ['name' => $quad['name'], 'label' => $quad['label']]))
@section('og_description', $quad['line'])
@section('canonical', route('horny-test.result', ['slug' => $quad['slug']]))
@section('robots', $translated ? 'index,follow' : 'noindex,follow')

@section('schema')
{{-- articleBody 要對得上畫面上真的看得到的內容 —— 只放免費那幾段,鎖住的深入
     解讀不進來。結構化資料寫了頁面上沒有的東西,是 cloaking。 --}}
@php
    $articleBody = implode("\n", array_filter(array_merge(
        [$quad['long']],
        (array) ($quad['signals'] ?? []),
        (array) ($quad['stuck'] ?? []),
        [$quad['bedroom'] ?? null, $quad['misread'] ?? null],
    )));
@endphp
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@graph' => [
        [
            '@type' => 'Article',
            'headline' => __('horny.seo.result_title', ['name' => $quad['name'], 'label' => $quad['label']]),
            'description' => $quad['line'],
            'articleBody' => $articleBody,
            'url' => route('horny-test.result', ['slug' => $quad['slug']]),
            'inLanguage' => str_replace('_', '-', app()->getLocale()),
            'isPartOf' => ['@type' => 'Quiz', 'name' => __('horny.title'), 'url' => route('horny-test.show')],
        ],
        [
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => __('ui.home'), 'item' => route('home')],
                ['@type' => 'ListItem', 'position' => 2, 'name' => __('horny.title'), 'item' => route('horny-test.show')],
                ['@type' => 'ListItem', 'position' => 3, 'name' => $quad['name'], 'item' => route('horny-test.result', ['slug' => $quad['slug']])],
            ],
        ],
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}
</script>
@endsection

@section('content')
<div class="container tt-page">
    <div class="tt-main">

        <article class="tt-verdict tt-c-{{ $quad['colour'] }}">
            @if($result)
            <div class="tt-crown">{{ __('horny.result.crown') }}</div>
            <div class="hm-scores">
                @foreach($axes as $ak => $axis)
                <span class="hm-score">
                    <b class="hm-score-v">{{ $result['axes'][$ak] ?? 0 }}</b>
                    <span class="hm-score-k">{{ $axis['name'] }}</span>
                </span>
                @endforeach
            </div>
            @endif
            <h1 class="tt-name">{{ $quad['name'] }}</h1>
            <p class="tt-pct">{{ $quad['label'] }}</p>
            <p class="tt-line">{{ $quad['line'] }}</p>
            <p class="tt-long">{{ $quad['long'] }}</p>
        </article>

        {{-- 象限圖。這是這一份測驗的主角:位置比分數好記,而旁邊那三格是什麼
             也一起看得到。 --}}
        <section class="tt-card">
            <h2>{{ __('horny.result.map_title') }}</h2>
            @include('partials.horny-map')
            @if($confidence && ($confidence['is_middle'] ?? false))
            <p class="tt-also">{{ __('horny.result.middle_note', ['band' => $confidence['middle_band']]) }}</p>
            @endif
        </section>

        {{-- 免費區。從搜尋或分享連結進來的人沒有分數,這幾段就是他讀到的全部。
             語氣刻意直白 —— 講「實際會發生什麼」比講「你的心理狀態」有用,而且
             「性冷感」這種誤解正是搜尋的人真正在找的東西。 --}}
        @if(!empty($quad['signals']))
        <section class="tt-card">
            <h2>{{ __('horny.result.signals', ['name' => $quad['name']]) }}</h2>
            <p class="tt-hint">{{ __('horny.result.signals_hint') }}</p>
            <ul class="tt-signals">
                @foreach($quad['signals'] as $signal)
                <li>{{ $signal }}</li>
                @endforeach
            </ul>
        </section>
        @endif

        @if(!empty($quad['bedroom']))
        <section class="tt-card">
            <h2>{{ __('horny.result.bedroom') }}</h2>
            <p class="tt-body">{{ $quad['bedroom'] }}</p>
        </section>
        @endif

        @if(!empty($quad['stuck']))
        <section class="tt-card">
            <h2>{{ __('horny.result.stuck') }}</h2>
            <p class="tt-hint">{{ __('horny.result.stuck_hint') }}</p>
            <ul class="tt-signals">
                @foreach($quad['stuck'] as $item)
                <li>{{ $item }}</li>
                @endforeach
            </ul>
        </section>
        @endif

        @if(!empty($quad['misread']))
        <section class="tt-card">
            <h2>{{ __('horny.result.misread') }}</h2>
            <p class="tt-body">{{ $quad['misread'] }}</p>
        </section>
        @endif

        {{-- 五種位置。有分數的人看得到自己在哪一格,沒分數的人看到的是這一格
             在整張圖上的位置 —— 順便是通往其他四頁的內部連結。 --}}
        <section class="tt-card">
            <h2>{{ __('horny.result.quadrants_title') }}</h2>
            <div class="hm-all">
                @foreach($quadrants as $q)
                <a href="{{ route('horny-test.result', ['slug' => $q['slug']]) }}"
                   class="{{ $q['key'] === $key ? 'is-current' : '' }}">
                    <span class="hm-all-name">{{ $q['name'] }}</span>
                    <span class="hm-all-label">{{ $q['label'] }}</span>
                    <span class="hm-all-line">{{ $q['line'] }}</span>
                </a>
                @endforeach
            </div>
        </section>

        @if($result)
        <section class="tt-card">
            <h2>{{ __('horny.result.dimensions_title') }}</h2>
            <p class="tt-hint">{{ __('horny.result.dimensions_hint') }}</p>

            {{-- 九條線分兩組畫,不用雷達:九個角的雷達認不出形狀,而這一份要看的
                 本來就是「哪一組高」而不是整體的形狀。 --}}
            @foreach($axes as $ak => $axis)
            <div class="hm-axis">
                <div class="hm-axis-head">
                    <span class="hm-axis-name">{{ $axis['name'] }}</span>
                    <span class="hm-axis-score">{{ $result['axes'][$ak] ?? 0 }}<small>/100</small></span>
                </div>
                @foreach($result['dimensions'] as $d)
                    @if(($d['axis'] ?? null) === $ak)
                    <div class="tt-bar {{ $d['pct'] < 40 ? 'is-dim' : '' }}">
                        <span class="tt-bar-name">{{ $dimensions[$d['key']]['name'] }}</span>
                        <span class="tt-bar-track">
                            <span class="tt-bar-fill tt-c-{{ config('horny.dimensions.'.$d['key'].'.colour', 'gold') }}"
                                  style="width:{{ $d['pct'] }}%"></span>
                        </span>
                        <span class="tt-bar-pct">{{ $d['pct'] }}%</span>
                    </div>
                    @endif
                @endforeach
            </div>
            @endforeach

            @if($confidence)
            <p class="tt-also">
                {{ __('horny.result.top_dim') }}
                <b>{{ $confidence['top'] }}</b> {{ $confidence['top_pct'] }}%
            </p>
            @endif
        </section>
        @endif

        {{-- 深入解讀。鎖住的時候**完全不渲染**內容 —— 塞進 HTML 再用 CSS 遮起來,
             等於檢視原始碼就破解了,那跟沒有鎖一樣。 --}}
        <section class="tt-card tt-deep">
            <h2>{{ __('horny.result.deep_title') }}</h2>

            @if($unlocked)
                <div class="tt-sw tt-sw-plus">
                    <span class="tt-sw-tag">{{ __('horny.result.advice_title') }}</span>
                    <p>{{ $quad['advice'] }}</p>
                </div>

                @if(!empty($quad['steps']))
                <h3 class="tt-deep-sub">{{ __('horny.result.steps_title') }}</h3>
                <ol class="rp-steps">
                    @foreach($quad['steps'] as $step)
                    <li>{{ $step }}</li>
                    @endforeach
                </ol>
                @endif

                {{-- 給對方看的一段話。這種事自己解釋半天,常常不如一句寫好的話。 --}}
                @if(!empty($quad['partner']))
                <div class="tt-partner">
                    <div class="tt-partner-head">
                        <span class="tt-partner-title">{{ __('horny.result.partner_title') }}</span>
                        <span class="tt-partner-hint">{{ __('horny.result.partner_hint') }}</span>
                    </div>
                    <blockquote class="tt-partner-quote">{{ $quad['partner'] }}</blockquote>
                </div>
                @endif

                @if($reading)
                <h3 class="tt-deep-sub">{{ __('horny.result.reading_title') }}
                    <em>{{ __('horny.result.reading_personal') }}</em></h3>
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

                <p class="tt-deep-note">{{ __('horny.result.deep_unlocked_note') }}</p>
            @else
                <p class="tt-deep-teaser">{{ __('horny.result.deep_locked') }}</p>
                @if(is_array(__('horny.result.deep_locked_list')))
                <ul class="tt-deep-list">
                    @foreach(__('horny.result.deep_locked_list') as $li)
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
            <h2>{{ __('horny.result.basis_title') }}</h2>
            <p class="tt-hint">{{ __('horny.result.basis_hint') }}</p>

            <dl class="tt-basis-grid">
                <div>
                    <dt>{{ __('horny.result.basis_total') }}</dt>
                    <dd>{{ __('horny.result.basis_total_v', ['n' => $basis['total']]) }}</dd>
                </div>
                <div>
                    <dt>{{ __('horny.result.basis_axis') }}</dt>
                    <dd>{{ __('horny.result.basis_axis_v', [
                        'desire' => $basis['axis_counts']['desire'] ?? 0,
                        'brake' => $basis['axis_counts']['brake'] ?? 0]) }}</dd>
                </div>
                <div>
                    <dt>{{ __('horny.result.basis_middle') }}</dt>
                    <dd>{{ __('horny.result.basis_middle_v', ['band' => $basis['middle_band']]) }}</dd>
                </div>
                <div>
                    <dt>{{ __('horny.result.basis_symmetry') }}</dt>
                    <dd>{{ $basis['symmetric']
                        ? __('horny.result.basis_symmetry_ok')
                        : __('horny.result.basis_symmetry_off') }}</dd>
                </div>
                @foreach($basis['dimensions'] as $d)
                <div>
                    <dt>{{ $d['name'] }}</dt>
                    <dd>{{ __('horny.result.basis_dim_v', [
                        'count' => $d['count'], 'forward' => $d['forward'], 'reverse' => $d['reverse']]) }}</dd>
                </div>
                @endforeach
            </dl>

            {{-- 有分數的人多一段。49 分和 51 分會被分到不同的象限、拿到不同的解讀,
                 但那兩分之差在一份自陳量表裡沒有意義 —— 不講的話,這一頁看起來
                 比它實際上更確定。 --}}
            @if($confidence)
            <h3 class="tt-deep-sub">{{ __('horny.result.basis_your_title') }}</h3>
            <ul class="tt-basis-list">
                <li>{{ __('horny.result.basis_scores', [
                    'desire' => $confidence['axes']['desire'], 'brake' => $confidence['axes']['brake'],
                    'name' => $quad['name']]) }}</li>
                @if($confidence['near_edge'])
                <li>{{ __('horny.result.basis_edge', [
                    'dist' => $confidence['dist'], 'total' => $basis['total']]) }}</li>
                @endif
                <li>{{ $confidence['gap'] >= 15
                    ? __('horny.result.basis_gap', ['gap' => $confidence['gap']])
                    : __('horny.result.basis_close', ['gap' => $confidence['gap']]) }}</li>
                @if(!empty($confidence['meta']))
                <li>{{ __('horny.result.basis_answers', [
                    'decisive' => $confidence['meta']['decisive'], 'neutral' => $confidence['meta']['neutral']]) }}</li>
                @endif
            </ul>
            @endif

            <h3 class="tt-deep-sub">{{ __('horny.result.basis_formula_title') }}</h3>
            <p class="tt-basis-p">{{ __('horny.result.basis_formula') }}</p>

            <h3 class="tt-deep-sub">{{ __('horny.result.basis_limits_title') }}</h3>
            <p class="tt-basis-p">{{ __('horny.result.basis_limits', ['total' => $basis['total']]) }}</p>
        </section>

        <p class="rp-disclaimer">{{ __('horny.disclaimer') }}</p>

        {{-- 結果讀完了再放。剛揭曉就插一個廣告是這一頁最傷的位置。 --}}
        @include('partials.ad-unit', ['zone' => 'home_banner'])

        <div class="tt-actions">
            <a href="{{ route('horny-test.show') }}" class="btn btn-primary btn-xl">
                {{ $result ? __('horny.retake') : __('horny.start') }}
            </a>
            <button type="button" class="btn btn-outline" id="tt-share">{{ __('horny.result.share') }}</button>
        </div>

        <section class="tt-faq">
            <h2>{{ __('horny.faq_title') }}</h2>
            @foreach(__('horny.faq') as $f)
            <details class="tt-faq-item">
                <summary>{{ $f['q'] }}</summary>
                <p>{{ $f['a'] }}</p>
            </details>
            @endforeach
        </section>

        <p class="rp-cross">
            {{ __('horny.result.other_test') }}
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
        var url = @json(route('horny-test.result', ['slug' => $quad['slug']]));
        // 手機有原生分享就用原生的,桌機退回複製連結
        if (navigator.share) {
            navigator.share({title: document.title, url: url}).catch(function () {});
            return;
        }
        navigator.clipboard.writeText(url).then(function () {
            share.textContent = @json(__('horny.result.copied'));
            setTimeout(function () { share.textContent = @json(__('horny.result.share')); }, 1800);
        });
    });
})();
</script>
@endsection
