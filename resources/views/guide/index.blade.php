@extends('layouts.app')

@section('title', __('guides.index_seo_title') . ' — ' . __('ui.site_name'))
@section('meta_description', __('guides.index_seo_description'))
@section('og_title', __('guides.index_seo_title'))
@section('og_description', __('guides.index_seo_description'))
@section('canonical', route('guide.index'))
@section('robots', $translated ? 'index,follow' : 'noindex,follow')

@section('schema')
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@graph' => [
        [
            '@type' => 'CollectionPage',
            'name' => __('guides.index_seo_title'),
            'description' => __('guides.index_seo_description'),
            'url' => route('guide.index'),
            'inLanguage' => str_replace('_', '-', app()->getLocale()),
        ],
        [
            '@type' => 'ItemList',
            'itemListElement' => collect($articles)->values()->map(fn ($a, $i) => [
                '@type' => 'ListItem',
                'position' => $i + 1,
                'name' => $a['h1'],
                'url' => route('guide.show', ['slug' => $a['slug']]),
            ])->all(),
        ],
        [
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => __('ui.home'), 'item' => route('home')],
                ['@type' => 'ListItem', 'position' => 2, 'name' => __('guides.index_h1'), 'item' => route('guide.index')],
            ],
        ],
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}
</script>
@endsection

@section('styles')
<style>
.gdx-page{max-width:760px;margin:0 auto;padding:40px 20px 72px}
.gdx-head{margin-bottom:30px}
.gdx-head h1{font-size:clamp(1.6rem,4.4vw,2.2rem);font-weight:800;letter-spacing:-.02em;margin-bottom:10px}
.gdx-lead{font-size:.98rem;line-height:1.9;color:var(--text-dim);max-width:58ch}
.gdx-list{display:grid;gap:14px}
.gdx-item{border:1px solid var(--border);border-radius:14px;background:var(--surface);
  padding:20px 22px;transition:border-color .14s,transform .14s}
.gdx-item:hover{border-color:var(--accent);transform:translateY(-2px)}
.gdx-item h2{font-size:1.05rem;line-height:1.5;margin-bottom:8px}
.gdx-item h2 a{color:var(--text)}
.gdx-item h2 a:hover{color:var(--accent)}
.gdx-item p{font-size:.88rem;line-height:1.8;color:var(--text-dim);margin-bottom:10px}
.gdx-more{font-size:.82rem;color:var(--gold)}
.gdx-date{font-size:.75rem;color:var(--text-dim);margin-left:10px}
</style>
@endsection

@section('content')
<div class="container gdx-page">
    <header class="gdx-head">
        <h1>{{ __('guides.index_h1') }}</h1>
        <p class="gdx-lead">{{ __('guides.index_lead') }}</p>
    </header>

    <div class="gdx-list">
        @foreach($articles as $a)
        <article class="gdx-item">
            {{-- 標題本身就是連結:列表頁通往文章的唯一路徑,錨文字就是文章標題,
                 這對搜尋引擎理解那一頁在講什麼是最直接的訊號。 --}}
            <h2><a href="{{ route('guide.show', ['slug' => $a['slug']]) }}">{{ $a['h1'] }}</a></h2>
            <p>{{ $a['lead'] }}</p>
            <a class="gdx-more" href="{{ route('guide.show', ['slug' => $a['slug']]) }}">{{ __('guides.read_more') }} →</a>
            @if($a['updated'])
            <span class="gdx-date">{{ __('guides.updated_at', ['date' => $a['updated']]) }}</span>
            @endif
        </article>
        @endforeach
    </div>

    @include('partials.ad-unit', ['zone' => 'home_banner'])
</div>
@endsection
