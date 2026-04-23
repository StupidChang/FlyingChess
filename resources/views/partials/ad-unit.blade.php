{{--
  廣告版位元件 — 支援 TrafficJunky 與 Google AdSense
  優先順序：TrafficJunky → AdSense → 不顯示

  用法：
    @include('partials.ad-unit', ['zone' => 'home_top'])
    @include('partials.ad-unit', ['zone' => 'home_mid'])
    @include('partials.ad-unit', ['zone' => 'play'])
--}}
@php
  $tjSiteId  = config('services.trafficjunky.site_id');
  $tjSpot    = config("services.trafficjunky.spot_{$zone}");
  $asPubId   = config('services.adsense.publisher_id');
  $asSlot    = config("services.adsense.slot_{$zone}");
  $useTJ     = $tjSiteId && $tjSpot;
  $useAS     = !$useTJ && $asPubId && $asSlot;
@endphp

@if($useTJ)
{{-- ── TrafficJunky 廣告 ── --}}
<div class="ad-unit" aria-label="廣告" data-network="tj">
  <script type="text/javascript">
    var _TJWIDGET = { site_id: "{{ $tjSiteId }}", spot_id: "{{ $tjSpot }}" };
  </script>
  <script async src="//ads.trafficjunky.net/ads/player.js"></script>
</div>

@elseif($useAS)
{{-- ── Google AdSense 廣告 ── --}}
<div class="ad-unit" aria-label="廣告" data-network="adsense">
  <ins class="adsbygoogle"
       style="display:block"
       data-ad-client="{{ $asPubId }}"
       data-ad-slot="{{ $asSlot }}"
       data-ad-format="auto"
       data-full-width-responsive="true"></ins>
  <script>(adsbygoogle = window.adsbygoogle || []).push({});</script>
</div>
@endif
