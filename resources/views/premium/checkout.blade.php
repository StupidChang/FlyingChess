@extends('layouts.app')
@section('title', '付款中 — Premium 會員')
@section('robots', 'noindex,nofollow')
@section('content')

<div class="premium-section" style="text-align:center;padding-top:60px">
    <h1 style="color:var(--gold);margin-bottom:20px">正在前往付款頁面...</h1>
    <p style="color:var(--text-dim);margin-bottom:28px">若未自動跳轉，請點擊下方按鈕。</p>

    <form id="ecpay-form" action="{{ $actionUrl }}" method="POST">
        @foreach($params as $key => $value)
            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
        @endforeach
        <button type="submit" class="btn btn-gold btn-xl">前往 ECPay 付款</button>
    </form>
</div>

<script>
    document.getElementById('ecpay-form').submit();
</script>
@endsection
