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
                   class="admin-filter-tab {{ !request('category') && !request('tier') ? 'active' : '' }}">全部</a>
                @foreach(['truth' => '真心話', 'dare' => '大冒險', 'couple' => '情侶', 'party' => '派對'] as $k => $v)
                <a href="{{ route('admin.cards', ['category' => $k]) }}"
                   class="admin-filter-tab {{ request('category') === $k ? 'active' : '' }}">{{ $v }}</a>
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

        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>分類</th>
                        <th>內容</th>
                        <th>等級</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($cards as $card)
                    <tr>
                        <td>{{ $card->id }}</td>
                        <td>
                            <span class="badge-{{ $card->category }}">
                                {{ ['truth'=>'真心話','dare'=>'大冒險','couple'=>'情侶','party'=>'派對'][$card->category] ?? $card->category }}
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
                    <tr><td colspan="5" style="text-align:center;padding:24px">沒有找到卡片</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div style="margin-top:16px">{{ $cards->links() }}</div>
    </div>
</section>
@endsection
