@extends('layouts.app')
@section('title','新建棋盤')
@section('content')
<div class="container form-page">
    <h1>建立新棋盤</h1>
    <p style="color:var(--text-dim);margin-bottom:24px">將以預設棋盤的格子內容為基礎，建立後可自由編輯每個格子</p>
    <form action="{{ route('boards.store') }}" method="POST" class="form-card">
        @csrf
        <div class="form-group">
            <label>棋盤名稱</label>
            <input type="text" name="name" class="form-control" placeholder="例：我們的情侶棋盤" maxlength="100" required>
        </div>
        <div class="form-group">
            <label>說明（選填）</label>
            <textarea name="description" class="form-control" rows="3" maxlength="500" placeholder="棋盤說明..."></textarea>
        </div>
        <div class="form-actions">
            <button class="btn btn-gold">建立並開始編輯</button>
            <a href="{{ route('home') }}" class="btn btn-outline">取消</a>
        </div>
    </form>
</div>
@endsection
