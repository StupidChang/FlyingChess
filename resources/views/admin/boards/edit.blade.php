@extends('layouts.app')

@section('title', '編輯棋盤 — 後台')
@section('robots', 'noindex,nofollow')

@section('content')
@include('admin._nav')

<section class="section section--sm">
    <div class="container" style="max-width:640px">
        <h1 style="margin-bottom:24px">編輯棋盤：{{ $board->name }}</h1>

        @if($errors->any())
        <div class="toast toast-err" style="margin-bottom:16px">
            @foreach($errors->all() as $e) <div>{{ $e }}</div> @endforeach
        </div>
        @endif

        <form action="{{ route('admin.boards.update', $board) }}" method="POST" class="admin-form">
            @csrf @method('PATCH')

            <div class="form-group">
                <label for="name">名稱</label>
                <input type="text" id="name" name="name" value="{{ old('name', $board->name) }}"
                       class="form-input" required maxlength="100">
            </div>

            <div class="form-group">
                <label for="description">描述</label>
                <textarea id="description" name="description" class="form-input"
                          rows="3" maxlength="500">{{ old('description', $board->description) }}</textarea>
            </div>

            <div class="form-group">
                <label class="form-check">
                    <input type="hidden" name="is_default" value="0">
                    <input type="checkbox" name="is_default" value="1"
                           {{ old('is_default', $board->is_default) ? 'checked' : '' }}>
                    設為預設棋盤（全站唯一）
                </label>
            </div>

            <div class="form-group">
                <label class="form-check">
                    <input type="hidden" name="is_template" value="0">
                    <input type="checkbox" name="is_template" value="1"
                           {{ old('is_template', $board->is_template) ? 'checked' : '' }}>
                    公開範本
                </label>
            </div>

            <div class="form-group">
                <label class="form-check">
                    <input type="hidden" name="is_premium_template" value="0">
                    <input type="checkbox" name="is_premium_template" value="1"
                           {{ old('is_premium_template', $board->is_premium_template) ? 'checked' : '' }}>
                    付費會員範本
                </label>
            </div>

            <div style="display:flex;gap:12px;margin-top:24px">
                <button type="submit" class="btn">儲存</button>
                <a href="{{ route('admin.boards') }}" class="btn btn-outline">返回列表</a>
            </div>
        </form>

        <div style="margin-top:32px;padding-top:24px;border-top:1px solid var(--border)">
            <p style="color:var(--text-dim);font-size:.9rem">
                分享碼：<code>{{ $board->share_code }}</code> ·
                畫布：{{ $board->canvas_rows }}×{{ $board->canvas_cols }} ·
                格子數：{{ $board->squares()->count() }}
            </p>
        </div>
    </div>
</section>
@endsection
