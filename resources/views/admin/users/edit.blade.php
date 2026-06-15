@extends('layouts.app')

@section('title', '編輯會員 — 後台')
@section('robots', 'noindex,nofollow')

@section('content')
@include('admin._nav')

<section class="section section--sm">
    <div class="container" style="max-width:640px">
        <h1 style="margin-bottom:24px">編輯會員：{{ $user->name }}</h1>

        @if(session('success'))
        <div class="toast toast-ok" style="margin-bottom:16px">{{ session('success') }}</div>
        @endif
        @if($errors->any())
        <div class="toast toast-err" style="margin-bottom:16px">
            @foreach($errors->all() as $e) <div>{{ $e }}</div> @endforeach
        </div>
        @endif

        <div style="margin-bottom:24px;padding:16px;background:var(--surface);border:1px solid var(--border);border-radius:var(--radius)">
            <p><strong>Email：</strong>{{ $user->email }}</p>
            <p><strong>註冊時間：</strong>{{ $user->created_at->format('Y-m-d H:i') }}</p>
            <p><strong>棋盤數：</strong>{{ $user->boards()->count() }}</p>
            <p><strong>帳號狀態：</strong>
                @if($user->isBanned())
                    <span class="badge-admin" style="background:#dc2626">已封鎖</span>
                    <span style="font-size:.85rem;color:var(--text-dim)">（{{ $user->banned_at?->format('Y-m-d H:i') }}）</span>
                @else
                    正常
                @endif
            </p>
        </div>

        @unless($user->isAdmin() || $user->id === auth()->id())
        <div style="display:flex;gap:12px;margin-bottom:24px">
            @if($user->isBanned())
            <form action="{{ route('admin.users.unban', $user) }}" method="POST"
                  onsubmit="return confirm('確定要解除封鎖「{{ $user->name }}」嗎？')">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline">解除封鎖</button>
            </form>
            @else
            <form action="{{ route('admin.users.ban', $user) }}" method="POST"
                  onsubmit="return confirm('確定要封鎖「{{ $user->name }}」嗎？被封鎖後將無法登入。')">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline">封鎖帳號</button>
            </form>
            @endif
            <form action="{{ route('admin.users.destroy', $user) }}" method="POST"
                  onsubmit="return confirm('確定要刪除「{{ $user->name }}」嗎？此操作會連帶刪除其建立的棋盤，且無法復原。')">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-sm btn-outline" style="color:#dc2626;border-color:#dc2626">刪除帳號</button>
            </form>
        </div>
        @endunless

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
