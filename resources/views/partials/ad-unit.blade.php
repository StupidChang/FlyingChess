{{--
  廣告版位元件 — adapter 模式
  用法: @include('partials.ad-unit', ['zone' => 'home_banner'])
  Zones: home_banner, home_mid, lobby_side, game_end, share
--}}
@php
    // Premium users: no ads
    $showAds = !auth()->check() || !auth()->user()?->isPremium();

    $adapter = config('ads.adapter', 'adsense');
    $hasTJ = false;
    $hasAS = false;

    if ($showAds) {
        if ($adapter === 'trafficjunky') {
            $siteId = config('ads.trafficjunky.site_id');
            $spotId = config("ads.trafficjunky.spot_{$zone}");
            $hasTJ = $siteId && $spotId;
        }

        $pubId = config('ads.adsense.publisher_id');
        $slotId = config("ads.adsense.slot_{$zone}");
        $hasAS = $pubId && $slotId;
    }
@endphp

@if($showAds && $hasTJ)
<div class="ad-unit ad-unit--banner" aria-label="{{ __('ui.ad_label') }}" data-zone="{{ $zone }}">
    <script type="text/javascript">
        var _TJWIDGET = { site_id: "{{ $siteId }}", spot_id: "{{ $spotId }}" };
    </script>
    <script async src="//ads.trafficjunky.net/ads/player.js"></script>
</div>
@elseif($showAds && $hasAS)
<div class="ad-unit ad-unit--banner" aria-label="{{ __('ui.ad_label') }}" data-zone="{{ $zone }}">
    <ins class="adsbygoogle"
         style="display:block"
         data-ad-client="{{ $pubId }}"
         data-ad-slot="{{ $slotId }}"
         data-ad-format="auto"
         data-full-width-responsive="true"></ins>
    <script>(adsbygoogle = window.adsbygoogle || []).push({});</script>
</div>
@endif
