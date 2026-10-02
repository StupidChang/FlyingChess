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
                  data-confirm="確定要解除封鎖「{{ $user->name }}」嗎？"
                  onsubmit="return confirm(this.dataset.confirm)">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline">解除封鎖</button>
            </form>
            @else
            <form action="{{ route('admin.users.ban', $user) }}" method="POST"
                  data-confirm="確定要封鎖「{{ $user->name }}」嗎？被封鎖後將無法登入。"
                  onsubmit="return confirm(this.dataset.confirm)">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline">封鎖帳號</button>
            </form>
            @endif
            <form action="{{ route('admin.users.destroy', $user) }}" method="POST"
                  data-confirm="確定要刪除「{{ $user->name }}」嗎？此操作會連帶刪除其建立的棋盤，且無法復原。"
                  onsubmit="return confirm(this.dataset.confirm)">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-sm btn-outline" style="color:#dc2626;border-color:#dc2626">刪除帳號</button>
            </form>
        </div>
        @endunless

        <form action="{{ route('admin.users.update', $user) }}" method="POST" class="admin-form">
            @csrf @method('PATCH')
            {{-- 帶著使用者原本停在列表的哪一頁、哪個篩選、哪個排序,存檔後照原樣
                 回去 —— 不然改一筆就要重找一次。 --}}
            <input type="hidden" name="return" value="{{ http_build_query($return ?? []) }}">

            <div class="form-group">
                <label for="name">暱稱</label>
                <input type="text" id="name" name="name" maxlength="50" required class="form-input"
                       value="{{ old('name', $user->name) }}">
            </div>

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
                <a href="{{ route('admin.users', $return ?? []) }}" class="btn btn-outline">返回列表</a>
            </div>
        </form>

        <h2 style="margin:40px 0 16px;font-size:1.15rem">發送站內通知</h2>
        @include('admin.users._notify-form', ['action' => route('admin.users.notify', $user)])

        <h2 style="margin:40px 0 12px;font-size:1.15rem">最近收到的通知</h2>
        @forelse($notifications as $n)
        @php $nv = \App\Notifications\SiteMessage::present($n); @endphp
        <div style="padding:12px 14px;margin-bottom:8px;background:var(--surface);border:1px solid var(--border);border-radius:var(--radius)">
            <div style="display:flex;justify-content:space-between;gap:12px">
                <strong>{{ $nv['title'] }}</strong>
                <span style="font-size:.78rem;color:var(--text-dim);white-space:nowrap">
                    {{ $n->created_at->format('Y-m-d H:i') }} · {{ $n->read_at ? '已讀' : '未讀' }}
                </span>
            </div>
            <p style="font-size:.85rem;color:var(--text-dim);margin:6px 0 0">{!! nl2br(e($nv['body'])) !!}</p>
        </div>
        @empty
        <p style="color:var(--text-dim);font-size:.9rem">還沒有任何通知。</p>
        @endforelse
    </div>
</section>
@endsection
