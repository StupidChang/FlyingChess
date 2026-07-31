{{--
    純轉盤(指人器)—— 手機平放桌面,指針朝外隨機指向在座的某個人。

    機制:
      - 盤面靜止、指針旋轉。若反過來(盤轉、指針固定在 12 點鐘),
        指到的永遠是同一個方位的人,就失去意義了。
      - 停止角度為連續隨機值,不切扇形、不做加權。扇形只是視覺,不是抽籤單位。
      - 盤面上沒有任何文字:它只決定「指到誰」,不決定要做什麼。

    視覺:沿用 wheel-game/show 的配色系統(8 色循環的放射漸層扇形 + 彩虹外圈 + 光暈),
    讓兩個轉盤頁看起來是同一家的東西。

    設計系統遵循 CLAUDE.md:指針與按鈕用 --accent(--gold 保留給 premium UI)。
--}}
@extends('layouts.app')

{{-- pure_wheel_seo_* 只用在 meta;頁面上的標題與提示仍用 pure_wheel /
     desc_pure_wheel(那兩個 key 也被首頁、大廳、導覽列共用)。 --}}
@section('title', __('games.pure_wheel_seo_title') . ' — ' . __('ui.site_name'))
@section('meta_description', __('games.pure_wheel_seo_meta'))
@section('canonical', route('wheel.pure'))

{{-- 純轉盤沒有人數上限:指針指向在座的任何一個人,程式不記錄玩家清單 --}}
@section('schema')
    @include('partials.game-schema', [
        'gameName' => __('games.pure_wheel'),
        'gameDescription' => __('games.desc_pure_wheel'),
        'gamePath' => 'wheel',
        'minPlayers' => 2,
        'maxPlayers' => null,
    ])
    @include('partials.game-faq-schema', ['faqKey' => 'wheel'])
@endsection

@section('faq')
    @include('partials.game-faq', ['faqKey' => 'wheel'])
@endsection

@php
    // 與 wheel-game/show.blade.php 的 COLORS 完全一致:[外緣深色, 中心淺色]
    $palette = [
        ['#e53935','#ff6659'], ['#fb8c00','#ffbd45'], ['#fdd835','#ffff6b'],
        ['#43a047','#76d275'], ['#1e88e5','#6ab7ff'], ['#8e24aa','#c158dc'],
        ['#f06292','#ff94c2'], ['#26a69a','#64d8cb'],
    ];
    $count = 12;                 // 與原本的轉盤同樣 12 格
    $cx = $cy = 100; $r = 95;
    $step = 360 / $count;
@endphp

@section('styles')
{{-- 這頁用到 mg-page-* 等共用元件,必須自己引入 minigames.css --}}
<link rel="stylesheet" href="{{ asset_v('css/minigames.css') }}">
<style>
.pw-page{
    --pw-size:min(78vmin, 400px);
    /* 扣掉 60px 的 site-header,讓盤面在可視區內垂直置中 */
    min-height:calc(100vh - 60px);
    display:flex;flex-direction:column;align-items:center;justify-content:center;
    gap:clamp(18px,4vh,32px);padding:20px 16px;
    -webkit-user-select:none;user-select:none;
}
@supports (height:100dvh){ .pw-page{min-height:calc(100dvh - 60px)} }

.pw-stage{position:relative;width:var(--pw-size);height:var(--pw-size);flex:0 0 auto;
    cursor:pointer;touch-action:manipulation;border-radius:50%}
.pw-stage:focus-visible{outline:3px solid var(--accent);outline-offset:10px}

/* 背後光暈:與 wg-wheel-glow 同一手法(模糊的彩虹 conic + 緩慢自轉) */
.pw-glow{position:absolute;inset:-12px;border-radius:50%;z-index:0;pointer-events:none;
    background:conic-gradient(#e53935,#fb8c00,#fdd835,#43a047,#1e88e5,#8e24aa,#f06292,#e53935);
    filter:blur(16px);opacity:.35;animation:pw-glow-spin 8s linear infinite}
@keyframes pw-glow-spin{from{transform:rotate(0deg)}to{transform:rotate(360deg)}}

/* 彩虹外圈:與 wg-wheel-ring 同一手法(padding-box/border-box 雙層背景) */
.pw-ring{position:absolute;inset:-4px;border-radius:50%;z-index:3;pointer-events:none;
    border:3px solid transparent;
    background:linear-gradient(var(--bg),var(--bg)) padding-box,
               conic-gradient(#e53935,#fb8c00,#fdd835,#43a047,#1e88e5,#8e24aa,#f06292,#e53935) border-box}

.pw-plate{position:absolute;inset:0;width:100%;height:100%;display:block;z-index:1;
    border-radius:50%;box-shadow:0 0 30px rgba(0,0,0,.4)}

/* 中央軸座(原本是佔 68% 的深色底盤)。

   為什麼縮小:那塊底盤原本是拿來墊高指針對比的 —— 粉色愛心壓在黃色扇形上看不
   清楚,所以在底下鋪一層近黑色。副作用是盤面 68% 的面積都是黑的,顏色只剩外圈
   一圈,整個轉盤看起來就是「黑底加彩邊」。

   改法是把對比責任移回指針本身:金屬刀鋒有深色描邊與投影,壓在任何一個扇形上
   都有邊界(這也是實體錶針在任何錶面上都看得見的原理)。底盤因此只需要留一個
   收住軸心的小軸座,顏色就能鋪滿整個盤面。

   RGB 效果的做法不變:深色填 padding-box、conic-gradient 填 border-box,圓盤
   本體對稱,所以旋轉整個元素時只有彩色邊緣看起來在轉。 */
.pw-inner{
    position:absolute;top:50%;left:50%;z-index:2;pointer-events:none;
    width:23%;height:23%;border-radius:50%;
    border:3px solid transparent;
    background:
        radial-gradient(circle at 38% 30%, #39415a 0%, #1c2130 66%, #12151f 100%) padding-box,
        conic-gradient(#e53935,#fb8c00,#fdd835,#43a047,#1e88e5,#8e24aa,#f06292,#e53935) border-box;
    box-shadow:0 4px 16px rgba(0,0,0,.5), inset 0 1px 6px rgba(255,255,255,.10);
    transform:translate(-50%,-50%);
    animation:pw-inner-spin 6s linear infinite;
}
@keyframes pw-inner-spin{
    from{transform:translate(-50%,-50%) rotate(0deg)}
    to  {transform:translate(-50%,-50%) rotate(360deg)}
}

/* 指針:整支跟著轉,尖端朝外。
   兩層陰影:貼身的一層讓刀鋒與扇形之間有實體感的接縫,散開的一層做離盤的浮起。 */
.pw-needle{position:absolute;inset:0;width:100%;height:100%;display:block;z-index:4;
    transform:rotate(0deg);transform-origin:50% 50%;
    transition:transform 4.6s cubic-bezier(.16,.7,.06,1);
    filter:drop-shadow(0 1px 1.5px rgba(0,0,0,.45))
           drop-shadow(0 6px 14px rgba(0,0,0,.42))}

.pw-hint{margin:0;font-size:.82rem;color:var(--text-dim);text-align:center;max-width:22rem}
.pw-actions .btn{min-width:min(260px,74vw)}

@media (prefers-reduced-motion: reduce){
    .pw-needle{transition:transform .25s linear}
    /* 光暈與底盤七彩邊緣停止旋轉,但保留靜態配色 */
    .pw-glow,.pw-inner{animation:none}
}
</style>
@endsection

@section('content')
<div class="pw-page">
    {{-- 整版轉盤,同 /play 的理由用 sr-only 補 H1。 --}}
    <h1 class="sr-only">{{ __('games.pure_wheel') }}</h1>

    <div class="pw-stage" id="pw-stage" role="button" tabindex="0"
         aria-label="{{ __('games.pure_wheel') }}">

        <div class="pw-glow"></div>

        {{-- 靜止盤面:彩色扇形,無任何文字 --}}
        <svg class="pw-plate" viewBox="0 0 200 200" aria-hidden="true" focusable="false">
            <defs>
                @for ($i = 0; $i < $count; $i++)
                    @php $c = $palette[$i % count($palette)]; @endphp
                    <radialGradient id="pwSeg{{ $i }}" cx="50%" cy="50%" r="50%">
                        <stop offset="0.13" stop-color="{{ $c[1] }}"/>
                        <stop offset="1"    stop-color="{{ $c[0] }}"/>
                    </radialGradient>
                @endfor

                {{-- 中心暈影。扇形的漸層是「內淺外深」,12 格的淺色端全部收在圓心,
                     沒有東西壓的話中央會糊成一片亮色。這層由中心向外淡出的陰影
                     把軸座坐進盤面裡,也順便給指針靠近圓心的那一段一點對比。
                     最深只到 38% 黑,而且 26% 半徑就完全透明 —— 是陰影,不是底盤。 --}}
                <radialGradient id="pwVignette" cx="50%" cy="50%" r="50%">
                    <stop offset="0"    stop-color="#000" stop-opacity=".38"/>
                    <stop offset=".13"  stop-color="#000" stop-opacity=".20"/>
                    <stop offset=".26"  stop-color="#000" stop-opacity="0"/>
                </radialGradient>
            </defs>

            @for ($i = 0; $i < $count; $i++)
                @php
                    // 從 12 點鐘開始,順時針切 $count 等份
                    $a1 = deg2rad($i * $step - 90);
                    $a2 = deg2rad(($i + 1) * $step - 90);
                    $x1 = round($cx + $r * cos($a1), 3); $y1 = round($cy + $r * sin($a1), 3);
                    $x2 = round($cx + $r * cos($a2), 3); $y2 = round($cy + $r * sin($a2), 3);
                    $large = $step > 180 ? 1 : 0;
                @endphp
                <path d="M{{ $cx }} {{ $cy }} L{{ $x1 }} {{ $y1 }} A{{ $r }} {{ $r }} 0 {{ $large }} 1 {{ $x2 }} {{ $y2 }} Z"
                      fill="url(#pwSeg{{ $i }})"
                      stroke="rgba(255,255,255,.4)" stroke-width="1.4"/>
            @endfor

            {{-- 畫在扇形之後、外框之前,才蓋得住 12 條分隔線在圓心的交會點 --}}
            <circle cx="{{ $cx }}" cy="{{ $cy }}" r="{{ $r }}" fill="url(#pwVignette)"/>

            <circle cx="{{ $cx }}" cy="{{ $cy }}" r="{{ $r }}"
                    fill="none" stroke="rgba(255,255,255,.15)" stroke-width="2"/>
        </svg>

        {{-- 指針後方的深色底盤(七彩外緣) --}}
        <div class="pw-inner"></div>

        {{-- 旋轉指針:長端朝外,短端為配重 --}}
        {{-- 指針:金屬刀鋒(錶針結構)。

             為什麼不是平面色塊:單一填色的指針壓在 12 個彩色扇形上,總會有幾格
             跟它撞色。錶針的解法是「結構」而不是「顏色」—— 沿中軸分成受光與
             背光兩個切面,再加一圈深色描邊,於是在任何底色上都靠亮暗交界被辨識
             出來,不依賴與底色的色相差。這也是把中央黑底盤拿掉之後,指針還看得
             清楚的原因。

             尖端一小段染 --accent 是為了標示「指的是這一頭」;--gold 依 CLAUDE.md
             保留給 premium UI,所以這裡不用金色。 --}}
        <svg class="pw-needle" id="pw-needle" viewBox="0 0 200 200"
             aria-hidden="true" focusable="false">
            <defs>
                {{-- 受光面(左):幾乎純白到淺鋼藍 --}}
                <linearGradient id="pwFacetL" x1="0" y1="0" x2="1" y2="0">
                    <stop offset="0"   stop-color="#ffffff"/>
                    <stop offset=".55" stop-color="#eef3fb"/>
                    <stop offset="1"   stop-color="#cbd5e6"/>
                </linearGradient>
                {{-- 背光面(右):中鋼藍到深鋼藍,與左面拉出明度差 --}}
                <linearGradient id="pwFacetR" x1="0" y1="0" x2="1" y2="0">
                    <stop offset="0"   stop-color="#9da9c0"/>
                    <stop offset=".6"  stop-color="#78849e"/>
                    <stop offset="1"   stop-color="#5a6580"/>
                </linearGradient>
                {{-- 配重尾:同色系但整體更暗,視覺重量壓在後端 --}}
                <linearGradient id="pwTail" x1="0" y1="0" x2="1" y2="0">
                    <stop offset="0"   stop-color="#dfe6f2"/>
                    <stop offset=".5"  stop-color="#9aa6bd"/>
                    <stop offset="1"   stop-color="#616c85"/>
                </linearGradient>
                {{-- 軸心:拋光鋼的珠光。SVG 的 fill 不吃 CSS radial-gradient(),
                     必須用 <radialGradient> 定義後以 url(#id) 引用。 --}}
                <radialGradient id="pwHub" cx="34%" cy="28%" r="76%">
                    <stop offset="0"   stop-color="#ffffff"/>
                    <stop offset=".45" stop-color="#dde4f0"/>
                    <stop offset="1"   stop-color="#9ba7bd"/>
                </radialGradient>
            </defs>

            {{-- 配重尾:與刀鋒反向,讓整支指針在視覺上是平衡的 --}}
            <g stroke="rgba(9,12,20,.55)" stroke-width="1.1" stroke-linejoin="round">
                <path fill="url(#pwTail)"
                      d="M100 112 L103.4 114 C104.2 122 104.9 130 105 135.2
                         C105.1 139.2 102.9 141.8 100 141.8
                         C97.1 141.8 94.9 139.2 95 135.2
                         C95.1 130 95.8 122 96.6 114 Z"/>
                <circle cx="100" cy="133.5" r="5.6" fill="url(#pwHub)"/>
            </g>

            {{-- 刀鋒:整體輪廓先描一次深色邊,再用兩個切面填色。
                 先畫輪廓再蓋切面,可以避免中軸那條分界線也被描到。 --}}
            <path d="M100 13
                     C101.7 34 104.7 62 105.3 84.5
                     C105.5 91.5 103.1 96.5 100 96.5
                     C96.9 96.5 94.5 91.5 94.7 84.5
                     C95.3 62 98.3 34 100 13 Z"
                  fill="none" stroke="rgba(9,12,20,.6)" stroke-width="2.2"
                  stroke-linejoin="round"/>
            {{-- 受光切面(左半) --}}
            <path fill="url(#pwFacetL)"
                  d="M100 13 C98.3 34 95.3 62 94.7 84.5
                     C94.5 91.5 96.9 96.5 100 96.5 Z"/>
            {{-- 背光切面(右半) --}}
            <path fill="url(#pwFacetR)"
                  d="M100 13 C101.7 34 104.7 62 105.3 84.5
                     C105.5 91.5 103.1 96.5 100 96.5 Z"/>
            {{-- 尖端染色段:標示指向的那一頭 --}}
            <path fill="var(--accent)" fill-opacity=".92"
                  stroke="rgba(9,12,20,.5)" stroke-width="1" stroke-linejoin="round"
                  d="M100 13 C101.1 26 101.9 32.5 102.2 37.4
                     L97.8 37.4 C98.1 32.5 98.9 26 100 13 Z"/>
            {{-- 沿中軸的一道細高光,做出「折出稜線」的金屬感 --}}
            <path d="M100 20 L100 92" stroke="rgba(255,255,255,.5)" stroke-width=".9"
                  stroke-linecap="round" fill="none"/>

            {{-- 拋光軸心:外圈深色壓邊,內圈 accent 細環,中央珠光 --}}
            <circle cx="100" cy="100" r="16.5" fill="url(#pwHub)"
                    stroke="rgba(9,12,20,.55)" stroke-width="1.6"/>
            <circle cx="100" cy="100" r="11.5" fill="none"
                    stroke="var(--accent)" stroke-width="2.2" stroke-opacity=".9"/>
            <circle cx="100" cy="100" r="5" fill="url(#pwHub)"
                    stroke="rgba(9,12,20,.35)" stroke-width="1"/>
            {{-- 軸心左上的高光點,定住光源方向(與兩個切面的明暗一致) --}}
            <ellipse cx="94.2" cy="93.6" rx="4.2" ry="2.8" fill="rgba(255,255,255,.75)"
                     transform="rotate(-38 94.2 93.6)"/>
        </svg>

        <div class="pw-ring"></div>
    </div>

    <div class="pw-actions">
        <button type="button" class="btn btn-primary btn-xl" id="pw-spin">
            {{ __('minigame.wheel_spin_btn') }}
        </button>
    </div>

    <p class="pw-hint">{{ __('games.desc_pure_wheel') }}</p>
</div>
@endsection

@section('scripts')
<script>
(function(){
    var needle  = document.getElementById('pw-needle');
    var stage   = document.getElementById('pw-stage');
    var spinBtn = document.getElementById('pw-spin');
    if(!needle || !stage || !spinBtn) return;

    var reduce = window.matchMedia
        && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var SPIN_MS = reduce ? 250 : 4600;   // 需與 .pw-needle 的 transition 秒數一致

    var spinning = false;
    var angle = 0;   // 累積角度:每次從當前位置續轉,不跳回原點

    function spin(){
        if(spinning) return;
        spinning = true;
        spinBtn.disabled = true;

        // 連續隨機角度,不切扇形 —— 扇形只是視覺參考。
        // 指到人與人之間的縫隙也是合理結果,由在座的人自行判斷。
        var turns = reduce ? 0 : 4 + Math.floor(Math.random() * 4);
        angle += turns * 360 + Math.random() * 360;
        needle.style.transform = 'rotate(' + angle + 'deg)';

        setTimeout(function(){
            spinning = false;
            spinBtn.disabled = false;
        }, SPIN_MS + 100);
    }

    stage.addEventListener('click', spin);
    spinBtn.addEventListener('click', function(e){ e.stopPropagation(); spin(); });
    stage.addEventListener('keydown', function(e){
        if(e.key === 'Enter' || e.key === ' '){ e.preventDefault(); spin(); }
    });
})();
</script>
@endsection
