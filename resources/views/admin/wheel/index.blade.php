@extends('layouts.app')

@section('title', '轉盤管理 — 後台')
@section('robots', 'noindex,nofollow')

@section('content')
@include('admin._nav')

<section class="section section--sm">
    <div class="container">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px">
            <h1>轉盤任務管理</h1>
            <a href="{{ route('admin.wheel-segments.create') }}" class="btn">新增任務</a>
        </div>

        <div class="admin-filters">
            <div class="admin-filter-tabs">
                <a href="{{ route('admin.wheel-segments') }}"
                   class="admin-filter-tab {{ !request('tier') ? 'active' : '' }}">全部</a>
                @foreach(['mild' => '輕鬆', 'medium' => '親密', 'intense' => '大膽'] as $k => $v)
                <a href="{{ route('admin.wheel-segments', ['tier' => $k]) }}"
                   class="admin-filter-tab {{ request('tier') === $k ? 'active' : '' }}">{{ $v }}</a>
                @endforeach
            </div>
            <form action="{{ route('admin.wheel-segments') }}" method="GET" class="admin-search">
                <input type="text" name="q" value="{{ request('q') }}" placeholder="搜尋任務內容…"
                       class="admin-search-input">
                <button type="submit" class="btn btn-sm">搜尋</button>
            </form>
        </div>

        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>強度</th>
                        <th>內容</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($segments as $segment)
                    <tr>
                        <td>{{ $segment->id }}</td>
                        <td>
                            <span class="badge-{{ $segment->tier }}">
                                {{ ['mild'=>'輕鬆','medium'=>'親密','intense'=>'大膽'][$segment->tier] ?? $segment->tier }}
                            </span>
                        </td>
                        <td style="max-width:400px">{{ Str::limit($segment->content, 80) }}</td>
                        <td style="white-space:nowrap">
                            <a href="{{ route('admin.wheel-segments.edit', $segment) }}" class="btn btn-sm">編輯</a>
                            <form action="{{ route('admin.wheel-segments.destroy', $segment) }}" method="POST"
                                  style="display:inline" onsubmit="return confirm('確定刪除此任務？')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline" style="color:var(--accent)">刪除</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="4" style="text-align:center;padding:24px">沒有找到任務</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div style="margin-top:16px">{{ $segments->links() }}</div>
    </div>
</section>
@endsection
