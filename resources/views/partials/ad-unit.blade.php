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
            // 寬螢幕專用 zone（選配）。ExoClick 的 zone 尺寸是固定的,滿版容器
            // 用 300x250 會兩側大片留白,所以桌機另開一個大尺寸 zone。
            $zoneIdWide = config("ads.exoclick.zone_{$zone}_desktop");
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
    {{-- 尺寸的挑選與投放都交給 exoServe(見 public/js/ads.js)。收合式版位要等
         展開才投放,一般版位載入就投,差別只在呼叫時機 —— 邏輯只留一份。 --}}
    <div class="ad-slot" data-zoneid-narrow="{{ $zoneId }}"
         @if($zoneIdWide) data-zoneid-wide="{{ $zoneIdWide }}" @endif></div>
    <script>exoServe(document.currentScript.previousElementSibling);</script>
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

{{-- 這裡原本有一條「不想看廣告?升級 Premium」的連結。拿掉了:每個廣告下面都掛
     一句招攬,五個版位加起來變成整站在碎念,而 /premium 在導覽列本來就有入口。
     語系檔的 ui.ad_remove 與 .ad-upsell 的樣式先留著,要復原只要把這段補回來。 --}}
