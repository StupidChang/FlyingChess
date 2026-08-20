@extends('layouts.app')
@section('title', __('seo.templates_title') . ' — ' . __('ui.site_name'))
@section('meta_description', __('seo.templates_description'))
@section('og_title', __('seo.templates_title') . ' — ' . __('ui.site_name'))
@section('og_description', __('seo.templates_description'))
@section('content')

<div class="boards-section container">
    <div class="section-head">
        <h1 style="color:var(--gold)">{{ __('play.templates') }}</h1>
    </div>
    <p style="color:var(--text-dim);margin-bottom:24px">{{ __('play.templates_intro') }}</p>

    <div class="boards-grid">
        @foreach($templates as $board)
        <article class="board-card {{ $board->is_premium_template ? 'template-lock' : '' }}">
            @if($board->is_premium_template)
                <span class="template-lock-badge">{{ __('play.premium_template') }}</span>
            @endif
            {{-- 參考圖不再輸出到任何頁面。那兩個檔案是完整的棋盤設計稿,四十格
                 內容全在上面,而使用到它們的八張棋盤**全部是付費範本** —— 只要
                 網址出現在 HTML 裡,檢視原始碼就能拿到原圖,付費牆等於不存在。
                 檔案也一併移出 public/,見 storage/app/board-references/。 --}}
            <div class="board-card-body">
                <h3>{{ $board->name }}</h3>
                @if($board->description)<p>{{ $board->description }}</p>@endif
                <span class="badge-squares">{{ __('ui.square_count', ['n' => $board->squares_count]) }}</span>
                @if($board->is_premium_template)
                    <span class="badge-premium">Premium</span>
                @else
                    <span class="badge-free">{{ __('play.free') }}</span>
                @endif
            </div>
            <div class="board-card-foot">
                <a href="{{ route('boards.template.preview', $board) }}" class="btn btn-sm btn-outline">{{ __('play.preview') }}</a>
                @if($board->is_premium_template)
                    @if(\App\Support\PremiumAccess::keepsakes(auth()->user()))
                        <form action="{{ route('boards.template.clone', $board) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-gold">{{ __('play.use_template') }}</button>
                        </form>
                    @elseif(app(\App\Support\Payments\PaymentGateway::class)->isLive())
                        <a href="{{ route('premium.index') }}" class="btn btn-sm btn-gold">{{ __('play.upgrade_to_unlock') }}</a>
                    @endif
                    {{-- 沒有金流時不放「升級解鎖」——「預覽」那顆會帶到有「看廣告解鎖」
                         的頁面,那才是現在唯一走得通的路。 --}}
                @else
                    @auth
                        <form action="{{ route('boards.template.clone', $board) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-gold">{{ __('play.use_template') }}</button>
                        </form>
                    @else
                        <a href="{{ route('register') }}" class="btn btn-sm btn-outline-gold">{{ __('play.register_to_use') }}</a>
                    @endauth
                @endif
            </div>
        </article>
        @endforeach
    </div>
</div>
@endsection
