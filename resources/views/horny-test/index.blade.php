@extends('layouts.app')

@section('title', __('horny.seo.title') . ' — ' . __('ui.site_name'))
@section('meta_description', __('horny.seo.description'))
@section('og_title', __('horny.seo.title'))
@section('og_description', __('horny.seo.description'))
@section('canonical', route('horny-test.show'))

{{-- 沒翻譯的語系標 noindex:讓搜尋引擎收錄一頁中文內容配英文網址,
     對排名是扣分不是加分。翻好之後把語系加進 config/horny.php 的 translated。 --}}
@section('robots', $translated ? 'index,follow' : 'noindex,follow')

@section('schema')
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@graph' => [
        [
            '@type' => 'Quiz',
            'name' => __('horny.seo.title'),
            'description' => __('horny.seo.description'),
            'url' => route('horny-test.show'),
            'educationalLevel' => 'adult',
            'numberOfQuestions' => count($questions),
            'inLanguage' => str_replace('_', '-', app()->getLocale()),
        ],
        [
            '@type' => 'FAQPage',
            'mainEntity' => collect(__('horny.faq'))->map(fn ($f) => [
                '@type' => 'Question',
                'name' => $f['q'],
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f['a']],
            ])->all(),
        ],
        [
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => __('ui.home'), 'item' => route('home')],
                ['@type' => 'ListItem', 'position' => 2, 'name' => __('horny.title'), 'item' => route('horny-test.show')],
            ],
        ],
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}
</script>
@endsection

@section('content')
<div class="container tt-page">
    <div class="tt-main">
        <header class="tt-head">
            <h1>{{ __('horny.h1') }}</h1>
            <p class="tt-tagline">{{ __('horny.tagline') }}</p>
            {{-- 一句重點 + 三個要點。原本是一整段一百多字的文字牆,畫面上沒有任何
                 落點,掃頁的人抓不到「這測驗在測什麼」。 --}}
            {{-- 封面圖的版位。圖片還沒有的時候整段不渲染 —— 線上就是線上,
                 空框比沒有那一塊更糟。檔名與尺寸見 public/images/dual-control/README.md --}}
            @php $cover = optional_image('images/dual-control/cover'); @endphp
            @if($cover)
            <figure class="tt-hero-img">
                <img src="{{ $cover }}" alt="{{ __('horny.h1') }}" loading="lazy" decoding="async">
            </figure>
            @endif

            <p class="tt-lead">{!! inline_emphasis(__('horny.intro_lead')) !!}</p>
            <dl class="tt-points">
                @foreach(__('horny.intro_points') as $point)
                <div class="tt-point">
                    <dt>{{ $point['k'] }}</dt>
                    <dd>{{ $point['v'] }}</dd>
                </div>
                @endforeach
            </dl>

            {{-- 封面。一進來就攤開 40 題會勸退,先給一個「開始」的緩衝。
                 題目仍然在 HTML 裡(SEO 與沒有 JS 的情況都要拿得到),
                 只是預設收起來。 --}}
            <div class="tt-facts">
                <span>{{ __('horny.facts.count', ['n' => count($questions)]) }}</span>
                <span>{{ __('horny.facts.time') }}</span>
                <span>{{ __('horny.facts.free') }}</span>
            </div>
            <button type="button" class="btn btn-primary btn-xl tt-start" id="tt-start">{{ __('horny.start') }}</button>
        </header>

        {{-- 兩條軸與九個面向先攤在測驗前面:對還沒作答的訪客(含搜尋引擎)來說,
             這是這一頁唯一說得出「這測驗到底在測什麼」的內容。 --}}
        <section class="tt-card">
            {{-- 九個面向也收起來:開場那三個要點已經講完「這在測什麼」,再攤九個
                 名字只是把還沒作答的人先淹一次。展開仍然看得到,而且連結與文字都
                 留在 HTML 裡 —— 這一頁對搜尋引擎仍然說得出自己在測什麼。 --}}
            <details class="tt-all-fold">
                <summary>
                    <span>{{ __('horny.result.axis_title') }}</span>
                    <em>{{ __('horny.axis_fold_hint') }}</em>
                </summary>
                <p class="tt-hint">{{ __('horny.result.axis_hint') }}</p>
                @foreach($axes as $axisKey => $axis)
                <div class="hm-axis">
                    <div class="hm-axis-head">
                        <span class="hm-axis-name">{{ $axis['name'] }}</span>
                        <span class="hm-axis-note">{{ $axis['low'] }} ←→ {{ $axis['high'] }}</span>
                    </div>
                    <p class="hm-axis-note">{{ $axis['note'] }}</p>
                    <ul class="rp-dims">
                        @foreach($dimensions as $dk => $d)
                            @if($d['axis'] === $axisKey)
                            <li class="rp-dim tt-c-{{ config('horny.dimensions.'.$dk.'.colour', 'gold') }}">
                                <strong>{{ $d['name'] }}</strong>
                                <span>{{ $d['note'] }}</span>
                            </li>
                            @endif
                        @endforeach
                    </ul>
                </div>
                @endforeach
            </details>
        </section>

        <form action="{{ route('horny-test.submit') }}" method="POST" id="tt-form" class="tt-collapsed">
            @csrf

            <div class="tt-progress">
                <div class="tt-progress-bar"><div class="tt-progress-fill" id="tt-fill"></div></div>
                <span class="tt-progress-count" id="tt-count">0 / {{ count($questions) }}</span>
            </div>

            @foreach($questions as $q)
                @if($q['section'])
                <h2 class="tt-section">{{ $q['section'] }}</h2>
                @endif

                <fieldset class="tt-q" id="tt-q{{ $q['n'] }}">
                    <legend class="sr-only">{{ $q['text'] }}</legend>
                    <span class="tt-q-no">{{ str_pad($q['n'] + 1, 2, '0', STR_PAD_LEFT) }}</span>
                    <p class="tt-q-text">{{ $q['text'] }}</p>
                    {{-- 符合在左、不符合在右:和屬性測驗同一個方向,兩個測驗的操作手感一致。
                         反轉的只是顯示順序,$i 仍是原本的索引,所以分數(value = $i)與
                         顏色(tt-opt-$i)都不變、不會算錯。 --}}
                    <div class="tt-scale">
                        @foreach(array_reverse($scale, true) as $i => $label)
                        <input type="radio" name="a[{{ $q['n'] }}]" id="a{{ $q['n'] }}_{{ $i }}" value="{{ $i }}"
                               {{ old('a.'.$q['n']) !== null && (int) old('a.'.$q['n']) === $i ? 'checked' : '' }}>
                        <label for="a{{ $q['n'] }}_{{ $i }}" class="tt-opt tt-opt-{{ $i }}"><span class="tt-dot"></span>{{ $label }}</label>
                        @endforeach
                    </div>
                </fieldset>

            @endforeach

            @error('a')<p class="tt-error">{{ $message }}</p>@enderror

            <div class="tt-actions">
                <button type="submit" class="btn btn-primary btn-xl" id="tt-submit">{{ __('horny.submit') }}</button>
            </div>
        </form>

        <p class="rp-disclaimer">{{ __('horny.disclaimer') }}</p>

        {{-- 這一頁**不列出**有幾種結果 —— 做完就知道了。
             代價要講清楚:五個象限頁因此少了一個從母頁進來的入口,發現路徑只剩
             sitemap 與「結果頁互相連結」。它們仍然在 sitemap 裡、每一頁也都連到
             另外四頁,所以不是孤島,但比有母頁入口弱一階。 --}}
        <section class="tt-faq">
            <h2>{{ __('horny.faq_title') }}</h2>
            @foreach(__('horny.faq') as $f)
            <details class="tt-faq-item">
                <summary>{{ $f['q'] }}</summary>
                <p>{{ $f['a'] }}</p>
            </details>
            @endforeach
        </section>


        {{-- 這一頁只留這一個內文版位,而且放到最後面。題目一開始是收起來的,
             所以「交卷按鈕之後」在還沒作答的人眼裡幾乎就在開始測驗那顆鈕底下。 --}}
        @include('partials.ad-unit', ['zone' => 'home_banner'])
    </div>

    <aside class="tt-rail">
        @include('partials.ad-unit', ['zone' => 'lobby_side'])
    </aside>
</div>
@endsection

@section('scripts')
<script>
(function () {
    var form = document.getElementById('tt-form');
    var total = {{ count($questions) }};

    /* 沒有 JS 的話題目就直接是展開的 —— 收合是 JS 加上去的增強,
       不是必要條件。爬蟲與關掉 JS 的人一樣讀得到全部題目。 */
    var start = document.getElementById('tt-start');
    start.hidden = false;
    /* 作答中把開場整塊藏起來(標題、標語、重點句、三個要點、facts)—— 那幾行是
       「要不要做」用的,已經開始做了就只是把題目往下推。
       用 class 掛在 .tt-page 上而不是逐個元素 hidden:開場的組成之後還會變,
       掛容器就不會漏掉哪一個。這是 JS 加上去的,初始 HTML 仍然完整,爬蟲照樣讀得到。 */
    var page = document.querySelector('.tt-page');
    function beginTaking() {
        form.classList.remove('tt-collapsed');
        start.hidden = true;
        if (page) page.classList.add('is-taking');
    }

    start.addEventListener('click', function () {
        beginTaking();
        form.querySelector('.tt-q').scrollIntoView({behavior: 'smooth', block: 'start'});
    });

    // 重載後帶著舊作答回來(驗證失敗)的話,直接展開,不要再擋一次
    if (form.querySelector('.tt-q input:checked')) {
        beginTaking();
    }

    function update() {
        var done = form.querySelectorAll('.tt-q input:checked').length;
        document.getElementById('tt-fill').style.width = (done / total * 100) + '%';
        document.getElementById('tt-count').textContent = done + ' / ' + total;
    }

    form.addEventListener('change', function (e) {
        if (e.target.type === 'radio') {
            e.target.closest('.tt-q').classList.add('answered');
            update();
        }
    });

    /* 交卷前先擋一次。伺服器一樣會驗(前端擋得住的只有手滑),但讓使用者
       直接跳到沒答的那一題,比整頁重載之後自己找快得多。 */
    form.addEventListener('submit', function (e) {
        var first = null;
        for (var i = 0; i < total; i++) {
            if (!form.querySelector('input[name="a[' + i + ']"]:checked')) { first = i; break; }
        }
        if (first === null) return;

        e.preventDefault();
        var el = document.getElementById('tt-q' + first);
        el.classList.add('missing');
        el.scrollIntoView({behavior: 'smooth', block: 'center'});
        var msg = document.getElementById('tt-missing');
        msg.textContent = @json(__('horny.unanswered', ['n' => '__N__']))
            .replace('__N__', total - form.querySelectorAll('.tt-q input:checked').length);
        msg.hidden = false;
    });

    var m = document.createElement('p');
    m.className = 'tt-error';
    m.id = 'tt-missing';
    m.hidden = true;
    form.querySelector('.tt-actions').before(m);

    update();
})();
</script>
@endsection
