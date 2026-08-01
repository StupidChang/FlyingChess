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
    @if($zoneIdWide)
        {{-- 兩個尺寸擇一插入。刻意不用「兩個都輸出、CSS 藏掉一個」的作法:
             被 display:none 的那個一樣會載入並計曝光,可視率被拉低會壓低單價,
             聯播網也可能判定為無效流量。斷點 940px = 900px 素材 + 左右邊距,
             平板落到窄版,避免寬素材被 iframe 裁掉右半邊。
             只在載入時判斷一次 —— 事後改變視窗寬度不會重抓,重抓等於再計一次
             曝光,那比尺寸不完美更糟。 --}}
        <div class="ad-slot" data-zoneid-narrow="{{ $zoneId }}" data-zoneid-wide="{{ $zoneIdWide }}"></div>
        <script>
        (function () {
            var slot = document.currentScript.previousElementSibling;
            var ins = document.createElement('ins');
            ins.className = 'eas6a97888e2';
            ins.dataset.zoneid = window.matchMedia('(min-width: 940px)').matches
                ? slot.dataset.zoneidWide
                : slot.dataset.zoneidNarrow;
            slot.appendChild(ins);
            (AdProvider = window.AdProvider || []).push({"serve": {}});
        })();
        </script>
    @else
        <ins class="eas6a97888e2" data-zoneid="{{ $zoneId }}"></ins>
        <script>(AdProvider = window.AdProvider || []).push({"serve": {}});</script>
    @endif
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

@if($showAds && ($hasEC || $hasTJ || $hasAS))
    {{-- 廣告本身就是 Premium 的最佳說服時機:給一條低調的出口,不用彈窗。 --}}
    <div class="ad-upsell">
        <a href="{{ route('premium.index') }}">{{ __('ui.ad_remove') }}</a>
    </div>
@endif
