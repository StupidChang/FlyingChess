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
                @include('admin._filter-clear', ['params' => ['filter']])
                @include('admin._filter-tab', ['param' => 'filter', 'value' => 'template', 'label' => '範本'])
                @include('admin._filter-tab', ['param' => 'filter', 'value' => 'default', 'label' => '預設'])
                @include('admin._filter-tab', ['param' => 'filter', 'value' => 'user', 'label' => '使用者建立'])
            </div>
            <form action="{{ route('admin.boards') }}" method="GET" class="admin-search">
                {{-- 篩選現在是複選,搜尋時要把整組帶著走 --}}
                @foreach((array) request('filter', []) as $f)
                <input type="hidden" name="filter[]" value="{{ $f }}">
                @endforeach
                <input type="text" name="q" value="{{ request('q') }}" placeholder="搜尋棋盤名稱…"
                       class="admin-search-input">
                <button type="submit" class="btn btn-sm">搜尋</button>
            </form>
        </div>

        @include('admin._per-page', ['paginator' => $boards, 'location' => 'top', 'showLinks' => false])
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        @include('admin._sort-header', ['key' => 'id', 'label' => 'ID'])
                        @include('admin._sort-header', ['key' => 'name', 'label' => '名稱'])
                        <th>建立者</th>
                        <th>屬性</th>
                        @include('admin._sort-header', ['key' => 'squares', 'label' => '格子數'])
                        @include('admin._sort-header', ['key' => 'created_at', 'label' => '建立時間'])

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
                            <a href="{{ route('admin.boards.edit', [$board, 'return' => request()->getQueryString()]) }}" class="btn btn-sm">編輯</a>
                            <a href="{{ route('boards.edit', $board) }}" class="btn btn-sm btn-outline" target="_blank">畫布</a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" style="text-align:center;padding:24px">沒有找到棋盤</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @include('admin._per-page', ['paginator' => $boards])
    </div>
</section>
@endsection
