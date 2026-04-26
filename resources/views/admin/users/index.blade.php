@extends('layouts.app')

@section('title', '會員管理 — 後台')
@section('robots', 'noindex,nofollow')

@section('content')
@include('admin._nav')

<section class="section section--sm">
    <div class="container">
        <h1 style="margin-bottom:24px">會員管理</h1>

        <div class="admin-filters">
            <div class="admin-filter-tabs">
                @php $f = request('filter', 'all'); @endphp
                <a href="{{ route('admin.users', ['filter' => 'all']) }}"
                   class="admin-filter-tab {{ $f === 'all' ? 'active' : '' }}">全部</a>
                <a href="{{ route('admin.users', ['filter' => 'premium']) }}"
                   class="admin-filter-tab {{ $f === 'premium' ? 'active' : '' }}">付費會員</a>
                <a href="{{ route('admin.users', ['filter' => 'admin']) }}"
                   class="admin-filter-tab {{ $f === 'admin' ? 'active' : '' }}">管理員</a>
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
                        </td>
                        <td>{{ $user->created_at->format('Y-m-d H:i') }}</td>
                        <td><a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm">編輯</a></td>
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
