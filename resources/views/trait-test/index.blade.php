@extends('layouts.app')

@section('title', __('traits.seo.title') . ' — ' . __('ui.site_name'))
@section('meta_description', __('traits.seo.description'))
@section('og_title', __('traits.seo.title'))
@section('og_description', __('traits.seo.description'))
@section('canonical', route('trait-test.show'))

{{-- 沒翻譯的語系標 noindex:讓搜尋引擎收錄一頁中文內容配英文網址,
     對排名是扣分不是加分。翻好之後把語系加進 config/traits.php 的 translated。 --}}
@section('robots', $translated ? 'index,follow' : 'noindex,follow')

@section('schema')
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@graph' => [
        [
            '@type' => 'Quiz',
            'name' => __('traits.seo.title'),
            'description' => __('traits.seo.description'),
            'url' => route('trait-test.show'),
            'educationalLevel' => 'adult',
            'numberOfQuestions' => count($questions),
            'inLanguage' => str_replace('_', '-', app()->getLocale()),
        ],
        [
            '@type' => 'FAQPage',
            'mainEntity' => collect(__('traits.faq'))->map(fn ($f) => [
                '@type' => 'Question',
                'name' => $f['q'],
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f['a']],
            ])->all(),
        ],
        [
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => __('ui.home'), 'item' => route('home')],
                ['@type' => 'ListItem', 'position' => 2, 'name' => __('traits.title'), 'item' => route('trait-test.show')],
            ],
        ],
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
</script>
@endsection

@section('content')
<div class="container tt-page">
    <div class="tt-main">
        <header class="tt-head">
            <h1>{{ __('traits.title') }}</h1>
            <p class="tt-tagline">{{ __('traits.tagline') }}</p>
            <p class="tt-intro">{{ __('traits.intro') }}</p>

            {{-- 封面。一進來就攤開 30 題會勸退,先給一個「開始」的緩衝。
                 題目仍然在 HTML 裡(SEO 與沒有 JS 的情況都要拿得到),
                 只是預設收起來。 --}}
            <div class="tt-facts">
                <span>{{ __('traits.facts.count', ['n' => count($questions)]) }}</span>
                <span>{{ __('traits.facts.time') }}</span>
                <span>{{ __('traits.facts.free') }}</span>
            </div>
            <button type="button" class="btn btn-gold btn-xl tt-start" id="tt-start">{{ __('traits.start') }}</button>
        </header>

        <form action="{{ route('trait-test.submit') }}" method="POST" id="tt-form" class="tt-collapsed">
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
                    <div class="tt-scale">
                        @foreach($scale as $i => $label)
                        <input type="radio" name="a[{{ $q['n'] }}]" id="a{{ $q['n'] }}_{{ $i }}" value="{{ $i - 2 }}"
                               {{ old('a.'.$q['n']) !== null && (int) old('a.'.$q['n']) === $i - 2 ? 'checked' : '' }}>
                        <label for="a{{ $q['n'] }}_{{ $i }}"><span class="tt-dot"></span>{{ $label }}</label>
                        @endforeach
                    </div>
                </fieldset>

            @endforeach

            @error('a')<p class="tt-error">{{ $message }}</p>@enderror

            <div class="tt-actions">
                <button type="submit" class="btn btn-gold btn-xl" id="tt-submit">{{ __('traits.submit') }}</button>
            </div>
        </form>

        {{-- 這一頁只留這一個內文版位,而且放在交卷按鈕之後 —— 作答到一半被
             廣告打斷是最傷的,主角是測驗本身。桌機另外有右側欄。 --}}
        @include('partials.ad-unit', ['zone' => 'home_banner'])

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
    start.addEventListener('click', function () {
        form.classList.remove('tt-collapsed');
        start.hidden = true;
        form.querySelector('.tt-q').scrollIntoView({behavior: 'smooth', block: 'start'});
    });

    // 重載後帶著舊作答回來(驗證失敗)的話,直接展開,不要再擋一次
    if (form.querySelector('.tt-q input:checked')) {
        form.classList.remove('tt-collapsed');
        start.hidden = true;
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
        msg.textContent = @json(__('traits.unanswered', ['n' => '__N__']))
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
