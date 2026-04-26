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

    {{-- 客服聯絡 --}}
    <div style="margin-top:40px;background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:24px;max-width:480px;margin-left:auto;margin-right:auto">
        <h2 style="font-size:1.1rem;color:var(--text);margin-bottom:16px;text-align:center">需要協助？</h2>
        <div style="display:flex;flex-direction:column;gap:12px">
            <div style="display:flex;align-items:center;gap:10px">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:20px;height:20px;color:var(--gold);flex-shrink:0">
                    <path d="M1.5 8.67v8.58a3 3 0 0 0 3 3h15a3 3 0 0 0 3-3V8.67l-8.928 5.493a3 3 0 0 1-3.144 0L1.5 8.67Z"/>
                    <path d="M22.5 6.908V6.75a3 3 0 0 0-3-3h-15a3 3 0 0 0-3 3v.158l9.714 5.978a1.5 1.5 0 0 0 1.572 0L22.5 6.908Z"/>
                </svg>
                <div>
                    <div style="font-size:.8rem;color:var(--text-dim)">Email 客服</div>
                    <a href="mailto:support@couplefly.com" style="color:var(--gold);font-size:.9rem">support@couplefly.com</a>
                </div>
            </div>
            <div style="display:flex;align-items:center;gap:10px">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:20px;height:20px;color:var(--gold);flex-shrink:0">
                    <path fill-rule="evenodd" d="M4.848 2.771A49.144 49.144 0 0 1 12 2.25c2.43 0 4.817.178 7.152.52 1.978.29 3.348 2.024 3.348 3.97v6.02c0 1.946-1.37 3.68-3.348 3.97a48.901 48.901 0 0 1-3.476.383.39.39 0 0 0-.297.17l-2.755 4.133a.75.75 0 0 1-1.248 0l-2.755-4.133a.39.39 0 0 0-.297-.17 48.9 48.9 0 0 1-3.476-.384c-1.978-.29-3.348-2.024-3.348-3.97V6.741c0-1.946 1.37-3.68 3.348-3.97ZM6.75 8.25a.75.75 0 0 1 .75-.75h9a.75.75 0 0 1 0 1.5h-9a.75.75 0 0 1-.75-.75Zm.75 2.25a.75.75 0 0 0 0 1.5H12a.75.75 0 0 0 0-1.5H7.5Z" clip-rule="evenodd"/>
                </svg>
                <div>
                    <div style="font-size:.8rem;color:var(--text-dim)">LINE 客服</div>
                    <span style="color:var(--text);font-size:.9rem">@couplefly</span>
                </div>
            </div>
            <div style="display:flex;align-items:center;gap:10px">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:20px;height:20px;color:var(--gold);flex-shrink:0">
                    <path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25ZM12.75 6a.75.75 0 0 0-1.5 0v6c0 .414.336.75.75.75h4.5a.75.75 0 0 0 0-1.5h-3.75V6Z" clip-rule="evenodd"/>
                </svg>
                <div>
                    <div style="font-size:.8rem;color:var(--text-dim)">客服時間</div>
                    <span style="color:var(--text);font-size:.9rem">週一至週五 10:00–18:00</span>
                </div>
            </div>
        </div>
        <p style="text-align:center;margin-top:14px;font-size:.78rem;color:var(--text-dim)">
            付款問題、帳號問題、內容回報皆可透過上述方式聯繫我們
        </p>
    </div>
</div>
@endsection
