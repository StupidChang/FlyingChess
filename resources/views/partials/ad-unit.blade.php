{{--
  廣告版位元件 — adapter 模式
  用法: @include('partials.ad-unit', ['zone' => 'home_banner'])
  Zones: home_banner, home_mid, lobby_side, game_end, share
  Adapter 由 config('ads.adapter') 決定：exoclick / trafficjunky / adsense
--}}
@php
    // Premium users: no ads
    $showAds = !auth()->check() || !auth()->user()?->isPremium();

    $adapter = config('ads.adapter', 'exoclick');
    $hasEC = false;
    $hasTJ = false;
    $hasAS = false;

    if ($showAds) {
        if ($adapter === 'exoclick') {
            $zoneId = config("ads.exoclick.zone_{$zone}");
            $hasEC = (bool) $zoneId;
        } elseif ($adapter === 'trafficjunky') {
            $siteId = config('ads.trafficjunky.site_id');
            $spotId = config("ads.trafficjunky.spot_{$zone}");
            $hasTJ = $siteId && $spotId;
        }

        $pubId = config('ads.adsense.publisher_id');
        $slotId = config("ads.adsense.slot_{$zone}");
        $hasAS = $adapter === 'adsense' && $pubId && $slotId;
    }
@endphp

@if($showAds && $hasEC)
<div class="ad-unit ad-unit--banner" aria-label="{{ __('ui.ad_label') }}" data-zone="{{ $zone }}">
    <script async src="https://a.magsrv.com/ad-provider.js"></script>
    <ins class="eas6a97888e2" data-zoneid="{{ $zoneId }}"></ins>
    <script>(AdProvider = window.AdProvider || []).push({"serve": {}});</script>
</div>
@elseif($showAds && $hasTJ)
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
