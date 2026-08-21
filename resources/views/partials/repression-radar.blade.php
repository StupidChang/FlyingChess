{{--
    五邊形雷達 —— 五個面向。

    這裡用雷達是對的:五個面向都是**同向**的 0–100(分數越高 = 那一項的煞車越明顯),
    沒有雙極的問題,而且五項一起看的「形狀」本身就是資訊 —— 全面偏高和只有一項突出,
    是兩種完全不同的狀態,長條圖要一條一條比才看得出來,雷達一眼就知道。

    順序**固定照 config**,不照分數排序 —— 排序過的雷達形狀不能跨人比較,那就失去
    雷達唯一的優勢了。

    純 SVG、伺服器端算好座標,沒有 JS。需要 $result['dimensions'] 與 $dimensions。
--}}
@php
    $order = array_keys((array) config('repression.dimensions'));
    $byKey = collect($result['dimensions'] ?? [])->keyBy('key');

    $cx = 100;
    $cy = 96;
    $r = 70;
    $n = max(1, count($order));

    // 從正上方開始,順時針。-90° 是 12 點鐘方向
    $point = function (int $i, float $ratio) use ($cx, $cy, $r, $n) {
        $a = -M_PI / 2 + ($i * 2 * M_PI / $n);
        return [
            round($cx + cos($a) * $r * $ratio, 2),
            round($cy + sin($a) * $r * $ratio, 2),
        ];
    };

    $rings = [0.25, 0.5, 0.75, 1.0];
    $shape = [];
    $marks = [];
    $labels = [];

    foreach ($order as $i => $key) {
        $pct = (int) ($byKey[$key]['pct'] ?? 0);
        // 0% 會讓多邊形塌到圓心、看不出有這一項,所以給一點最小半徑
        [$px, $py] = $point($i, max(0.04, $pct / 100));
        $shape[] = $px.','.$py;
        $marks[] = ['x' => $px, 'y' => $py, 'pct' => $pct];

        [$lx, $ly] = $point($i, 1.24);
        $labels[] = [
            'x' => $lx,
            'y' => $ly,
            'name' => $dimensions[$key]['name'] ?? $key,
            'pct' => $pct,
            // 左右兩側的字要往內對齊,不然會被裁掉
            'anchor' => $lx < $cx - 6 ? 'end' : ($lx > $cx + 6 ? 'start' : 'middle'),
        ];
    }
@endphp

<div class="rp-radar-wrap">
    {{-- viewBox 左右各留 32:三點鐘與九點鐘方向的標籤是 start/end 對齊,文字會往
         畫布外延伸,不留白就會被裁掉(尤其中文標籤四個字) --}}
    <svg class="rp-radar" viewBox="-32 -4 264 208" role="img"
         aria-label="{{ __('repression.result.dimensions_title') }}">
        @foreach($rings as $ring)
            @php
                $ringPts = [];
                foreach ($order as $i => $key) {
                    [$px, $py] = $point($i, $ring);
                    $ringPts[] = $px.','.$py;
                }
            @endphp
            <polygon class="rp-radar-ring {{ $ring === 1.0 ? 'is-outer' : '' }}"
                     points="{{ implode(' ', $ringPts) }}"/>
        @endforeach

        @foreach($order as $i => $key)
            @php [$sx, $sy] = $point($i, 1.0); @endphp
            <line class="rp-radar-spoke" x1="{{ $cx }}" y1="{{ $cy }}" x2="{{ $sx }}" y2="{{ $sy }}"/>
        @endforeach

        <polygon class="rp-radar-area" points="{{ implode(' ', $shape) }}"/>

        @foreach($marks as $m)
            <circle class="rp-radar-dot" cx="{{ $m['x'] }}" cy="{{ $m['y'] }}" r="2.6"/>
        @endforeach

        @foreach($labels as $l)
            <text class="rp-radar-name" x="{{ $l['x'] }}" y="{{ $l['y'] }}"
                  text-anchor="{{ $l['anchor'] }}">{{ $l['name'] }}</text>
            <text class="rp-radar-pct" x="{{ $l['x'] }}" y="{{ $l['y'] + 9 }}"
                  text-anchor="{{ $l['anchor'] }}">{{ $l['pct'] }}%</text>
        @endforeach
    </svg>
    <p class="tt-hint rp-radar-hint">{{ __('repression.result.radar_hint') }}</p>
</div>
