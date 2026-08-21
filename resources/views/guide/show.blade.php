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
/* ── 玩法指南的排版 ──
   之前整篇是同一個白、段落之間只有 10px,結果一整頁看起來是一塊 —— 從搜尋
   進來的人掃兩秒就跳掉。這一組的重點全在「層級」:標題最亮、內文次亮、
   輔助文字最暗,再靠圖示與編號給眼睛落點。

   顏色只用 --accent(玫瑰)當編輯用的強調色。--gold 在這個站是付費/金錢專用
   (見 CLAUDE.md 的設計代幣),文章內容不該借用它,不然「金色 = 要付錢」
   這個訊號會被稀釋。 */
.gd-page{--gd-body:#c5cad8;max-width:900px;margin:0 auto;padding:36px 20px 72px}
[data-theme="light"] .gd-page{--gd-body:#3f4658}
.gd-crumb{font-size:.8rem;color:var(--text-dim);margin-bottom:14px}
.gd-crumb a{color:var(--text-dim);text-decoration:underline;text-underline-offset:3px}
.gd-crumb a:hover{color:var(--accent)}
.gd-page h1{font-size:clamp(1.55rem,4.2vw,2.1rem);font-weight:800;letter-spacing:-.02em;
  line-height:1.35;margin-bottom:10px;text-wrap:balance}
.gd-meta{font-size:.78rem;color:var(--text-dim);margin-bottom:22px}

/* 導言。比內文大一級、左邊一條主色 —— 一眼看得出「這一段是總結」。 */
.gd-lead{font-size:clamp(1.02rem,.98rem + .3vw,1.2rem);line-height:1.95;color:var(--text);
  margin:0 0 30px;padding:2px 0 2px 18px;border-left:3px solid var(--accent)}

/* 目錄。長文沒有目錄的話,從搜尋進來的人看不出這一頁有沒有他要的東西就跳掉了。 */
.gd-toc{background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:16px 20px;margin-bottom:40px}
.gd-toc-title{font-size:.8rem;color:var(--accent);font-weight:700;letter-spacing:.04em;margin-bottom:10px}
.gd-toc ol{margin:0;padding-left:0;list-style:none;counter-reset:gdtoc}
.gd-toc li{counter-increment:gdtoc;margin:6px 0;font-size:.9rem;line-height:1.6;
  display:flex;gap:10px;align-items:baseline}
.gd-toc li::before{content:counter(gdtoc,decimal-leading-zero);color:var(--accent);
  font-size:.74rem;font-weight:700;font-variant-numeric:tabular-nums;flex:0 0 auto}
.gd-toc a{color:var(--text-dim)}
.gd-toc a:hover{color:var(--accent)}

/* 段落之間拉開,並用一條細線收尾 —— 捲動的時候看得出上一節結束了。 */
.gd-section{margin:0 0 14px;padding-bottom:26px;border-bottom:1px solid var(--border);scroll-margin-top:80px}
.gd-section:last-of-type{border-bottom:0}
.gd-section h2{display:flex;align-items:center;gap:11px;font-size:clamp(1.14rem,1.06rem + .3vw,1.32rem);
  font-weight:800;line-height:1.45;margin:30px 0 15px}
.gd-h2-mark{flex:0 0 auto;display:grid;place-items:center;width:32px;height:32px;border-radius:9px;
  color:var(--accent);background:color-mix(in srgb, var(--accent) 14%, transparent);
  border:1px solid color-mix(in srgb, var(--accent) 30%, transparent)}
.gd-icon{width:18px;height:18px;display:block}
.gd-h2-text{flex:1 1 auto;text-wrap:balance}
.gd-h2-num{flex:0 0 auto;font-size:.72rem;font-weight:700;color:var(--text-dim);
  font-variant-numeric:tabular-nums;opacity:.55}

/* 內文比標題暗一階。全部同一個白的時候,標題等於沒有存在感。 */
/* 字級跟著版面一起長。中文一行超過大約 50 字就會開始跳行 —— 版面加寬之後
   不把字級一起帶上去,每行的字數會爆掉,反而更難讀。 */
.gd-section p{font-size:clamp(.96rem,.92rem + .3vw,1.12rem);line-height:2;
  margin:0 0 16px;color:var(--gd-body)}
.gd-section p strong,.gd-lead strong{color:var(--text);font-weight:700;
  background:linear-gradient(transparent 62%, color-mix(in srgb, var(--accent) 26%, transparent) 62%)}

/* 清單。自訂圓點,而且第一層縮排跟內文對齊。 */
.gd-list{margin:16px 0;padding-left:2px;list-style:none}
.gd-list li{position:relative;padding-left:22px;font-size:clamp(.95rem,.91rem + .28vw,1.1rem);
  line-height:1.95;margin-bottom:13px;color:var(--gd-body)}
.gd-list li::before{content:'';position:absolute;left:4px;top:.72em;width:6px;height:6px;
  border-radius:50%;background:var(--accent);opacity:.8}
.gd-list strong{color:var(--text);font-weight:700}

/* 重點框。一節裡最該被記住的那一句 —— 掃頁的人只讀這些也拿得到重點。 */
.gd-section p.gd-note{margin:18px 0 4px;padding:14px 17px;border-radius:10px;
  background:color-mix(in srgb, var(--accent) 9%, var(--surface));
  border:1px solid color-mix(in srgb, var(--accent) 26%, transparent);
  color:var(--text);font-size:clamp(.94rem,.9rem + .25vw,1.07rem);line-height:1.9}
.gd-note-tag{font-size:.72rem;font-weight:700;letter-spacing:.06em;color:var(--accent);
  margin-right:6px;vertical-align:1px}

/* 段落中的內部連結。做成一張小卡而不是一句話裡的連結 —— 讀者掃過長文的時候
   看得見,但又不會打斷正在讀的那個段落。金色在這裡是刻意的:它連到的是站內
   可以馬上玩的東西,和文字內容不同層。 */
.gd-cta{display:block;margin:18px 0 4px;padding:12px 14px;border:1px solid var(--border);
  border-left:3px solid var(--gold);border-radius:10px;background:var(--bg);
  font-size:.88rem;line-height:1.7;color:var(--text);transition:border-color .14s,transform .14s}
.gd-cta:hover{border-color:var(--gold);transform:translateY(-1px)}
.gd-cta::after{content:' →';color:var(--gold)}

.gd-faq{margin-top:44px}
.gd-faq h2{font-size:1.16rem;font-weight:800;margin-bottom:14px}
.gd-faq-item{border-bottom:1px solid var(--border);padding:14px 0}
.gd-faq-item summary{cursor:pointer;font-weight:700;font-size:clamp(.95rem,.91rem + .2vw,1.03rem);line-height:1.6;
  display:flex;gap:9px;align-items:baseline;list-style:none}
.gd-faq-item summary::-webkit-details-marker{display:none}
.gd-faq-item summary::before{content:'Q';flex:0 0 auto;font-size:.76rem;color:var(--accent);font-weight:800}
.gd-faq-item[open] summary{color:var(--accent)}
.gd-faq-item p{margin:10px 0 0 19px;line-height:1.95;color:var(--gd-body);
  font-size:clamp(.92rem,.88rem + .2vw,1rem)}

.gd-related{margin-top:44px;border:1px solid var(--border);border-radius:12px;background:var(--surface);padding:18px 20px}
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

@media (max-width:480px){
  .gd-section h2{font-size:1.08rem;gap:9px}
  .gd-h2-mark{width:28px;height:28px}
  .gd-icon{width:16px;height:16px}
  .gd-h2-num{display:none}
}
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

    {{-- 導言與段落也吃 **粗體**:一段五六行的中文,沒有任何視覺落點會整段被跳過。
         inline_emphasis 先 escape 再只還原 <strong>,所以不會有 XSS ——
         見 app/Support/helpers.php。 --}}
    <p class="gd-lead">{!! inline_emphasis($article['lead']) !!}</p>

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
        {{-- 圖示 + 編號 + 標題。長文全白字的時候,讀者掃不出段落在哪裡結束 ——
             這一排的功能是給眼睛一個落點,不是裝飾。圖示是自己畫的 inline SVG
             (見 partials/guide-icon),沒有外部圖檔也沒有授權問題。 --}}
        <h2>
            <span class="gd-h2-mark">@include('partials.guide-icon', ['icon' => $s['icon'] ?? 'dot'])</span>
            <span class="gd-h2-text">{{ $s['h2'] }}</span>
            <span class="gd-h2-num" aria-hidden="true">{{ str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) }}</span>
        </h2>

        @foreach($s['p'] ?? [] as $para)
        <p>{!! inline_emphasis($para) !!}</p>
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
        <p>{!! inline_emphasis($para) !!}</p>
        @endforeach

        {{-- 這一節最該被記住的一句。文案端寫 'note' => '…',沒寫就不出現。 --}}
        @if(! empty($s['note']))
        <p class="gd-note"><b class="gd-note-tag">{{ __('guides.note_tag') }}</b>{!! inline_emphasis($s['note']) !!}</p>
        @endif

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
