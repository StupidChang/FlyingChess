@extends('layouts.app')
@section('title','驗證電子信箱 — 情侶飛行棋')
@section('content')
<div class="form-page">
    <div class="form-card">
        <h1 style="font-size:1.5rem;color:var(--gold);margin-bottom:16px;text-align:center">驗證電子信箱</h1>

        @if(session('success'))
        <div class="toast toast-ok" style="margin-bottom:16px">
            {{ session('success') }}
        </div>
        @endif

        <p style="text-align:center;color:var(--text-dim);margin-bottom:24px;line-height:1.7">
            感謝您的註冊！<br>
            驗證信已寄送至您的電子信箱，請點擊信中的連結完成驗證。<br>
            若未收到，請檢查垃圾郵件資料夾，或點擊下方按鈕重新寄送。
        </p>

        <form action="{{ route('verification.send') }}" method="POST">
            @csrf
            <div class="form-actions">
                <button type="submit" class="btn btn-gold btn-full">重新寄送驗證信</button>
            </div>
        </form>

        <div style="text-align:center;margin-top:16px">
            <a href="{{ route('home') }}" class="btn btn-outline btn-full" style="margin-bottom:8px">先去玩，稍後再驗證</a>
        </div>

        <p style="text-align:center;margin-top:12px;font-size:.88rem;color:var(--text-dim)">
            <form action="{{ route('logout') }}" method="POST" style="display:inline">
                @csrf
                <button type="submit" style="background:none;border:none;color:var(--gold);cursor:pointer;font-size:.88rem">登出</button>
            </form>
        </p>
    </div>
</div>
@endsection

@section('scripts')
@if(session('success') && str_contains(session('success'), '註冊成功'))
<script>
if (typeof gtag !== 'undefined') {
    gtag('event', 'signup_completed');
}
</script>
@endif
@endsection
