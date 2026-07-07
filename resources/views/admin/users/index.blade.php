@extends('layouts.app')

@section('title', '會員管理 — 後台')
@section('robots', 'noindex,nofollow')

@section('content')
@include('admin._nav')

<section class="section section--sm">
    <div class="container">
        <h1 style="margin-bottom:24px">會員管理</h1>

        @if(session('success'))
        <div class="toast toast-ok" style="margin-bottom:16px">{{ session('success') }}</div>
        @endif
        @if($errors->any())
        <div class="toast toast-err" style="margin-bottom:16px">
            @foreach($errors->all() as $e) <div>{{ $e }}</div> @endforeach
        </div>
        @endif

        <div class="admin-filters">
            <div class="admin-filter-tabs">
                @php $f = request('filter', 'all'); @endphp
                <a href="{{ route('admin.users', ['filter' => 'all']) }}"
                   class="admin-filter-tab {{ $f === 'all' ? 'active' : '' }}">全部</a>
                <a href="{{ route('admin.users', ['filter' => 'premium']) }}"
                   class="admin-filter-tab {{ $f === 'premium' ? 'active' : '' }}">付費會員</a>
                <a href="{{ route('admin.users', ['filter' => 'admin']) }}"
                   class="admin-filter-tab {{ $f === 'admin' ? 'active' : '' }}">管理員</a>
                <a href="{{ route('admin.users', ['filter' => 'banned']) }}"
                   class="admin-filter-tab {{ $f === 'banned' ? 'active' : '' }}">已封鎖</a>
            </div>
            <form action="{{ route('admin.users') }}" method="GET" class="admin-search">
                <input type="hidden" name="filter" value="{{ $f }}">
                <input type="text" name="q" value="{{ request('q') }}" placeholder="搜尋名稱或 Email…"
                       class="admin-search-input">
                <button type="submit" class="btn btn-sm">搜尋</button>
            </form>
        </div>

        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>名稱</th>
                        <th>Email</th>
                        <th>棋盤數</th>
                        <th>狀態</th>
                        <th>註冊時間</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                    <tr>
                        <td>{{ $user->id }}</td>
                        <td>{{ $user->name }}</td>
                        <td>{{ $user->email }}</td>
                        <td>{{ $user->boards_count }}</td>
                        <td>
                            @if($user->isAdmin()) <span class="badge-admin">Admin</span> @endif
                            @if($user->isPremium()) <span class="badge-premium">Premium</span> @endif
                            @if($user->isBanned()) <span class="badge-admin" style="background:#dc2626">已封鎖</span> @endif
                        </td>
                        <td>{{ $user->created_at->format('Y-m-d H:i') }}</td>
                        <td>
                            <div style="display:flex;gap:6px;flex-wrap:wrap">
                                <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm">編輯</a>
                                @unless($user->isAdmin() || $user->id === auth()->id())
                                    @if($user->isBanned())
                                    <form action="{{ route('admin.users.unban', $user) }}" method="POST"
                                          onsubmit="return confirm('確定要解除封鎖「{{ $user->name }}」嗎？')">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline">解封</button>
                                    </form>
                                    @else
                                    <form action="{{ route('admin.users.ban', $user) }}" method="POST"
                                          onsubmit="return confirm('確定要封鎖「{{ $user->name }}」嗎？被封鎖後將無法登入。')">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline">封鎖</button>
                                    </form>
                                    @endif
                                    <form action="{{ route('admin.users.destroy', $user) }}" method="POST"
                                          onsubmit="return confirm('確定要刪除「{{ $user->name }}」嗎？此操作會連帶刪除其建立的棋盤，且無法復原。')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline" style="color:#dc2626;border-color:#dc2626">刪除</button>
                                    </form>
                                @endunless
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" style="text-align:center;padding:24px">沒有找到會員</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div style="margin-top:16px">{{ $users->links() }}</div>
    </div>
</section>
@endsection
