@extends('layouts.app')

@section('title', '卡片管理 — 後台')
@section('robots', 'noindex,nofollow')

@section('content')
@include('admin._nav')

<section class="section section--sm">
    <div class="container">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px">
            <h1>卡片管理</h1>
            <a href="{{ route('admin.cards.create') }}" class="btn">新增卡片</a>
        </div>

        <div class="admin-filters">
            <div class="admin-filter-tabs">
                <a href="{{ route('admin.cards') }}"
                   class="admin-filter-tab {{ !request('category') && !request('tier') && !request('audience') ? 'active' : '' }}">全部</a>
                {{-- 類型與適用人數是兩個獨立的軸,篩選也要分兩排 --}}
                @foreach(\App\Models\TruthDareCard::CATEGORIES as $k => $v)
                <a href="{{ route('admin.cards', ['category' => $k, 'audience' => request('audience')]) }}"
                   class="admin-filter-tab {{ request('category') === $k ? 'active' : '' }}">{{ $v }}</a>
                @endforeach
                <span style="border-left:1px solid var(--border);margin:0 8px"></span>
                @foreach(\App\Models\TruthDareCard::AUDIENCES as $k => $v)
                <a href="{{ route('admin.cards', ['audience' => $k, 'category' => request('category')]) }}"
                   class="admin-filter-tab {{ request('audience') === $k ? 'active' : '' }}">{{ $v }}</a>
                @endforeach
                <span style="border-left:1px solid var(--border);margin:0 8px"></span>
                <a href="{{ route('admin.cards', ['tier' => 'free']) }}"
                   class="admin-filter-tab {{ request('tier') === 'free' ? 'active' : '' }}">一般</a>
                <a href="{{ route('admin.cards', ['tier' => 'premium']) }}"
                   class="admin-filter-tab {{ request('tier') === 'premium' ? 'active' : '' }}">🔞 18禁</a>
            </div>
            <form action="{{ route('admin.cards') }}" method="GET" class="admin-search">
                <input type="text" name="q" value="{{ request('q') }}" placeholder="搜尋卡片內容…"
                       class="admin-search-input">
                <button type="submit" class="btn btn-sm">搜尋</button>
            </form>
        </div>

        @include('admin._per-page', ['paginator' => $cards, 'location' => 'top', 'showLinks' => false])
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        @include('admin._sort-header', ['key' => 'id', 'label' => 'ID'])
                        @include('admin._sort-header', ['key' => 'category', 'label' => '類型'])
                        @include('admin._sort-header', ['key' => 'audience', 'label' => '適用'])
                        @include('admin._sort-header', ['key' => 'content', 'label' => '內容'])
                        @include('admin._sort-header', ['key' => 'tier', 'label' => '等級'])
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($cards as $card)
                    <tr>
                        <td>{{ $card->id }}</td>
                        <td>
                            <span class="badge-{{ $card->category }}">
                                {{ \App\Models\TruthDareCard::CATEGORIES[$card->category] ?? $card->category }}
                            </span>
                        </td>
                        <td>
                            <span class="badge-aud badge-aud--{{ $card->audience }}">
                                {{ \App\Models\TruthDareCard::AUDIENCES[$card->audience] ?? $card->audience }}
                            </span>
                        </td>
                        <td style="max-width:400px">{{ Str::limit($card->content, 80) }}</td>
                        <td>
                            @if($card->tier === 'premium')
                                <span class="badge-dare">18禁</span>
                            @else
                                <span style="color:var(--text-dim)">一般</span>
                            @endif
                        </td>
                        <td style="white-space:nowrap">
                            <a href="{{ route('admin.cards.edit', $card) }}" class="btn btn-sm">編輯</a>
                            <form action="{{ route('admin.cards.destroy', $card) }}" method="POST"
                                  style="display:inline" onsubmit="return confirm('確定刪除此卡片？')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline" style="color:var(--accent)">刪除</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" style="text-align:center;padding:24px">沒有找到卡片</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @include('admin._per-page', ['paginator' => $cards])
    </div>
</section>
@endsection
