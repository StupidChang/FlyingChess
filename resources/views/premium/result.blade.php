@extends('layouts.app')
@section('title', '付款結果 — Premium 會員')
@section('robots', 'noindex,nofollow')
@section('content')

<div class="premium-section" style="text-align:center;padding-top:60px">
    @if($order && $order->isPaid())
        <h1 style="color:#5fd080;margin-bottom:16px">付款成功！</h1>
        <p style="margin-bottom:8px">訂單編號：{{ $order->order_no }}</p>
        <p style="color:var(--text-dim);margin-bottom:28px">你的 Premium 會員已啟用，享受無廣告和進階功能吧！</p>
        @if(env('GOOGLE_GA4_ID'))
        {{-- Load GA4 inline — layout skips GA4 for premium users, but we need payment_success --}}
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ env('GOOGLE_GA4_ID') }}"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', '{{ env('GOOGLE_GA4_ID') }}');
            gtag('event', 'payment_success', {
                order_no: '{{ $order->order_no }}',
                value: {{ $order->amount }},
                currency: 'TWD'
            });
        </script>
        @endif
    @elseif($order && $order->status === 'failed')
        <h1 style="color:#f06080;margin-bottom:16px">付款失敗</h1>
        <p style="color:var(--text-dim);margin-bottom:28px">很抱歉，付款未能完成。請重新嘗試或使用其他付款方式。</p>
    @elseif($order && $order->isPending())
        <h1 style="color:var(--gold);margin-bottom:16px">付款處理中</h1>
        <p style="color:var(--text-dim);margin-bottom:28px">付款尚在處理中，請稍候片刻。若已完成付款，會員狀態將在幾分鐘內更新。</p>
    @else
        <h1 style="color:var(--text-dim);margin-bottom:16px">查無訂單資訊</h1>
        <p style="color:var(--text-dim);margin-bottom:28px">找不到對應的訂單，請回到會員頁面查看狀態。</p>
    @endif

    <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap">
        <a href="{{ route('premium.index') }}" class="btn btn-outline-gold">回到會員中心</a>
        <a href="{{ route('home') }}" class="btn btn-gold">回首頁</a>
    </div>
</div>
@endsection
