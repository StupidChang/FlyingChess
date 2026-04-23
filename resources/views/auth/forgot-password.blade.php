@extends('layouts.app')
@section('title','忘記密碼 — 情侶飛行棋')
@section('meta_description','重設情侶飛行棋帳號密碼，輸入電子信箱，我們將寄送重設連結給您。')
@section('og_description','重設情侶飛行棋帳號密碼')
@section('content')
<div class="form-page">
    <div class="form-card">
        <h1 style="font-size:1.5rem;color:var(--gold);margin-bottom:8px;text-align:center">忘記密碼</h1>
        <p style="text-align:center;color:var(--text-dim);font-size:.9rem;margin-bottom:24px">
            輸入您的電子信箱，我們將寄送密碼重設連結
        </p>

        @if(session('success'))
        <div class="toast toast-ok" style="margin-bottom:16px">
            {{ session('success') }}
        </div>
        @endif

        @if($errors->any())
        <div class="toast toast-err" style="margin-bottom:16px">
            {{ $errors->first() }}
        </div>
        @endif

        <form action="{{ route('password.email') }}" method="POST">
            @csrf
            <div class="form-group">
                <label>電子信箱</label>
                <input type="email" name="email" class="form-control"
                       value="{{ old('email') }}" required autocomplete="email"
                       placeholder="輸入註冊時使用的信箱">
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-gold btn-full">寄送重設連結</button>
            </div>
        </form>

        <p style="text-align:center;margin-top:20px;font-size:.88rem;color:var(--text-dim)">
            想起密碼了？
            <a href="{{ route('login') }}" style="color:var(--gold)">返回登入</a>
        </p>
    </div>
</div>
@endsection
