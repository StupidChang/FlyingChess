@extends('layouts.app')
@section('title', $board->name . ' — ' . __('play.template_preview'))
@section('meta_description', __('seo.templates_description'))
@section('robots', 'noindex,follow')
@section('content')

<div class="container" style="max-width:800px;padding:40px 20px">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:12px">
        <div>
            <h1 style="color:var(--gold);font-size:1.4rem">{{ $board->name }}</h1>
            @if($board->description)
                <p style="color:var(--text-dim);margin-top:4px">{{ $board->description }}</p>
            @endif
        </div>
        <div style="display:flex;gap:8px;align-items:center">
            @if($board->is_premium_template)
                <span class="badge-premium">{{ __('play.premium_template') }}</span>
            @else
                <span class="badge-free">{{ __('play.free_template') }}</span>
            @endif
            <span class="badge-squares">{{ __('ui.square_count', ['n' => $board->squares->count()]) }}</span>
        </div>
    </div>

    {{-- 原始設計參考圖整張棋盤都看得到,等於把付費內容送出去。鎖住時直接不輸出:
         用 CSS 糊掉沒有意義,圖檔還是下載到瀏覽器了,右鍵就能看原圖。 --}}
    @if($board->reference_image && $canSeeAll)
        <figure style="margin:0 0 24px">
            <a href="{{ asset($board->reference_image) }}" target="_blank" rel="noopener">
                <img src="{{ asset($board->reference_image) }}" alt="{{ $board->name }} 原始設計參考圖"
                     style="display:block;width:100%;height:auto;border-radius:12px;border:1px solid var(--border)">
            </a>
            <figcaption style="color:var(--text-dim);font-size:.8rem;margin-top:8px;text-align:center">
                原始設計參考圖（點擊可查看完整尺寸）
            </figcaption>
        </figure>
    @endif

    {{-- Static preview grid --}}
    <div style="background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:20px;overflow-x:auto;margin-bottom:24px">
        <div style="display:grid;grid-template-columns:repeat({{ $board->canvas_cols }}, minmax(60px, 1fr));gap:4px">
            @php
                $squareMap = $board->squares->keyBy(fn($s) => $s->grid_row . '-' . $s->grid_col);
            @endphp
            @for($r = 1; $r <= $board->canvas_rows; $r++)
                @for($c = 1; $c <= $board->canvas_cols; $c++)
                    @php $sq = $squareMap->get("$r-$c"); @endphp
                    @if($sq)
                        @php $locked = ! $canSeeAll && $sq->position >= $previewOpenSquares; @endphp
                        <div class="tpv-sq{{ $locked ? ' tpv-sq--locked' : '' }}">
                            {{-- 鎖住的格子連文字都不輸出,不是用 CSS 遮 --}}
                            {{ $locked ? '' : \Illuminate\Support\Str::limit($sq->text, 20) }}
                        </div>
                    @else
                        <div style="min-height:50px"></div>
                    @endif
                @endfor
            @endfor
        </div>
    </div>

    @unless($canSeeAll)
        {{-- 預覽開頭幾格就好:看得出調性,但看不完。兩條解鎖路徑並排。 --}}
        <p class="tpv-locked-note">
            {{ __('play.preview_locked_note', ['open' => $previewOpenSquares, 'minutes' => \App\Support\PremiumAccess::rewardedMinutes()]) }}
        </p>
    @endunless

    <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap">
        <a href="{{ route('boards.templates') }}" class="btn btn-outline">{{ __('play.back_to_templates') }}</a>
        @unless($canSeeAll)
            <button type="button" class="btn btn-gold"
                    onclick="window.rewardedUnlockOpen && rewardedUnlockOpen()">
                {{ __('minigame.rewarded_cta', ['minutes' => \App\Support\PremiumAccess::rewardedMinutes()]) }}
            </button>
        @endunless
        @if($board->is_premium_template)
            @auth
                @if(auth()->user()->isPremium())
                    <form action="{{ route('boards.template.clone', $board) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-gold">{{ __('play.use_this_template') }}</button>
                    </form>
                @else
                    <a href="{{ route('premium.index') }}" class="btn btn-gold">{{ __('play.upgrade_to_unlock') }}</a>
                @endif
            @else
                <a href="{{ route('premium.index') }}" class="btn btn-gold">{{ __('play.upgrade_to_unlock') }}</a>
            @endauth
        @else
            @auth
                <form action="{{ route('boards.template.clone', $board) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-gold">{{ __('play.use_this_template') }}</button>
                </form>
            @else
                <a href="{{ route('register') }}" class="btn btn-outline-gold">{{ __('play.register_to_use') }}</a>
            @endauth
        @endif
    </div>
</div>

@include('partials.rewarded-unlock')
@endsection
