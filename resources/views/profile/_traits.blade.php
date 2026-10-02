{{--
    個人資料頁的「我的屬性」區塊。

    參數:
      $traitResults  TraitResult 集合,舊到新

    四條光譜(不是 20 種屬性)各畫成一把左右兩極的尺,見下面的說明。
    純 HTML/CSS 定位,不用圖表函式庫:資料點很少(一個人不會測幾百次)。
--}}
@php
    $service = app(\App\Services\TraitTestService::class);
    $items = (array) __('traits.items');
    $axes = $service->axes();
    $latest = $traitResults->last();
    $scale = \App\Services\TraitTestService::AXIS_SCALE;
@endphp

<section style="margin-bottom:36px">
    <div class="section-head">
        <h2>{{ __('traits.profile.heading') }}</h2>
        <a href="{{ route('trait-test.show') }}" class="btn btn-sm btn-outline-gold">
            {{ $traitResults->isEmpty() ? __('traits.profile.take') : __('traits.retake') }}
        </a>
    </div>

    @if($traitResults->isEmpty())
        <div class="empty-notice">{{ __('traits.profile.empty') }}</div>
    @else
        @php $topItem = $items[$latest->top_trait] ?? null; @endphp
        @if($topItem)
        <a class="tt-latest tt-c-{{ config('traits.traits.'.$latest->top_trait.'.colour', 'gold') }}"
           href="{{ route('trait-test.result', ['slug' => $topItem['slug']]) }}">
            <span class="tt-latest-label">{{ __('traits.profile.latest') }}</span>
            <strong>{{ $topItem['name'] }}</strong>
            <span class="tt-latest-pct">{{ $latest->traits[0]['pct'] ?? 0 }}%</span>
        </a>
        @endif

        {{-- 四條光譜:每一條是一把左右兩極的尺。
             原本是 44px 高的折線,而實際分數幾乎都落在 ±2(滿分 ±8)之間 —— 四條線全貼在
             中線上,看起來一模一樣。改成橫向的尺:兩端寫極名、最新一次是大點、之前的是
             淡色小點,箭頭是「比上次往哪邊移動多少」。位置照實際比例畫(不放大),
             差 1 分在尺上也有 1/16 寬,看得出來。
             顏色是固定的兩極:左玫瑰紅、右藍、正中間灰色,不隨主題換 —— 兩套主題的底色
             都驗過色盲可辨識度。顏色只是輔助,每個點的位置與文字本身就說得清楚。 --}}
        @php
            $pct = fn ($v) => (int) abs(round($v / $scale * 100));
            // 正值是左極,所以正值要往左畫:+8 在 0%、0 在 50%、-8 在 100%
            $pos = fn ($v) => round(50 - max(-$scale, min($scale, $v)) / $scale * 50, 2);
            $side = fn ($v) => $v > 0 ? 'is-left' : ($v < 0 ? 'is-right' : 'is-mid');
            $verdict = fn ($v, $a) => $v === 0
                ? __('traits.profile.axis_middle')
                : __('traits.profile.axis_lean', ['pole' => $v > 0 ? $a['left'] : $a['right'], 'pct' => $pct($v)]);
        @endphp
        <h3 class="tt-axes-title">{{ __('traits.profile.axis_axes_title') }}</h3>
        <div class="tt-axes">
            @foreach($axes as $id => $a)
                @php
                    $points = $traitResults->map(fn ($r) => ['v' => (int) ($r->axes[$id] ?? 0), 'at' => $r->created_at])->values();
                    $now = $points->last();
                    $prev = $points->count() > 1 ? $points->get($points->count() - 2) : null;
                    $delta = $prev ? $now['v'] - $prev['v'] : null;
                @endphp
                <div class="tt-axis">
                    <div class="tt-axis-top">
                        <span class="tt-axis-q">{{ $a['note'] }}</span>
                        <strong @class(['tt-axis-now', $side($now['v'])])>{{ $verdict($now['v'], $a) }}</strong>
                    </div>

                    <div class="tt-axis-row">
                        <span class="tt-axis-pole is-left"><i aria-hidden="true"></i>{{ $a['left'] }}</span>
                        <div class="tt-axis-track" role="img"
                             aria-label="{{ $a['left'] }} ⇄ {{ $a['right'] }}:{{ $verdict($now['v'], $a) }}">
                            <span class="tt-axis-tick" style="left:25%"></span>
                            <span class="tt-axis-tick is-center" style="left:50%"></span>
                            <span class="tt-axis-tick" style="left:75%"></span>
                            @if($prev && $delta !== 0)
                                <span @class(['tt-axis-move', $delta > 0 ? 'to-left' : 'to-right'])
                                      style="left:{{ min($pos($prev['v']), $pos($now['v'])) }}%;width:{{ abs($pos($now['v']) - $pos($prev['v'])) }}%"></span>
                            @endif
                            @foreach($points as $i => $pt)
                                @php $isNow = $i === $points->count() - 1; @endphp
                                <span @class(['tt-axis-dot', 'is-now' => $isNow, 'is-past' => ! $isNow, $side($pt['v'])])
                                      style="left:{{ $pos($pt['v']) }}%"
                                      title="{{ __('traits.profile.axis_point', ['date' => $pt['at']->format('Y/m/d'), 'verdict' => $verdict($pt['v'], $a)]) }}"></span>
                            @endforeach
                        </div>
                        <span class="tt-axis-pole is-right">{{ $a['right'] }}<i aria-hidden="true"></i></span>
                    </div>

                    <p class="tt-axis-foot">
                        @if(! $prev)
                            {{ __('traits.profile.axis_first') }}
                        @elseif($delta === 0)
                            {{ __('traits.profile.axis_unchanged') }}
                        @else
                            {{ __('traits.profile.axis_moved', ['pole' => $delta > 0 ? $a['left'] : $a['right'], 'pct' => $pct($delta)]) }}
                        @endif
                    </p>
                </div>
            @endforeach
        </div>

        {{-- 每一次測驗一列,新的在上面 --}}
        @foreach($traitResults->reverse()->values() as $i => $r)
            @php
                $prev = $traitResults->reverse()->values()->get($i + 1);
                $item = $items[$r->top_trait] ?? null;
            @endphp
            <div class="tt-entry">
                <div>
                    <div class="tt-entry-name">
                        {{ $item['name'] ?? $r->top_trait }} {{ $r->traits[0]['pct'] ?? 0 }}%
                    </div>
                    <div class="tt-entry-sub">
                        @if($prev && $prev->top_trait !== $r->top_trait)
                            {!! __('traits.profile.changed', [
                                'from' => '<em>'.e($items[$prev->top_trait]['name'] ?? $prev->top_trait).'</em>',
                                'to' => '<em>'.e($item['name'] ?? $r->top_trait).'</em>',
                            ]) !!}
                        @else
                            {{ __('traits.profile.others', [
                                'names' => collect($r->runnersUp(2, 0))->map(fn ($t) => $items[$t['key']]['name'] ?? $t['key'])->join('、'),
                            ]) }}
                        @endif
                    </div>
                </div>
                <div class="tt-entry-date">{{ $r->created_at->format('Y/m/d') }}</div>
            </div>
        @endforeach
    @endif
</section>
