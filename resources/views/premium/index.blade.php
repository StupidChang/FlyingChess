@extends('layouts.app')
@section('title', 'Premium 會員 — 情侶飛行棋')
@section('meta_description', '升級 Premium 會員，免廣告、解鎖進階題庫、建立私人房間，NT$99/月。')
@section('robots', 'noindex,nofollow')
@section('content')

<div class="premium-section">
    <h1 style="text-align:center;color:var(--gold);margin-bottom:32px">Premium 會員</h1>

    @if($isPremium)
    <div class="premium-status">
        <strong style="color:var(--gold)">你是 Premium 會員</strong><br>
        到期日：{{ $expiresAt->format('Y/m/d') }}
        <br><br>
        <small>到期後將自動回復為免費會員，可隨時續費。</small>
    </div>
    @endif

    <div class="premium-card">
        <h2 style="color:var(--text);font-size:1.2rem">月費方案</h2>
        <div class="premium-price">NT${{ $price }}</div>
        <div class="premium-period">每月 / 不自動續訂</div>

        <ul class="premium-features">
            <li>全站免廣告</li>
            <li>進階互動題庫（真心話大冒險 Premium 卡牌）</li>
            <li>建立私人房間</li>
            <li>Premium 棋盤模板</li>
            <li>18+ 會員專屬內容</li>
        </ul>

        @auth
            <form action="{{ route('premium.checkout') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-gold btn-xl btn-full"
                    onclick="typeof gtag!=='undefined'&&gtag('event','checkout_started',{value:{{ $price }}})">
                    {{ $isPremium ? '續費延長' : '立即升級' }}
                </button>
            </form>
        @else
            <a href="{{ route('register') }}" class="btn btn-gold btn-xl btn-full">
                註冊後升級
            </a>
            <p style="text-align:center;margin-top:12px;font-size:.85rem;color:var(--text-dim)">
                已有帳號？<a href="{{ route('login') }}" style="color:var(--gold)">登入</a>
            </p>
        @endauth
    </div>

    <div style="text-align:center;margin-top:24px;font-size:.82rem;color:var(--text-dim)">
        付款由綠界科技 ECPay 安全處理。不自動續訂，到期前可手動續費。<br>
        續費時，新到期日從原到期日起延長 30 天。
    </div>
</div>
@endsection
