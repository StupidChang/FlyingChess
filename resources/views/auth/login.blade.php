@extends('layouts.app')
@section('title', '登入 — 情侶飛行棋')
@section('robots', 'noindex,nofollow')
@section('canonical', route('login'))
@section('content')
<div class="form-page">
    <div class="form-card">
        <h1 style="font-size:1.5rem;color:var(--gold);margin-bottom:24px;text-align:center">會員登入</h1>

        @if($errors->any())
        <div class="toast toast-err" style="margin-bottom:16px">
            {{ $errors->first() }}
        </div>
        @endif

        <form action="{{ route('login') }}" method="POST">
            @csrf
            <div class="form-group">
                <label>電子信箱</label>
                <input type="email" name="email" class="form-control"
                       value="{{ old('email') }}" required autocomplete="email">
            </div>
            <div class="form-group">
                <label>密碼</label>
                <input type="password" name="password" class="form-control"
                       required autocomplete="current-password">
            </div>
            <div class="form-group" style="display:flex;align-items:center;gap:8px">
                <input type="checkbox" name="remember" id="remember" value="1">
                <label for="remember" style="margin:0;font-size:.9rem;color:var(--text-dim)">記住我</label>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-gold btn-full">登入</button>
            </div>
            <div style="text-align:right;margin-top:8px">
                <a href="{{ route('password.request') }}" style="color:var(--text-dim);font-size:.85rem">忘記密碼？</a>
            </div>
        </form>

        <p style="text-align:center;margin-top:20px;font-size:.88rem;color:var(--text-dim)">
            還沒有帳號？
            <a href="{{ route('register') }}" style="color:var(--gold)">立即註冊</a>
        </p>
    </div>
</div>
@endsection
