@extends('layouts.app')

@section('title', __('traits.seo.result_title', ['name' => $item['name']]) . ' — ' . __('ui.site_name'))
@section('meta_description', __('traits.seo.result_description', ['name' => $item['name'], 'line' => $item['line']]))
@section('og_title', __('traits.seo.result_title', ['name' => $item['name']]))
@section('og_description', $item['line'])
@section('canonical', route('trait-test.result', ['slug' => $item['slug']]))
@section('robots', $translated ? 'index,follow' : 'noindex,follow')

@section('schema')
{{-- articleBody 要對得上畫面上真的看得到的內容 —— 只放免費那幾段,鎖住的深入
     解讀不進來。結構化資料寫了頁面上沒有的東西,是 cloaking。 --}}
@php
    $articleBody = implode("\n", array_filter(array_merge(
        [$item['long']],
        (array) ($item['signals'] ?? []),
        [$item['bedroom'] ?? null, $item['everyday'] ?? null],
    )));
@endphp
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@graph' => [
        [
            '@type' => 'Article',
            'headline' => __('traits.seo.result_title', ['name' => $item['name']]),
            'description' => $item['line'],
            'articleBody' => $articleBody,
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
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}
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

        {{-- 免費區。從搜尋或分享連結進來的人沒有分數,這幾段就是他讀到的全部 ——
             在這之前只有一句總結加一段介紹,20 個結果頁對搜尋引擎幾乎是同一頁。
             這裡每一型都不一樣,下面的共現與依據則是從權重表現算的。 --}}
        @if(!empty($item['signals']))
        <section class="tt-card">
            <h2>{{ __('traits.result.signals', ['name' => $item['name']]) }}</h2>
            <p class="tt-hint">{{ __('traits.result.signals_hint') }}</p>
            <ul class="tt-signals">
                @foreach($item['signals'] as $signal)
                <li>{{ $signal }}</li>
                @endforeach
            </ul>
        </section>
        @endif

        @if(!empty($item['bedroom']))
        <section class="tt-card">
            <h2>{{ __('traits.result.bedroom', ['name' => $item['name']]) }}</h2>
            <p class="tt-body">{{ $item['bedroom'] }}</p>
        </section>
        @endif

        @if(!empty($item['everyday']))
        <section class="tt-card">
            <h2>{{ __('traits.result.everyday') }}</h2>
            <p class="tt-body">{{ $item['everyday'] }}</p>
        </section>
        @endif

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

                {{-- 優勢 / 盲點:一正一反,顏色分開,一眼看得出哪個是好消息哪個要留意 --}}
                @if(!empty($item['strength']) || !empty($item['watch']))
                <div class="tt-swgrid">
                    @if(!empty($item['strength']))
                    <div class="tt-sw tt-sw-plus">
                        <span class="tt-sw-tag">{{ __('traits.result.deep_strength') }}</span>
                        <p>{{ $item['strength'] }}</p>
                    </div>
                    @endif
                    @if(!empty($item['watch']))
                    <div class="tt-sw tt-sw-minus">
                        <span class="tt-sw-tag">{{ __('traits.result.deep_watch') }}</span>
                        <p>{{ $item['watch'] }}</p>
                    </div>
                    @endif
                </div>
                @endif

                {{-- 合拍 / 磨合 --}}
                @if(!empty($item['match']) || !empty($item['friction']))
                <h3 class="tt-deep-sub">{{ __('traits.result.deep_pairing') }}</h3>
                @if(!empty($item['match']))
                <div class="tt-pair tt-pair-match">
                    <span class="tt-pair-tag">{{ __('traits.result.deep_match') }}</span>
                    <p>{{ $item['match'] }}</p>
                </div>
                @endif
                @if(!empty($item['friction']))
                <div class="tt-pair tt-pair-friction">
                    <span class="tt-pair-tag">{{ __('traits.result.deep_friction') }}</span>
                    <p>{{ $item['friction'] }}</p>
                </div>
                @endif
                @endif

                {{-- 給對方看的一句話 --}}
                @if(!empty($item['partner_line']))
                <div class="tt-partner">
                    <div class="tt-partner-head">
                        <span class="tt-partner-title">{{ __('traits.result.deep_partner_title') }}</span>
                        <span class="tt-partner-hint">{{ __('traits.result.deep_partner_hint') }}</span>
                    </div>
                    <blockquote class="tt-partner-quote">{{ $item['partner_line'] }}</blockquote>
                </div>
                @endif

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
                @if(is_array(__('traits.result.deep_locked_list')))
                <ul class="tt-deep-list">
                    @foreach(__('traits.result.deep_locked_list') as $li)
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

        {{-- 依據。**不上鎖** —— 免費的人至少要知道這個數字是怎麼來的,不然「你 87%
             像露出型」跟星座沒兩樣。數字全部從 config 的權重表現算,題目改了這裡
             跟著改;手寫的話遲早對不上,而對不上的依據比沒有依據更糟。 --}}
        <section class="tt-card tt-basis">
            <h2>{{ __('traits.result.basis_title') }}</h2>
            <p class="tt-hint">{{ __('traits.result.basis_hint') }}</p>

            <dl class="tt-basis-grid">
                <div>
                    <dt>{{ __('traits.result.basis_questions') }}</dt>
                    <dd>{{ __('traits.result.basis_questions_v', ['n' => $basis['count'], 'total' => $basis['total']]) }}</dd>
                </div>
                <div>
                    <dt>{{ __('traits.result.basis_sections') }}</dt>
                    {{-- 用斜線不用頓號:段落名本身就含頓號(表達、界線與收尾),
                         串起來會讀成兩個段落 --}}
                    <dd>{{ implode(' / ', $basis['sections']) }}</dd>
                </div>
                <div>
                    <dt>{{ __('traits.result.basis_reverse') }}</dt>
                    <dd>{{ $basis['reverse']
                        ? __('traits.result.basis_reverse_v', ['n' => $basis['reverse']])
                        : __('traits.result.basis_reverse_none') }}</dd>
                </div>
                @foreach($basis['axes'] as $axis)
                <div>
                    <dt>{{ $axis['label'] }}</dt>
                    <dd>{{ __('traits.result.basis_axis_v', ['n' => $axis['n'], 'total' => $axis['total'], 'lean' => $axis['lean']]) }}</dd>
                </div>
                @endforeach
            </dl>

            {{-- 有分數的人多一段:主屬性領先多少、有沒有並列、作答夠不夠明確。
                 領先 1% 也印一頂王冠是這類測驗最容易誤導人的地方。 --}}
            @if($confidence)
            @php
                $tied = collect($confidence['tied']);
                $tiedText = $tied->take(3)->implode('、')
                    . ($tied->count() > 3 ? __('traits.result.basis_tied_more', ['n' => $tied->count() - 3]) : '');
            @endphp
            <h3 class="tt-deep-sub">{{ __('traits.result.basis_your_title') }}</h3>
            <ul class="tt-basis-list">
                <li>{{ __('traits.result.basis_gap', [
                    'top' => $confidence['top'], 'pct' => $confidence['pct'], 'gap' => $confidence['gap']]) }}</li>
                @if($tied->isNotEmpty())
                <li>{{ __('traits.result.basis_tied', [
                    'within' => \App\Services\TraitTestService::TIED_WITHIN, 'names' => $tiedText]) }}</li>
                @endif
                <li>{{ __('traits.result.basis_strong', [
                    'n' => $confidence['strong'], 'at' => \App\Services\TraitTestService::STRONG_AT]) }}</li>
                @if(!empty($confidence['meta']))
                <li>{{ __('traits.result.basis_answers', [
                    'decisive' => $confidence['meta']['decisive'], 'neutral' => $confidence['meta']['neutral']]) }}</li>
                @endif
            </ul>
            @endif

            <h3 class="tt-deep-sub">{{ __('traits.result.basis_formula_title') }}</h3>
            <p class="tt-basis-p">{{ __('traits.result.basis_formula') }}</p>

            <h3 class="tt-deep-sub">{{ __('traits.result.basis_limits_title') }}</h3>
            <p class="tt-basis-p">{{ __('traits.result.basis_limits', ['total' => $basis['total']]) }}</p>
        </section>

        {{-- 共現。不是手寫的「相關屬性」清單,是權重表本身的結構:同一題正權重餵到
             的兩個屬性天生會一起升高,一正一負的互為反面。手寫清單遲早跟題目脫節。 --}}
        @if($related['together'])
        <section class="tt-card">
            <h2>{{ __('traits.result.related', ['name' => $item['name']]) }}</h2>
            <p class="tt-hint">{{ __('traits.result.related_hint') }}</p>
            <div class="tt-rel">
                @foreach($related['together'] as $rel)
                <a class="tt-rel-item tt-c-{{ $rel['colour'] }}"
                   href="{{ route('trait-test.result', ['slug' => $rel['slug']]) }}">
                    <span class="tt-rel-head">
                        <strong>{{ $rel['name'] }}</strong>
                        <em>{{ __('traits.result.related_shared', ['n' => $rel['shared']]) }}</em>
                    </span>
                    <span class="tt-rel-line">{{ $rel['line'] }}</span>
                </a>
                @endforeach
            </div>
        </section>
        @endif

        @if($related['against'])
        <section class="tt-card">
            <h2>{{ __('traits.result.tension') }}</h2>
            <p class="tt-hint">{{ __('traits.result.tension_hint', ['name' => $item['name']]) }}</p>
            <div class="tt-rel">
                @foreach($related['against'] as $rel)
                <a class="tt-rel-item tt-c-{{ $rel['colour'] }}"
                   href="{{ route('trait-test.result', ['slug' => $rel['slug']]) }}">
                    <span class="tt-rel-head">
                        <strong>{{ $rel['name'] }}</strong>
                        <em>{{ __('traits.result.tension_shared', ['n' => $rel['shared']]) }}</em>
                    </span>
                    <span class="tt-rel-line">{{ $rel['line'] }}</span>
                </a>
                @endforeach
            </div>
        </section>
        @endif

        {{-- 結果讀完了再放。剛揭曉就插一個廣告是這一頁最傷的位置。 --}}
        @include('partials.ad-unit', ['zone' => 'home_banner'])

        <div class="tt-actions">
            <a href="{{ route('trait-test.show') }}" class="btn btn-primary btn-xl">
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
