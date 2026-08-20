@extends('layouts.app')

@section('title', $article['seo_title'] . ' — ' . __('ui.site_name'))
@section('meta_description', $article['seo_description'])
@section('og_title', $article['seo_title'])
@section('og_description', $article['seo_description'])
@section('canonical', route('guide.show', ['slug' => $slug]))

{{-- 沒翻譯的語系標 noindex:中文內容配英文網址被收錄,對排名是扣分不是加分。
     翻好之後把語系加進 config/guides.php 的 translated。 --}}
@section('robots', $translated ? 'index,follow' : 'noindex,follow')

@section('schema')
<script type="application/ld+json">
{!! json_encode(array_values(array_filter([
    [
        '@context' => 'https://schema.org',
        '@type' => 'Article',
        'headline' => $article['h1'],
        'description' => $article['seo_description'],
        'url' => route('guide.show', ['slug' => $slug]),
        'inLanguage' => str_replace('_', '-', app()->getLocale()),
        'dateModified' => $updated,
        'author' => ['@type' => 'Organization', 'name' => __('ui.site_name')],
        'publisher' => ['@type' => 'Organization', 'name' => __('ui.site_name')],
    ],
    /* FAQPage 一定要和頁面上看得到的內容一致。宣告了頁面上沒有的問答是政策違規,
       而這兩半都讀同一個 lang 檔就是為了讓它們不可能走鐘。 */
    ! empty($article['faq']) ? [
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => collect($article['faq'])->map(fn ($f) => [
            '@type' => 'Question',
            'name' => $f['q'],
            'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f['a']],
        ])->all(),
    ] : null,
    [
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => __('ui.home'), 'item' => route('home')],
            ['@type' => 'ListItem', 'position' => 2, 'name' => __('guides.index_h1'), 'item' => route('guide.index')],
            ['@type' => 'ListItem', 'position' => 3, 'name' => $article['h1'], 'item' => route('guide.show', ['slug' => $slug])],
        ],
    ],
])), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}
</script>
@endsection

@section('styles')
<style>
.gd-page{max-width:760px;margin:0 auto;padding:36px 20px 72px}
.gd-crumb{font-size:.8rem;color:var(--text-dim);margin-bottom:14px}
.gd-crumb a{color:var(--text-dim);text-decoration:underline;text-underline-offset:3px}
.gd-crumb a:hover{color:var(--accent)}
.gd-page h1{font-size:clamp(1.55rem,4.2vw,2.1rem);font-weight:800;letter-spacing:-.02em;
  line-height:1.35;margin-bottom:10px;text-wrap:balance}
.gd-meta{font-size:.78rem;color:var(--text-dim);margin-bottom:22px}
.gd-lead{font-size:1rem;line-height:1.9;color:var(--text);margin-bottom:28px}

/* 目錄。長文沒有目錄的話,從搜尋進來的人看不出這一頁有沒有他要的東西就跳掉了。 */
.gd-toc{background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:16px 20px;margin-bottom:34px}
.gd-toc-title{font-size:.85rem;color:var(--gold);font-weight:700;margin-bottom:8px}
.gd-toc ol{margin:0;padding-left:20px}
.gd-toc li{margin:5px 0;font-size:.9rem}
.gd-toc a{color:var(--text)}
.gd-toc a:hover{color:var(--accent)}

.gd-section{margin-bottom:34px;scroll-margin-top:80px}
.gd-section h2{font-size:1.12rem;margin-bottom:12px;border-left:3px solid var(--accent);padding-left:10px}
.gd-section p{line-height:1.9;margin-bottom:10px}
.gd-list{margin:12px 0;padding-left:20px;list-style:disc}
.gd-list li{line-height:1.85;margin-bottom:9px}
.gd-list strong{color:var(--text)}

/* 段落中的內部連結。做成一張小卡而不是一句話裡的連結 —— 讀者掃過長文的時候
   看得見,但又不會打斷正在讀的那個段落。 */
.gd-cta{display:block;margin:16px 0 4px;padding:12px 14px;border:1px solid var(--border);
  border-left:3px solid var(--gold);border-radius:10px;background:var(--bg);
  font-size:.88rem;line-height:1.7;color:var(--text);transition:border-color .14s,transform .14s}
.gd-cta:hover{border-color:var(--gold);transform:translateY(-1px)}
.gd-cta::after{content:' →';color:var(--gold)}

.gd-faq{margin-top:40px}
.gd-faq h2{font-size:1.12rem;margin-bottom:12px}
.gd-faq-item{border-bottom:1px solid var(--border);padding:12px 0}
.gd-faq-item summary{cursor:pointer;font-weight:600;font-size:.94rem}
.gd-faq-item p{margin-top:8px;line-height:1.85;color:var(--text-dim);font-size:.9rem}

.gd-related{margin-top:40px;border:1px solid var(--border);border-radius:12px;background:var(--surface);padding:18px 20px}
.gd-related h2{font-size:1rem;margin-bottom:4px}
.gd-related-hint{font-size:.8rem;color:var(--text-dim);margin-bottom:14px}
.gd-chips{display:flex;flex-wrap:wrap;gap:8px}
.gd-chip{padding:8px 14px;border:1px solid var(--border);border-radius:99px;background:var(--bg);
  font-size:.85rem;color:var(--text);transition:border-color .14s,transform .14s}
.gd-chip:hover{border-color:var(--accent);transform:translateY(-1px)}

.gd-others{margin-top:34px}
.gd-others h2{font-size:1rem;margin-bottom:12px}
.gd-others ul{list-style:none;margin:0;padding:0;display:grid;gap:8px}
.gd-others a{display:block;padding:12px 14px;border:1px solid var(--border);border-radius:10px;
  background:var(--surface);font-size:.9rem;line-height:1.6;color:var(--text);transition:border-color .14s,transform .14s}
.gd-others a:hover{border-color:var(--accent);transform:translateY(-1px)}
</style>
@endsection

@section('content')
<article class="container gd-page">
    <nav class="gd-crumb" aria-label="breadcrumb">
        <a href="{{ route('home') }}">{{ __('ui.home') }}</a> ›
        <a href="{{ route('guide.index') }}">{{ __('guides.index_h1') }}</a>
    </nav>

    <h1>{{ $article['h1'] }}</h1>
    @if($updated)
    <p class="gd-meta">{{ __('guides.updated_at', ['date' => $updated]) }}</p>
    @endif

    <p class="gd-lead">{{ $article['lead'] }}</p>

    <nav class="gd-toc" aria-label="{{ __('guides.toc_title') }}">
        <p class="gd-toc-title">{{ __('guides.toc_title') }}</p>
        <ol>
            @foreach($article['sections'] as $i => $s)
            <li><a href="#s{{ $i }}">{{ $s['h2'] }}</a></li>
            @endforeach
        </ol>
    </nav>

    @foreach($article['sections'] as $i => $s)
    <section class="gd-section" id="s{{ $i }}">
        <h2>{{ $s['h2'] }}</h2>

        @foreach($s['p'] ?? [] as $para)
        <p>{{ $para }}</p>
        @endforeach

        @if(! empty($s['ul']))
        <ul class="gd-list">
            @foreach($s['ul'] as $li)
            {{-- inline_emphasis 會先 escape 再只還原 **粗體**,永遠只可能產出
                 <strong> —— 見 app/Support/helpers.php 的說明。 --}}
            <li>{!! inline_emphasis($li) !!}</li>
            @endforeach
        </ul>
        @endif

        {{-- p2:列表之後的收尾段落。分開一個 key 是為了讓「段落→清單→結論」
             這個順序在文案裡就固定下來,不用在 view 裡判斷。 --}}
        @foreach($s['p2'] ?? [] as $para)
        <p>{{ $para }}</p>
        @endforeach

        @if(! empty($s['cta']) && \Illuminate\Support\Facades\Route::has($s['cta']['route']))
        <a class="gd-cta" href="{{ route($s['cta']['route']) }}">{{ $s['cta']['text'] }}</a>
        @endif
    </section>
    @endforeach

    @if(! empty($article['faq']))
    <section class="gd-faq">
        <h2>{{ __('guides.faq_title') }}</h2>
        @foreach($article['faq'] as $f)
        <details class="gd-faq-item">
            <summary>{{ $f['q'] }}</summary>
            <p>{{ $f['a'] }}</p>
        </details>
        @endforeach
    </section>
    @endif

    @include('partials.ad-unit', ['zone' => 'home_banner'])

    @if($related)
    <section class="gd-related">
        <h2>{{ __('guides.related_title') }}</h2>
        <p class="gd-related-hint">{{ __('guides.related_hint') }}</p>
        <div class="gd-chips">
            @foreach($related as $r)
            <a class="gd-chip" href="{{ $r['url'] }}">{{ $r['label'] }}</a>
            @endforeach
        </div>
    </section>
    @endif

    @if($others)
    <section class="gd-others">
        <h2>{{ __('guides.index_h1') }}</h2>
        <ul>
            @foreach($others as $o)
            <li><a href="{{ route('guide.show', ['slug' => $o['slug']]) }}">{{ $o['h1'] }}</a></li>
            @endforeach
        </ul>
    </section>
    @endif

    <p class="gd-meta" style="margin-top:28px">
        <a href="{{ route('guide.index') }}">{{ __('guides.back_to_index') }}</a>
    </p>
</article>
@endsection
