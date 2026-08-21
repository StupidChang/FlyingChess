@extends('layouts.app')

@section('title', __('traits.compare.seo_title') . ' — ' . __('ui.site_name'))
@section('meta_description', __('traits.compare.seo_description'))
@section('og_title', __('traits.compare.seo_title'))
@section('og_description', __('traits.compare.seo_description'))

{{-- 刻意 noindex。20 型兩兩 = 190 種組合,內容是同一批文字重新排列 —— 開放索引
     等於自己跟自己搶字。canonical 指回題目頁,而不是指向某一組組合。 --}}
@section('robots', 'noindex,follow')
@section('canonical', route('trait-test.show'))

@section('content')
<div class="container tt-page">
    <div class="tt-main">

        <header class="tt-cmp-head">
            <h1>{{ __('traits.compare.title') }}</h1>
            <p class="tt-line">{{ __('traits.compare.intro') }}</p>
        </header>

        {{-- 選型。純 GET 表單:換選項就是換網址,整頁可以直接分享出去,也不需要 JS。 --}}
        <form class="tt-cmp-pick" method="get" action="{{ route('trait-test.compare') }}">
            <label>
                <span>{{ __('traits.compare.you') }}</span>
                <select name="a">
                    <option value="">{{ __('traits.compare.pick') }}</option>
                    @foreach($items as $key => $item)
                    <option value="{{ $item['slug'] }}" @selected($key === $aKey)>{{ $item['name'] }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                <span>{{ __('traits.compare.partner') }}</span>
                <select name="b">
                    <option value="">{{ __('traits.compare.pick') }}</option>
                    @foreach($items as $key => $item)
                    <option value="{{ $item['slug'] }}" @selected($key === $bKey)>{{ $item['name'] }}</option>
                    @endforeach
                </select>
            </label>
            <button type="submit" class="btn btn-primary">{{ __('traits.compare.submit') }}</button>
        </form>

        @if(! $comparison)
            <p class="tt-hint tt-cmp-empty">{{ __('traits.compare.empty') }}</p>
            <p class="tt-cmp-cta"><a class="btn btn-outline" href="{{ route('trait-test.show') }}">{{ __('traits.compare.cta_test') }}</a></p>
        @else

            <section class="tt-card tt-cmp-pair">
                <div class="tt-cmp-side tt-c-{{ $a['colour'] }}">
                    <span class="tt-cmp-who">{{ __('traits.compare.legend_you') }}</span>
                    <h2>{{ $a['name'] }}</h2>
                    <p>{{ $a['line'] }}</p>
                    <a href="{{ route('trait-test.result', ['slug' => $a['slug']]) }}">{{ __('traits.compare.cta_result', ['name' => $a['name']]) }}</a>
                </div>
                <div class="tt-cmp-side tt-c-{{ $b['colour'] }}">
                    <span class="tt-cmp-who">{{ __('traits.compare.legend_partner') }}</span>
                    <h2>{{ $b['name'] }}</h2>
                    <p>{{ $b['line'] }}</p>
                    <a href="{{ route('trait-test.result', ['slug' => $b['slug']]) }}">{{ __('traits.compare.cta_result', ['name' => $b['name']]) }}</a>
                </div>
            </section>

            @if($aKey === $bKey)
            <p class="tt-hint tt-cmp-same">{{ __('traits.compare.same') }}</p>
            @endif

            {{-- 免費。這一段全部是「描述」層:結論(有沒有被寫進名單)與題庫裡的
                 共現數字,跟結果頁那段計分依據同一個層級 —— 不上鎖。名單的**內容**
                 才是付費的那一半,在下面。 --}}
            <section class="tt-card tt-cmp-stand">
                <h2>{{ __('traits.compare.stand_title') }}</h2>

                @if($comparison['named']['match'])
                <p class="tt-cmp-good">{{ __('traits.compare.named_match') }}</p>
                @endif
                @if($comparison['named']['friction'])
                <p class="tt-cmp-bad">{{ __('traits.compare.named_friction') }}</p>
                @endif
                @if(! $comparison['named']['match'] && ! $comparison['named']['friction'])
                <p class="tt-hint">{{ __('traits.compare.named_none') }}</p>
                @endif

                @php $sig = $comparison['signal']; @endphp
                @if($sig['same'] + $sig['opposite'] > 0)
                <div class="tt-cmp-sig">
                    <span class="tt-cmp-sig-num tt-cmp-sig-same">{{ __('traits.compare.signal_same', ['n' => $sig['same']]) }}</span>
                    <span class="tt-cmp-sig-num tt-cmp-sig-opp">{{ __('traits.compare.signal_opposite', ['n' => $sig['opposite']]) }}</span>
                </div>
                <p class="tt-cmp-sig-read">
                    @if($sig['opposite'] === 0 || $sig['same'] > $sig['opposite'] * 2)
                        {{ __('traits.compare.signal_read_same') }}
                    @elseif($sig['same'] === 0 || $sig['opposite'] >= $sig['same'])
                        {{ __('traits.compare.signal_read_opposite') }}
                    @else
                        {{ __('traits.compare.signal_read_mixed') }}
                    @endif
                </p>
                <p class="tt-hint">{{ __('traits.compare.signal_hint') }}</p>
                @else
                <p class="tt-hint">{{ __('traits.compare.signal_none') }}</p>
                @endif
            </section>

            <section class="tt-card tt-cmp-axes">
                <h2>{{ __('traits.compare.axis_title') }}</h2>

                @if(! $comparison['rows'])
                <p class="tt-hint">{{ __('traits.compare.axis_none') }}</p>
                @else
                <p class="tt-hint">{{ __('traits.compare.axis_hint') }}</p>

                <div class="tt-cmp-legend">
                    <span class="tt-cmp-key tt-cmp-key-a">{{ __('traits.compare.legend_you') }}</span>
                    <span class="tt-cmp-key tt-cmp-key-b">{{ __('traits.compare.legend_partner') }}</span>
                </div>

                @foreach($comparison['rows'] as $row)
                @php
                    /* -1…+1 換成軌道上的百分比。左右各留 6% —— 標記是用
                       translateX(-50%) 置中的藥丸,壓到 0% 或 100% 會有一半凸出
                       軌道、蓋掉兩端的極性文字。 */
                    $posA = 6 + (($row['a']['pos'] + 1) / 2) * 88;
                    $posB = 6 + (($row['b']['pos'] + 1) / 2) * 88;
                @endphp
                <div class="tt-cmp-axis tt-cmp-{{ $row['state'] }}">
                    <div class="tt-cmp-axis-head">
                        <strong>{{ $row['note'] }}</strong>
                        <span class="tt-cmp-state">{{ __('traits.compare.state.'.$row['state']) }}</span>
                    </div>
                    <div class="tt-cmp-track">
                        <span class="tt-cmp-pole">{{ $row['left'] }}</span>
                        <div class="tt-cmp-rail">
                            <span class="tt-cmp-mid" aria-hidden="true"></span>
                            <span class="tt-cmp-dot tt-cmp-dot-a" style="left: {{ $posA }}%">{{ __('traits.compare.legend_you') }}</span>
                            <span class="tt-cmp-dot tt-cmp-dot-b" style="left: {{ $posB }}%">{{ __('traits.compare.legend_partner') }}</span>
                        </div>
                        <span class="tt-cmp-pole">{{ $row['right'] }}</span>
                    </div>
                </div>
                @endforeach

                @if($comparison['blank'])
                <p class="tt-hint tt-cmp-blank">{{ __('traits.compare.axis_blank', ['axes' => implode('、', $comparison['blank'])]) }}</p>
                @endif
                @endif
            </section>

            @if($comparison['rows'])
            <section class="tt-card tt-cmp-summary">
                <h2>{{ __('traits.compare.summary_title') }}</h2>

                @if($comparison['aligned'])
                    @php
                        $axesNames = collect($comparison['rows'])
                            ->whereIn('id', $comparison['aligned'])
                            ->map(fn ($r) => $r['name'])
                            ->implode('、');
                    @endphp
                    <p class="tt-cmp-good">{{ __('traits.compare.aligned_line', ['axes' => $axesNames]) }}</p>
                @else
                    <p class="tt-cmp-good">{{ __('traits.compare.aligned_none') }}</p>
                @endif

                @if($comparison['tension'])
                    @php $t = $comparison['tension']; @endphp
                    <p class="tt-cmp-bad">{{ __('traits.compare.tension_line', [
                        'axis' => $t['name'],
                        'a' => $t['a']['label'] ?: __('traits.compare.balanced'),
                        'b' => $t['b']['label'] ?: __('traits.compare.balanced'),
                    ]) }}</p>
                @else
                    <p class="tt-cmp-bad">{{ __('traits.compare.tension_none') }}</p>
                @endif
            </section>
            @endif

            {{-- 付費。這幾段在結果頁就鎖著(match / friction / partner_line 全在
                 @if($unlocked) 裡面),對照頁必須用同一條線 —— 不然這一頁就是
                 繞過付費牆的入口。鎖住時**完全不渲染**內容。 --}}
            <section class="tt-card tt-deep">
                <h2>{{ __('traits.compare.deep_title') }}</h2>

                @if($unlocked)
                    @foreach([['item' => $a, 'who' => __('traits.compare.legend_you')], ['item' => $b, 'who' => __('traits.compare.legend_partner')]] as $sideIndex => $side)
                    @php $it = $side['item']; @endphp
                    @if(!empty($it['match']) || !empty($it['friction']) || !empty($it['partner_line']))
                    <div class="tt-cmp-deep-side">
                        <h3 class="tt-deep-sub">{{ $side['who'] }} · {{ $it['name'] }}</h3>

                        @if(!empty($it['match']))
                        <div class="tt-pair tt-pair-match">
                            <span class="tt-pair-tag">{{ __('traits.compare.match_of', ['name' => $it['name']]) }}</span>
                            <p>{{ $it['match'] }}</p>
                        </div>
                        @endif

                        @if(!empty($it['friction']))
                        <div class="tt-pair tt-pair-friction">
                            <span class="tt-pair-tag">{{ __('traits.compare.friction_of', ['name' => $it['name']]) }}</span>
                            <p>{{ $it['friction'] }}</p>
                        </div>
                        @endif

                        @if(!empty($it['partner_line']))
                        <div class="tt-partner">
                            <div class="tt-partner-head">
                                <span class="tt-partner-title">{{ __('traits.compare.partner_of', ['name' => $it['name']]) }}</span>
                            </div>
                            <blockquote class="tt-partner-quote">{{ $it['partner_line'] }}</blockquote>
                        </div>
                        @endif
                    </div>
                    @endif
                    @endforeach
                @else
                    <p class="tt-deep-teaser">{{ __('traits.compare.deep_locked') }}</p>
                    @if(is_array(__('traits.compare.deep_locked_list')))
                    <ul class="tt-deep-list">
                        @foreach(__('traits.compare.deep_locked_list') as $li)
                        <li>{{ $li }}</li>
                        @endforeach
                    </ul>
                    @endif
                    <button type="button" class="btn btn-gold tt-deep-btn"
                            onclick="window.rewardedUnlockOpen && rewardedUnlockOpen()">
                        {{ __('minigame.rewarded_cta', ['minutes' => \App\Support\PremiumAccess::rewardedMinutes()]) }}
                    </button>
                @endif
            </section>

            <p class="tt-cmp-cta"><a class="btn btn-outline" href="{{ route('trait-test.show') }}">{{ __('traits.compare.cta_test') }}</a></p>
        @endif

    </div>
</div>

@include('partials.rewarded-unlock', ['barHidden' => true])
@endsection
