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

@section('title', __('games.pure_wheel') . ' — ' . __('ui.site_name'))
@section('meta_description', __('games.desc_pure_wheel'))
@section('canonical', route('wheel.pure'))

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

/* 指針正後方的底盤:深色圓盤 + 旋轉的七彩外緣。
   目的是把指針從 12 色扇形上「墊」出來 —— 粉色指針直接壓在黃色或粉色扇形上
   對比不足,加一層深底才看得清楚指向。
   RGB 效果的做法:深色填在 padding-box、conic-gradient 填在 border-box,
   圓盤本體是對稱的,所以旋轉整個元素時只有彩色邊緣看起來在轉。 */
.pw-inner{
    position:absolute;top:50%;left:50%;z-index:2;pointer-events:none;
    width:68%;height:68%;border-radius:50%;
    border:4px solid transparent;
    background:
        radial-gradient(circle at 42% 34%, #2b3042 0%, #14161f 72%) padding-box,
        conic-gradient(#e53935,#fb8c00,#fdd835,#43a047,#1e88e5,#8e24aa,#f06292,#e53935) border-box;
    box-shadow:0 0 18px rgba(0,0,0,.55), inset 0 2px 10px rgba(0,0,0,.5);
    transform:translate(-50%,-50%);
    animation:pw-inner-spin 6s linear infinite;
}
@keyframes pw-inner-spin{
    from{transform:translate(-50%,-50%) rotate(0deg)}
    to  {transform:translate(-50%,-50%) rotate(360deg)}
}

/* 指針:整支跟著轉,尖端朝外 */
.pw-needle{position:absolute;inset:0;width:100%;height:100%;display:block;z-index:4;
    transform:rotate(0deg);transform-origin:50% 50%;
    transition:transform 4.6s cubic-bezier(.16,.7,.06,1);
    filter:drop-shadow(0 3px 10px rgba(0,0,0,.5))}

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

            <circle cx="{{ $cx }}" cy="{{ $cy }}" r="{{ $r }}"
                    fill="none" stroke="rgba(255,255,255,.15)" stroke-width="2"/>
        </svg>

        {{-- 指針後方的深色底盤(七彩外緣) --}}
        <div class="pw-inner"></div>

        {{-- 旋轉指針:長端朝外,短端為配重 --}}
        <svg class="pw-needle" id="pw-needle" viewBox="0 0 200 200"
             aria-hidden="true" focusable="false">
            <defs>
                {{-- 愛心指針:上淺下深的粉色漸層,做出圓潤的糖果感 --}}
                <linearGradient id="pwTip" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0"   stop-color="#ffd6e6"/>
                    <stop offset=".45" stop-color="#ff8fb8"/>
                    <stop offset="1"   stop-color="#f43f5e"/>
                </linearGradient>
                {{-- 柄:與愛心同色系,略暗以拉出層次 --}}
                <linearGradient id="pwStem" x1="0" y1="0" x2="1" y2="0">
                    <stop offset="0"   stop-color="#ff9ec4"/>
                    <stop offset=".45" stop-color="#ffe2ee"/>
                    <stop offset="1"   stop-color="#f8759c"/>
                </linearGradient>
                {{-- 中心軸的珠光效果。SVG 的 fill 屬性不接受 CSS radial-gradient(),
                     必須用 <radialGradient> 定義後以 url(#id) 引用。 --}}
                <radialGradient id="pwHub" cx="38%" cy="34%" r="72%">
                    <stop offset="0" stop-color="#ffffff"/>
                    <stop offset="1" stop-color="#f2e3ea"/>
                </radialGradient>
            </defs>

            {{-- 配重尾端:圓球,比三角形柔和 --}}
            <circle cx="100" cy="128" r="7.5" fill="rgba(255,255,255,.7)"
                    stroke="rgba(0,0,0,.18)" stroke-width="1"/>

            {{-- 圓潤的柄 --}}
            <rect x="93.5" y="38" width="13" height="68" rx="6.5"
                  fill="url(#pwStem)" stroke="rgba(0,0,0,.16)" stroke-width="1"/>

            {{-- 愛心指針:尖端朝外。原始愛心路徑的尖端朝下,
                 所以整組 transform 先歸心、放大,再 rotate(180) 讓尖端轉向外側。 --}}
            <g transform="translate(100 31) rotate(180) scale(1.34) translate(-16 -16)"
               stroke="rgba(0,0,0,.22)" stroke-width=".9" stroke-linejoin="round">
                <path fill="url(#pwTip)"
                      d="M16 28.4S3.2 20.9 3.2 12.3C3.2 7.9 6.7 4.6 10.9 4.6c2.5 0 4.3 1.2 5.1 2.6.8-1.4 2.6-2.6 5.1-2.6 4.2 0 7.7 3.3 7.7 7.7 0 8.6-12.8 16.1-12.8 16.1z"/>
                {{-- 高光小點,增加立體感 --}}
                <ellipse cx="10.6" cy="11.2" rx="2.7" ry="2" fill="rgba(255,255,255,.85)"
                         stroke="none" transform="rotate(-18 10.6 11.2)"/>
            </g>

            {{-- 珠光軸心 + 中央小愛心 --}}
            <circle cx="100" cy="100" r="15" fill="url(#pwHub)"
                    stroke="var(--accent)" stroke-width="2.5"/>
            <path fill="var(--accent)"
                  transform="translate(100 100) scale(.30) translate(-16 -16)"
                  d="M16 28.4S3.2 20.9 3.2 12.3C3.2 7.9 6.7 4.6 10.9 4.6c2.5 0 4.3 1.2 5.1 2.6.8-1.4 2.6-2.6 5.1-2.6 4.2 0 7.7 3.3 7.7 7.7 0 8.6-12.8 16.1-12.8 16.1z"/>
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
