@extends('layouts.app')

@section('title', '編輯會員 — 後台')
@section('robots', 'noindex,nofollow')

@section('content')
@include('admin._nav')

<section class="section section--sm">
    <div class="container" style="max-width:640px">
        <h1 style="margin-bottom:24px">編輯會員：{{ $user->name }}</h1>

        @if($errors->any())
        <div class="toast toast-err" style="margin-bottom:16px">
            @foreach($errors->all() as $e) <div>{{ $e }}</div> @endforeach
        </div>
        @endif

        <div style="margin-bottom:24px;padding:16px;background:var(--surface);border:1px solid var(--border);border-radius:var(--radius)">
            <p><strong>Email：</strong>{{ $user->email }}</p>
            <p><strong>註冊時間：</strong>{{ $user->created_at->format('Y-m-d H:i') }}</p>
            <p><strong>棋盤數：</strong>{{ $user->boards()->count() }}</p>
        </div>

        <form action="{{ route('admin.users.update', $user) }}" method="POST" class="admin-form">
            @csrf @method('PATCH')

            <div class="form-group">
                <label class="form-check">
                    <input type="hidden" name="is_admin" value="0">
                    <input type="checkbox" name="is_admin" value="1"
                           {{ old('is_admin', $user->is_admin) ? 'checked' : '' }}>
                    管理員權限
                </label>
            </div>

            <div class="form-group">
                <label for="premium_expires_at">Premium 到期日</label>
                <input type="datetime-local" id="premium_expires_at" name="premium_expires_at"
                       class="form-input"
                       value="{{ old('premium_expires_at', $user->premium_expires_at?->format('Y-m-d\TH:i')) }}">
                <p style="font-size:.8rem;color:var(--text-dim);margin-top:4px">留空表示無 Premium 資格</p>
            </div>

            <div style="display:flex;gap:12px;margin-top:24px">
                <button type="submit" class="btn">儲存</button>
                <a href="{{ route('admin.users') }}" class="btn btn-outline">返回列表</a>
            </div>
        </form>
    </div>
</section>
@endsection
