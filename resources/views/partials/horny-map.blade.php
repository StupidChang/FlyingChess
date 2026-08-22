{{-- 象限圖:橫軸色度(油門)、縱軸煞車。
     四個角直接標上象限的名字 —— 這張圖的價值不是「你偏多少」,是「你在哪一格,
     而旁邊那三格是什麼」。有分數的人多一個落點,沒分數的人看到的是這一格在
     整張圖上的位置(順便是通往另外四頁的內部連結)。

     需要:$quadrants(全部五個)、$key(目前這一格)、$axes、$result(可為 null) --}}
@php
    $desire = $result['axes']['desire'] ?? null;
    $brake = $result['axes']['brake'] ?? null;
    // SVG 的 y 是往下長的,煞車高要在上面,所以要翻過來
    /* 夾在 8–92:落點壓到邊角的話會蓋在那一角的象限名字上,而滿分 100 的人
       正好落在那裡。差幾個 pixel 換到「看得出是哪一角」。 */
    $x = $desire !== null ? max(8, min(92, $desire)) : null;
    $y = $brake !== null ? max(8, min(92, 100 - $brake)) : null;

    $byKey = collect($quadrants)->keyBy('key');
    $corner = fn ($d, $b) => collect($quadrants)->first(fn ($q) => $q['desire'] === $d && $q['brake'] === $b);
@endphp
<figure class="hm-fig">
    <div class="hm-grid">
        <span class="tt-map-lbl is-top">{{ $axes['brake']['name'] }}{{ $axes['brake']['high'] }}</span>
        <span class="tt-map-lbl is-bottom">{{ $axes['brake']['name'] }}{{ $axes['brake']['low'] }}</span>
        <span class="tt-map-lbl is-left">{{ $axes['desire']['name'] }}{{ $axes['desire']['low'] }}</span>
        <span class="tt-map-lbl is-right">{{ $axes['desire']['name'] }}{{ $axes['desire']['high'] }}</span>

        @foreach([['low','high','tl'], ['high','high','tr'], ['low','low','bl'], ['high','low','br']] as [$d, $b, $pos])
            @php $c = $corner($d, $b); @endphp
            @if($c)
            <a class="hm-corner is-{{ $pos }} tt-c-{{ $c['colour'] }} {{ $c['key'] === $key ? 'is-current' : '' }}"
               href="{{ route('horny-test.result', ['slug' => $c['slug']]) }}">{{ $c['name'] }}</a>
            @endif
        @endforeach

        <svg class="tt-map-svg" viewBox="0 0 100 100" role="img"
             aria-label="{{ $axes['desire']['note'] }}／{{ $axes['brake']['note'] }}">
            <rect class="tt-map-bg" x="0" y="0" width="100" height="100" rx="4"/>
            {{-- 四分之一線比滿格網格好讀:只要看得出「偏哪一邊、偏多少」 --}}
            <line class="tt-map-q" x1="25" y1="0" x2="25" y2="100"/>
            <line class="tt-map-q" x1="75" y1="0" x2="75" y2="100"/>
            <line class="tt-map-q" x1="0" y1="25" x2="100" y2="25"/>
            <line class="tt-map-q" x1="0" y1="75" x2="100" y2="75"/>
            {{-- 中央那一塊畫出來:落在這個圈裡的人不屬於任何一個角,而那是刻意的 --}}
            <circle class="hm-mid" cx="50" cy="50" r="{{ (int) config('horny.middle_band', 10) }}"/>
            <line class="tt-map-axis" x1="50" y1="0" x2="50" y2="100"/>
            <line class="tt-map-axis" x1="0" y1="50" x2="100" y2="50"/>
            @if($x !== null && $y !== null)
            <line class="tt-map-ray" x1="50" y1="50" x2="{{ $x }}" y2="{{ $y }}"/>
            <circle class="tt-map-halo" cx="{{ $x }}" cy="{{ $y }}" r="8"/>
            <circle class="tt-map-dot" cx="{{ $x }}" cy="{{ $y }}" r="3.4"/>
            @endif
        </svg>
    </div>
    <figcaption class="tt-map-cap">
        @if($desire !== null)
            {{ __('horny.result.map_you') }} —
            <b>{{ $axes['desire']['name'] }}</b> {{ $desire }}% ·
            <b>{{ $axes['brake']['name'] }}</b> {{ $brake }}%
        @else
            {{ __('horny.result.map_hint') }}
        @endif
    </figcaption>
</figure>
