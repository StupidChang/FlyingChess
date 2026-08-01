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
                @include('admin._filter-clear', ['params' => ['tier', 'paid']])
                <span style="border-left:1px solid var(--border);margin:0 8px"></span>
                @foreach(\App\Models\WheelSegment::TIERS as $k => $v)
                @include('admin._filter-tab', ['param' => 'tier', 'value' => $k, 'label' => $v])
                @endforeach
                <span style="border-left:1px solid var(--border);margin:0 8px"></span>
                @include('admin._filter-tab', ['param' => 'paid', 'value' => '0', 'label' => '免費'])
                @include('admin._filter-tab', ['param' => 'paid', 'value' => '1', 'label' => '付費'])
            </div>
            <form action="{{ route('admin.wheel-segments') }}" method="GET" class="admin-search">
                {{-- 搜尋時把目前的篩選一起帶走,不然搜一次篩選就沒了 --}}
                @foreach((array) request('tier', []) as $v)
                <input type="hidden" name="tier[]" value="{{ $v }}">
                @endforeach
                @foreach((array) request('paid', []) as $v)
                <input type="hidden" name="paid[]" value="{{ $v }}">
                @endforeach
                <input type="text" name="q" value="{{ request('q') }}" placeholder="搜尋任務內容…"
                       class="admin-search-input">
                <button type="submit" class="btn btn-sm">搜尋</button>
            </form>
        </div>

        @include('admin._per-page', ['paginator' => $segments, 'location' => 'top', 'showLinks' => false])
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        @include('admin._sort-header', ['key' => 'id', 'label' => 'ID'])
                        @include('admin._sort-header', ['key' => 'tier', 'label' => '強度'])
                        @include('admin._sort-header', ['key' => 'content', 'label' => '內容'])
                        @include('admin._sort-header', ['key' => 'paid', 'label' => '收費'])
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($segments as $segment)
                    <tr>
                        <td>{{ $segment->id }}</td>
                        <td>
                            @include('admin._tier-badge', [
                                'key' => $segment->tier,
                                'label' => \App\Models\WheelSegment::TIERS[$segment->tier] ?? $segment->tier,
                            ])
                        </td>
                        <td style="max-width:400px">{{ Str::limit($segment->content, 80) }}</td>
                        <td>
                            @if($segment->is_paid)
                                <span class="badge-premium">付費</span>
                            @else
                                <span style="color:var(--text-dim)">免費</span>
                            @endif
                        </td>
                        <td style="white-space:nowrap">
                            <a href="{{ route('admin.wheel-segments.edit', [$segment, 'return' => request()->getQueryString()]) }}" class="btn btn-sm">編輯</a>
                            <form action="{{ route('admin.wheel-segments.destroy', $segment) }}" method="POST"
                                  style="display:inline" onsubmit="return confirm('確定刪除此任務？')">
                                @csrf @method('DELETE')
                                <input type="hidden" name="return" value="{{ request()->getQueryString() }}">
                                <button type="submit" class="btn btn-sm btn-outline" style="color:var(--accent)">刪除</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" style="text-align:center;padding:24px">沒有找到任務</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @include('admin._per-page', ['paginator' => $segments])
    </div>
</section>
@endsection
