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
                @include('admin._filter-clear', ['params' => ['category', 'audience', 'level', 'paid']])
                <span style="border-left:1px solid var(--border);margin:0 8px"></span>
                @foreach(\App\Models\TruthDareCard::CATEGORIES as $k => $v)
                @include('admin._filter-tab', ['param' => 'category', 'value' => $k, 'label' => $v])
                @endforeach
                <span style="border-left:1px solid var(--border);margin:0 8px"></span>
                @foreach(\App\Models\TruthDareCard::AUDIENCES as $k => $v)
                @include('admin._filter-tab', ['param' => 'audience', 'value' => $k, 'label' => $v])
                @endforeach
                <span style="border-left:1px solid var(--border);margin:0 8px"></span>
                @foreach(\App\Models\TruthDareCard::LEVELS as $k => $v)
                @include('admin._filter-tab', ['param' => 'level', 'value' => $k, 'label' => $v])
                @endforeach
                <span style="border-left:1px solid var(--border);margin:0 8px"></span>
                @include('admin._filter-tab', ['param' => 'paid', 'value' => '0', 'label' => '免費'])
                @include('admin._filter-tab', ['param' => 'paid', 'value' => '1', 'label' => '付費'])
            </div>
            <form action="{{ route('admin.cards') }}" method="GET" class="admin-search">
                {{-- 搜尋時把目前的篩選一起帶走,不然搜一次篩選就沒了 --}}
                @foreach((array) request('category', []) as $v)
                <input type="hidden" name="category[]" value="{{ $v }}">
                @endforeach
                @foreach((array) request('audience', []) as $v)
                <input type="hidden" name="audience[]" value="{{ $v }}">
                @endforeach
                @foreach((array) request('level', []) as $v)
                <input type="hidden" name="level[]" value="{{ $v }}">
                @endforeach
                @foreach((array) request('paid', []) as $v)
                <input type="hidden" name="paid[]" value="{{ $v }}">
                @endforeach
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
                        @include('admin._sort-header', ['key' => 'level', 'label' => '尺度'])
                        @include('admin._sort-header', ['key' => 'paid', 'label' => '收費'])
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
                            @include('admin._tier-badge', [
                                'key' => $card->level,
                                'label' => \App\Models\TruthDareCard::LEVELS[$card->level] ?? $card->level,
                            ])
                        </td>
                        <td>
                            @if($card->is_paid)
                                <span class="badge-premium">付費</span>
                            @else
                                <span style="color:var(--text-dim)">免費</span>
                            @endif
                        </td>
                        <td style="white-space:nowrap">
                            <a href="{{ route('admin.cards.edit', [$card, 'return' => request()->getQueryString()]) }}" class="btn btn-sm">編輯</a>
                            <form action="{{ route('admin.cards.destroy', $card) }}" method="POST"
                                  style="display:inline" onsubmit="return confirm('確定刪除此卡片？')">
                                @csrf @method('DELETE')
                                <input type="hidden" name="return" value="{{ request()->getQueryString() }}">
                                <button type="submit" class="btn btn-sm btn-outline" style="color:var(--accent)">刪除</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" style="text-align:center;padding:24px">沒有找到卡片</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @include('admin._per-page', ['paginator' => $cards])
    </div>
</section>
@endsection
