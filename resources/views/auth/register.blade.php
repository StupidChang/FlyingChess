@extends('layouts.app')
@section('title', '註冊 — 情侶飛行棋')
@section('robots', 'noindex,nofollow')
@section('canonical', route('register'))
@section('content')
<div class="form-page">
    <div class="form-card">
        <h1 style="font-size:1.5rem;color:var(--gold);margin-bottom:24px;text-align:center">建立帳號</h1>

        @if($errors->any())
        <div class="toast toast-err" style="margin-bottom:16px">
            {{ $errors->first() }}
        </div>
        @endif

        <form action="{{ route('register') }}" method="POST">
            @csrf
            <div class="form-group">
                <label>暱稱</label>
                <input type="text" name="name" class="form-control"
                       value="{{ old('name') }}" required maxlength="50">
            </div>
            <div class="form-group">
                <label>電子信箱</label>
                <input type="email" name="email" class="form-control"
                       value="{{ old('email') }}" required autocomplete="email">
            </div>
            <div class="form-group">
                <label>密碼（至少 8 字元）</label>
                <input type="password" name="password" class="form-control"
                       required minlength="8" autocomplete="new-password">
            </div>
            <div class="form-group">
                <label>確認密碼</label>
                <input type="password" name="password_confirmation" class="form-control"
                       required autocomplete="new-password">
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-gold btn-full">建立帳號</button>
            </div>
        </form>

        <p style="text-align:center;margin-top:20px;font-size:.88rem;color:var(--text-dim)">
            已有帳號？
            <a href="{{ route('login') }}" style="color:var(--gold)">點此登入</a>
        </p>
    </div>
</div>
@endsection
