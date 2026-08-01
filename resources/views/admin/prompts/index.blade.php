@extends('layouts.app')

@section('title', '題庫管理 — 後台')
@section('robots', 'noindex,nofollow')

@section('content')
@include('admin._nav')

<section class="section section--sm">
    <div class="container">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px">
            <h1>題庫管理</h1>
            <a href="{{ route('admin.prompts.create', ['game' => $game, 'pool' => $pool]) }}" class="btn">新增題目</a>
        </div>

        @if(session('success'))
        <div class="toast toast-ok" style="margin-bottom:16px">{{ session('success') }}</div>
        @endif

        <div class="admin-filters">
            <div class="admin-filter-tabs">
                @foreach(\App\Models\GamePrompt::GAMES as $key => $label)
                <a href="{{ route('admin.prompts', ['game' => $key]) }}"
                   class="admin-filter-tab {{ $game === $key ? 'active' : '' }}">{{ $label }}</a>
                @endforeach
                <span style="border-left:1px solid var(--border);margin:0 8px"></span>
                {{-- 分類與收費可以複選;遊戲只能單選 —— 分類的合法值取決於遊戲。 --}}
                @foreach(\App\Models\GamePrompt::POOLS[$game] as $k => $v)
                @include('admin._filter-tab', ['param' => 'pool', 'value' => $k, 'label' => $v])
                @endforeach
                <span style="border-left:1px solid var(--border);margin:0 8px"></span>
                @include('admin._filter-tab', ['param' => 'paid', 'value' => '0', 'label' => '免費'])
                @include('admin._filter-tab', ['param' => 'paid', 'value' => '1', 'label' => '付費'])
                @include('admin._filter-clear', ['params' => ['pool', 'paid']])
            </div>
            <form action="{{ route('admin.prompts') }}" method="GET" class="admin-search">
                <input type="hidden" name="game" value="{{ $game }}">
                @foreach((array) request('pool', []) as $v)
                <input type="hidden" name="pool[]" value="{{ $v }}">
                @endforeach
                @foreach((array) request('paid', []) as $v)
                <input type="hidden" name="paid[]" value="{{ $v }}">
                @endforeach
                <input type="text" name="q" value="{{ request('q') }}" placeholder="搜尋題目內容…"
                       class="admin-search-input">
                <button type="submit" class="btn btn-sm">搜尋</button>
            </form>
        </div>

        @if($isEmpty)
        {{-- 資料表空的時候遊戲是用程式碼裡的預設題庫在跑,不是壞掉。 --}}
        <div class="admin-note">
            <p>這個遊戲還沒有匯入題目,目前用的是程式碼裡的預設題庫。匯入之後就能在這裡編輯。</p>
            <form action="{{ route('admin.prompts.import') }}" method="POST">
                @csrf
                <input type="hidden" name="game" value="{{ $game }}">
                <button type="submit" class="btn btn-sm">匯入預設題目</button>
            </form>
        </div>
        @endif

        @include('admin._per-page', ['paginator' => $prompts, 'location' => 'top', 'showLinks' => false])
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        @include('admin._sort-header', ['key' => 'id', 'label' => 'ID'])
                        @include('admin._sort-header', ['key' => 'pool', 'label' => '分類'])
                        @include('admin._sort-header', ['key' => 'content', 'label' => '內容'])
                        @include('admin._sort-header', ['key' => 'paid', 'label' => '收費'])
                        @include('admin._sort-header', ['key' => 'sort_order', 'label' => '排序'])
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($prompts as $prompt)
                    <tr>
                        <td>{{ $prompt->id }}</td>
                        <td>
                            @include('admin._tier-badge', [
                                'key' => $prompt->pool,
                                'label' => \App\Models\GamePrompt::POOLS[$game][$prompt->pool] ?? $prompt->pool,
                            ])
                        </td>
                        <td style="max-width:400px">{{ Str::limit($prompt->content, 80) }}</td>
                        <td>
                            @if($prompt->is_paid)
                                <span class="badge-premium">付費</span>
                            @else
                                <span style="color:var(--text-dim)">免費</span>
                            @endif
                        </td>
                        <td>{{ $prompt->sort_order }}</td>
                        <td style="white-space:nowrap">
                            <a href="{{ route('admin.prompts.edit', [$prompt, 'return' => request()->getQueryString()]) }}" class="btn btn-sm">編輯</a>
                            <form action="{{ route('admin.prompts.destroy', $prompt) }}" method="POST"
                                  style="display:inline" onsubmit="return confirm('確定刪除此題目？')">
                                @csrf @method('DELETE')
                                <input type="hidden" name="return" value="{{ request()->getQueryString() }}">
                                <button type="submit" class="btn btn-sm btn-outline" style="color:var(--accent)">刪除</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" style="text-align:center;padding:24px">沒有找到題目</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @include('admin._per-page', ['paginator' => $prompts])
    </div>
</section>
@endsection
