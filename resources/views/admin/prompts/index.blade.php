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
            </div>
            <form action="{{ route('admin.prompts') }}" method="GET" class="admin-search">
                <input type="hidden" name="game" value="{{ $game }}">
                <select name="pool" class="admin-search-input">
                    <option value="">全部分類</option>
                    @foreach(\App\Models\GamePrompt::POOLS[$game] as $key => $label)
                    <option value="{{ $key }}" @selected($pool === $key)>{{ $label }}</option>
                    @endforeach
                </select>
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
                        <th>ID</th>
                        <th>分類</th>
                        <th>內容</th>
                        <th>排序</th>
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
                        <td>{{ $prompt->sort_order }}</td>
                        <td style="white-space:nowrap">
                            <a href="{{ route('admin.prompts.edit', $prompt) }}" class="btn btn-sm">編輯</a>
                            <form action="{{ route('admin.prompts.destroy', $prompt) }}" method="POST"
                                  style="display:inline" onsubmit="return confirm('確定刪除此題目？')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline" style="color:var(--accent)">刪除</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" style="text-align:center;padding:24px">沒有找到題目</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @include('admin._per-page', ['paginator' => $prompts])
    </div>
</section>
@endsection
