@extends('layouts.app')

@section('title', '棋盤管理 — 後台')
@section('robots', 'noindex,nofollow')

@section('content')
@include('admin._nav')

<section class="section section--sm">
    <div class="container">
        <h1 style="margin-bottom:24px">棋盤管理</h1>

        <div class="admin-filters">
            <div class="admin-filter-tabs">
                @php $f = request('filter', 'all'); @endphp
                <a href="{{ route('admin.boards', ['filter' => 'all']) }}"
                   class="admin-filter-tab {{ $f === 'all' ? 'active' : '' }}">全部</a>
                <a href="{{ route('admin.boards', ['filter' => 'template']) }}"
                   class="admin-filter-tab {{ $f === 'template' ? 'active' : '' }}">範本</a>
                <a href="{{ route('admin.boards', ['filter' => 'default']) }}"
                   class="admin-filter-tab {{ $f === 'default' ? 'active' : '' }}">預設</a>
                <a href="{{ route('admin.boards', ['filter' => 'user']) }}"
                   class="admin-filter-tab {{ $f === 'user' ? 'active' : '' }}">使用者建立</a>
            </div>
            <form action="{{ route('admin.boards') }}" method="GET" class="admin-search">
                <input type="hidden" name="filter" value="{{ $f }}">
                <input type="text" name="q" value="{{ request('q') }}" placeholder="搜尋棋盤名稱…"
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
                        <th>建立者</th>
                        <th>屬性</th>
                        <th>格子數</th>
                        <th>建立時間</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($boards as $board)
                    <tr>
                        <td>{{ $board->id }}</td>
                        <td>{{ $board->name }}</td>
                        <td>{{ $board->user?->name ?? '—' }}</td>
                        <td>
                            @if($board->is_default) <span class="badge-admin">預設</span> @endif
                            @if($board->is_template) <span class="badge-truth">範本</span> @endif
                            @if($board->is_premium_template) <span class="badge-dare">付費</span> @endif
                        </td>
                        <td>{{ $board->squares_count ?? $board->squares()->count() }}</td>
                        <td>{{ $board->created_at->format('Y-m-d') }}</td>
                        <td>
                            <a href="{{ route('admin.boards.edit', $board) }}" class="btn btn-sm">編輯</a>
                            @if($board->user_id)
                            <a href="{{ route('boards.edit', $board) }}" class="btn btn-sm btn-outline" target="_blank">畫布</a>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" style="text-align:center;padding:24px">沒有找到棋盤</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div style="margin-top:16px">{{ $boards->links() }}</div>
    </div>
</section>
@endsection
