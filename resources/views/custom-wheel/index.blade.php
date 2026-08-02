@extends('layouts.app')

@section('title', __('minigame.cw_seo_title') . ' — ' . __('ui.site_name'))
@section('meta_description', __('minigame.cw_seo_description'))
@section('og_title', __('minigame.cw_seo_title'))
@section('og_description', __('minigame.cw_seo_description'))
@section('canonical', route('custom-wheel.page'))

@section('styles')
<link rel="stylesheet" href="{{ asset_v('css/minigames.css') }}">
@endsection

@section('schema')
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@graph' => [
        [
            '@type' => 'WebApplication',
            'name' => __('minigame.cw_title'),
            'description' => __('minigame.cw_seo_description'),
            'url' => route('custom-wheel.page'),
            'applicationCategory' => 'GameApplication',
            'operatingSystem' => 'Web',
            'inLanguage' => str_replace('_', '-', app()->getLocale()),
            'offers' => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'USD'],
        ],
        [
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => __('ui.home'), 'item' => route('home')],
                ['@type' => 'ListItem', 'position' => 2, 'name' => __('games.wheel_game'), 'item' => route('wheel-game.show')],
                ['@type' => 'ListItem', 'position' => 3, 'name' => __('minigame.cw_title'), 'item' => route('custom-wheel.page')],
            ],
        ],
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
</script>
@endsection

@section('content')
<div class="container cw-page">
    @include('partials.custom-wheel')

    @include('partials.ad-unit', ['zone' => 'home_banner'])

    {{-- 回到命運轉盤。兩個工具是同一組,拆開之後要互相連得回去,
         不然使用者從搜尋進到這一頁就走不到遊戲本身。 --}}
    <p class="cw-back">
        <a href="{{ route('wheel-game.show') }}">← {{ __('minigame.cw_back_to_wheel') }}</a>
    </p>
</div>
@endsection
