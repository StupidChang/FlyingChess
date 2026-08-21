{{--
    象限定位圖 —— 四條光譜兩張圖。

    為什麼不用雷達圖:這四條軸都是**雙極**的(S ↔ M、感官 ↔ 心動)。雷達圖的語意是
    「離圓心越遠 = 越多」,把雙極資料塞進去,中間值會被讀成「中等程度」,但它實際的
    意思是「兩邊都有」—— 那是完全不同的結論。象限圖沒有這個問題:位置就是位置。

    純 SVG、伺服器端算好座標,沒有 JS —— 關掉 JS 或爬蟲進來都看得到,而顏色走 CSS
    變數所以跟著主題換。

    需要 $result['axes'] 與 $axes(光譜名稱),兩者都由結果頁傳進來。
--}}
@php
    $scale = \App\Services\TraitTestService::AXIS_SCALE;

    /* 光譜值 -8…+8 → 0…100 的位置。正值是 config 裡的第一個標籤(left),
       所以正值要落在左邊／上面 —— 和結果頁的長條方向一致。 */
    $pos = fn (int $v) => round(($scale - max(-$scale, min($scale, $v))) / ($scale * 2) * 100, 1);

    // 偏一邊多少(0 = 正中間,100 = 完全偏一邊),給圖下面那句話用
    $lean = fn (int $v) => (int) round(abs($v) / $scale * 100);

    $maps = [['DS', 'OR'], ['PE', 'IG']];
@endphp

<div class="tt-maps">
    @foreach($maps as [$xId, $yId])
        @php
            $xv = (int) ($result['axes'][$xId] ?? 0);
            $yv = (int) ($result['axes'][$yId] ?? 0);
            $x = $pos($xv);
            $y = $pos($yv);
            $xName = $xv >= 0 ? ($axes[$xId]['left'] ?? '') : ($axes[$xId]['right'] ?? '');
            $yName = $yv >= 0 ? ($axes[$yId]['left'] ?? '') : ($axes[$yId]['right'] ?? '');
        @endphp
        <figure class="tt-map">
            <div class="tt-map-grid">
                <span class="tt-map-lbl is-top">{{ $axes[$yId]['left'] ?? '' }}</span>
                <span class="tt-map-lbl is-left">{{ $axes[$xId]['left'] ?? '' }}</span>
                <span class="tt-map-lbl is-right">{{ $axes[$xId]['right'] ?? '' }}</span>
                <span class="tt-map-lbl is-bottom">{{ $axes[$yId]['right'] ?? '' }}</span>

                <svg class="tt-map-svg" viewBox="0 0 100 100" role="img"
                     aria-label="{{ $axes[$xId]['note'] ?? $xId }}／{{ $axes[$yId]['note'] ?? $yId }}">
                    <rect class="tt-map-bg" x="0" y="0" width="100" height="100" rx="4"/>
                    {{-- 四分之一線比滿格網格好讀:只要看得出「偏哪一邊、偏多少」 --}}
                    <line class="tt-map-q" x1="25" y1="0" x2="25" y2="100"/>
                    <line class="tt-map-q" x1="75" y1="0" x2="75" y2="100"/>
                    <line class="tt-map-q" x1="0" y1="25" x2="100" y2="25"/>
                    <line class="tt-map-q" x1="0" y1="75" x2="100" y2="75"/>
                    <line class="tt-map-axis" x1="50" y1="0" x2="50" y2="100"/>
                    <line class="tt-map-axis" x1="0" y1="50" x2="100" y2="50"/>
                    {{-- 從中心到落點的一條線:讓「偏離中間多少」變成看得到的長度 --}}
                    <line class="tt-map-ray" x1="50" y1="50" x2="{{ $x }}" y2="{{ $y }}"/>
                    <circle class="tt-map-halo" cx="{{ $x }}" cy="{{ $y }}" r="7"/>
                    <circle class="tt-map-dot" cx="{{ $x }}" cy="{{ $y }}" r="3.4"/>
                </svg>
            </div>
            <figcaption class="tt-map-cap">
                <b>{{ $yName }}</b> {{ $lean($yv) }}% ·
                <b>{{ $xName }}</b> {{ $lean($xv) }}%
            </figcaption>
        </figure>
    @endforeach
</div>
