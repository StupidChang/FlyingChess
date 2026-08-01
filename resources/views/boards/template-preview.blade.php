@extends('layouts.app')
@section('title', $board->name . ' — ' . __('play.template_preview'))
@section('meta_description', __('seo.templates_description'))
@section('robots', 'noindex,follow')
@section('content')

<div class="container tpv-page">
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

    {{-- 預覽棋盤。每一格都是正方形(aspect-ratio),行高不會被文字撐開 ——
         之前用 min-height 讓格子高度隨內容跑,同一列的格子高高低低,不成形。
         配色沿用真實棋盤那套 --sq-* 變數,預覽看起來才是同一張棋盤的縮小版。 --}}
    <div class="tpv-board">
        <div class="tpv-grid" style="--tpv-cols:{{ $board->canvas_cols }}">
            @php
                $squareMap = $board->squares->keyBy(fn($s) => $s->grid_row . '-' . $s->grid_col);
            @endphp
            @for($r = 1; $r <= $board->canvas_rows; $r++)
                @for($c = 1; $c <= $board->canvas_cols; $c++)
                    @php $sq = $squareMap->get("$r-$c"); @endphp
                    @if($sq)
                        @php $locked = ! $canSeeAll && ! in_array($sq->position, $openPositions, true); @endphp
                        <div class="tpv-sq{{ $locked ? ' tpv-sq--locked' : '' }}" data-c="{{ $sq->color }}">
                            <span class="tpv-num">{{ $sq->position }}</span>
                            {{-- 鎖住的格子連文字都不輸出,不是用 CSS 遮 --}}
                            @unless($locked)
                                <span class="tpv-text">{{ \Illuminate\Support\Str::limit($sq->text, 26) }}</span>
                            @endunless
                        </div>
                    @else
                        <div class="tpv-gap"></div>
                    @endif
                @endfor
            @endfor
        </div>
    </div>

    {{-- 窄螢幕用的清單:格子小到放不下字的時候,棋盤只負責顯示形狀,
         內容改用這份清單讀。資料跟上面同一份,只是排版不同。 --}}
    <ol class="tpv-list">
        @foreach($board->squares->sortBy('position') as $sq)
            @if($canSeeAll || in_array($sq->position, $openPositions, true))
                <li><span class="tpv-list-num">{{ $sq->position }}</span>{{ $sq->text }}</li>
            @endif
        @endforeach
    </ol>

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

@include('partials.rewarded-unlock', ['barHidden' => true])
@endsection
