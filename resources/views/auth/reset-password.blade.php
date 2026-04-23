@extends('layouts.app')
@section('title','重設密碼 — 情侶飛行棋')
@section('meta_description','重設您的情侶飛行棋帳號密碼')
@section('og_description','重設您的情侶飛行棋帳號密碼')
@section('content')
<div class="form-page">
    <div class="form-card">
        <h1 style="font-size:1.5rem;color:var(--gold);margin-bottom:8px;text-align:center">重設密碼</h1>
        <p style="text-align:center;color:var(--text-dim);font-size:.9rem;margin-bottom:24px">
            請輸入您的新密碼
        </p>

        @if($errors->any())
        <div class="toast toast-err" style="margin-bottom:16px">
            {{ $errors->first() }}
        </div>
        @endif

        <form action="{{ route('password.update') }}" method="POST">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <div class="form-group">
                <label>電子信箱</label>
                <input type="email" name="email" class="form-control"
                       value="{{ old('email', $email) }}" required autocomplete="email">
            </div>
            <div class="form-group">
                <label>新密碼（至少 8 字元）</label>
                <input type="password" name="password" class="form-control"
                       required minlength="8" autocomplete="new-password">
            </div>
            <div class="form-group">
                <label>確認新密碼</label>
                <input type="password" name="password_confirmation" class="form-control"
                       required autocomplete="new-password">
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-gold btn-full">儲存新密碼</button>
            </div>
        </form>

        <p style="text-align:center;margin-top:20px;font-size:.88rem;color:var(--text-dim)">
            <a href="{{ route('login') }}" style="color:var(--gold)">返回登入</a>
        </p>
    </div>
</div>
@endsection
