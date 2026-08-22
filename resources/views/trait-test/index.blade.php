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
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}
</script>
@endsection

@section('content')
<div class="container tt-page">
    <div class="tt-main">
        <header class="tt-head">
            <h1>{{ __('traits.title') }}</h1>
            <p class="tt-tagline">{{ __('traits.tagline') }}</p>
            {{-- 一句重點 + 三個要點。原本是一整段一百多字的文字牆,畫面上沒有任何
                 落點,掃頁的人抓不到「這測驗在測什麼」。 --}}
            {{-- is-oneline:這一句要求不換行。句子長度是它自己的責任 —— 加長到放不下就會
                 衝出卡片,所以改文案時要一起量(見 app.css 的 .tt-lead.is-oneline)。 --}}
            <p class="tt-lead is-oneline">{!! inline_emphasis(__('traits.intro_lead')) !!}</p>
            <dl class="tt-points">
                @foreach(__('traits.intro_points') as $point)
                <div class="tt-point">
                    <dt>{{ $point['k'] }}</dt>
                    <dd>{{ $point['v'] }}</dd>
                </div>
                @endforeach
            </dl>

            {{-- 封面。一進來就攤開 30 題會勸退,先給一個「開始」的緩衝。
                 題目仍然在 HTML 裡(SEO 與沒有 JS 的情況都要拿得到),
                 只是預設收起來。 --}}
            <div class="tt-facts">
                <span>{{ __('traits.facts.count', ['n' => count($questions)]) }}</span>
                <span>{{ __('traits.facts.free') }}</span>
            </div>
            <button type="button" class="btn btn-primary btn-xl tt-start" id="tt-start">{{ __('traits.start') }}</button>
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
                    {{-- 同意在左、不同意在右:反轉顯示順序,但 $i 仍是原本的索引,
                         所以分數(value = $i-2)與顏色(tt-opt-$i)都不變、不會算錯。 --}}
                    <div class="tt-scale">
                        @foreach(array_reverse($scale, true) as $i => $label)
                        <input type="radio" name="a[{{ $q['n'] }}]" id="a{{ $q['n'] }}_{{ $i }}" value="{{ $i - 2 }}"
                               {{ old('a.'.$q['n']) !== null && (int) old('a.'.$q['n']) === $i - 2 ? 'checked' : '' }}>
                        <label for="a{{ $q['n'] }}_{{ $i }}" class="tt-opt tt-opt-{{ $i }}"><span class="tt-dot"></span>{{ $label }}</label>
                        @endforeach
                    </div>
                </fieldset>

            @endforeach

            @error('a')<p class="tt-error">{{ $message }}</p>@enderror

            <div class="tt-actions">
                <button type="submit" class="btn btn-primary btn-xl" id="tt-submit">{{ __('traits.submit') }}</button>
            </div>
        </form>

        {{-- 20 個屬性頁的入口。這一頁以前完全沒有連到任何結果頁,那 20 頁只能從
             「別的結果頁」走到 —— 也就是離首頁 4 層。新網域的爬取預算很少,4 層深
             的頁面很可能永遠不會被抓。這個測驗的 SEO 價值就是那 20 頁,入口要放在
             它們的母頁上。 --}}
        <section class="tt-card">
            {{-- 20 個名字一次全攤出來,對還沒作答的人只是一牆詞 —— 他還不知道自己
                 是哪幾種,看名字也選不了。所以預設收起來。
                 用 <details> 而不是 JS:連結仍然完整留在 HTML 裡(收起來只是
                 display:none),爬蟲照樣抓得到 —— 這一頁的 SEO 價值就是通往那 20 頁的
                 入口,不能為了畫面乾淨把它們拿掉。 --}}
            <details class="tt-all-fold">
                <summary>
                    <span>{{ __('traits.result.all_traits') }}</span>
                    <em>{{ __('traits.all_traits_hint', ['n' => count($items)]) }}</em>
                </summary>
                <div class="tt-all">
                    @foreach($items as $k => $item)
                    <a href="{{ route('trait-test.result', ['slug' => $item['slug']]) }}"
                       class="tt-chip tt-c-{{ config('traits.traits.'.$k.'.colour', 'gold') }}">{{ $item['name'] }}</a>
                    @endforeach
                </div>
            </details>

            {{-- 兩人對照的入口。那一頁是 noindex 的工具頁,所以只從站內連進去。 --}}
            <p class="tt-cmp-cta">
                <a class="btn btn-outline" href="{{ route('trait-test.compare') }}">{{ __('traits.compare.title') }}</a>
            </p>
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

        {{-- 這一頁只留這一個內文版位,而且放到最後面。原本放在交卷按鈕之後,
             看起來很低 —— 但題目一開始是收起來的(.tt-collapsed),所以「交卷按鈕
             之後」在還沒作答的人眼裡幾乎就在開始測驗那顆鈕底下。放在 FAQ 之後,
             不管題目收著或攤開都在整頁的尾巴。桌機另外有右側欄。 --}}
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
