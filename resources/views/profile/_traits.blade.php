{{--
    個人資料頁的「我的屬性」區塊。

    參數:
      $traitResults  TraitResult 集合,舊到新

    四條走勢線畫的是光譜(不是 20 種屬性)—— 20 條線疊在一起看不出任何東西。
    走勢圖用 inline SVG 直接算座標:資料點很少(一個人不會測幾百次),
    拉一整套圖表函式庫進來只為了畫四條折線並不划算。
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

        {{-- 四條光譜的走勢 --}}
        <div class="tt-sparks">
            @foreach($axes as $id => $a)
                @php
                    $points = $traitResults->map(fn ($r) => (int) ($r->axes[$id] ?? 0))->all();
                    $n = count($points);
                    $w = 600; $h = 44; $pad = 6;
                    $x = fn ($i) => $n === 1 ? $w / 2 : $pad + $i * ($w - $pad * 2) / ($n - 1);
                    $y = fn ($v) => $h / 2 - ($v / $scale) * ($h / 2 - $pad);
                    $coords = [];
                    foreach ($points as $i => $v) { $coords[] = round($x($i), 1).','.round($y($v), 1); }
                    $last = end($points);
                @endphp
                <div class="tt-spark-card">
                    <div class="tt-spark-head">
                        <strong>{{ $a['left'] }} ⇄ {{ $a['right'] }}</strong>
                        <span>{{ $last >= 0 ? $a['left'] : $a['right'] }} {{ abs(round($last / $scale * 100)) }}%</span>
                    </div>
                    <svg class="tt-spark" viewBox="0 0 {{ $w }} {{ $h }}" preserveAspectRatio="none"
                         role="img" aria-label="{{ $a['left'] }}至{{ $a['right'] }}的變化">
                        <line x1="0" y1="{{ $h / 2 }}" x2="{{ $w }}" y2="{{ $h / 2 }}"
                              stroke="var(--border)" stroke-dasharray="3 4"></line>
                        @if($n > 1)
                        <polygon points="{{ round($x(0), 1) }},{{ $h / 2 }} {{ implode(' ', $coords) }} {{ round($x($n - 1), 1) }},{{ $h / 2 }}"
                                 fill="var(--gold)" opacity=".12"></polygon>
                        <polyline points="{{ implode(' ', $coords) }}" fill="none" stroke="var(--gold)"
                                  stroke-width="2" stroke-linejoin="round" vector-effect="non-scaling-stroke"></polyline>
                        @endif
                        @foreach($points as $i => $v)
                        <circle cx="{{ round($x($i), 1) }}" cy="{{ round($y($v), 1) }}"
                                r="{{ $i === $n - 1 ? 4 : 2.5 }}"
                                fill="{{ $i === $n - 1 ? 'var(--gold)' : 'var(--bg)' }}"
                                stroke="var(--gold)" stroke-width="2" vector-effect="non-scaling-stroke"></circle>
                        @endforeach
                    </svg>
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
