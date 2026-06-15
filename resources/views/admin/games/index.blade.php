@extends('layouts.app')

@section('title', '遊戲場次管理 — 後台')
@section('robots', 'noindex,nofollow')

@section('content')
@include('admin._nav')

<section class="section section--sm">
    <div class="container">
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:24px">
            <h1 style="margin:0">遊戲場次管理</h1>
            <form action="{{ route('admin.games.cleanup') }}" method="POST"
                  onsubmit="return confirm('確定要清理 7 天前的已結束／廢棄場次嗎？此操作無法復原。')">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline">清理 7 天前場次</button>
            </form>
        </div>

        @if(session('success'))
        <div class="toast toast-ok" style="margin-bottom:16px">{{ session('success') }}</div>
        @endif
        @if($errors->any())
        <div class="toast toast-err" style="margin-bottom:16px">
            @foreach($errors->all() as $e) <div>{{ $e }}</div> @endforeach
        </div>
        @endif

        <div class="admin-filters">
            <div class="admin-filter-tabs">
                <a href="{{ route('admin.games', ['status' => 'all']) }}"
                   class="admin-filter-tab {{ $status === 'all' ? 'active' : '' }}">全部</a>
                <a href="{{ route('admin.games', ['status' => 'waiting']) }}"
                   class="admin-filter-tab {{ $status === 'waiting' ? 'active' : '' }}">等待中</a>
                <a href="{{ route('admin.games', ['status' => 'playing']) }}"
                   class="admin-filter-tab {{ $status === 'playing' ? 'active' : '' }}">進行中</a>
                <a href="{{ route('admin.games', ['status' => 'finished']) }}"
                   class="admin-filter-tab {{ $status === 'finished' ? 'active' : '' }}">已結束</a>
            </div>
            <form action="{{ route('admin.games') }}" method="GET" class="admin-search">
                <input type="hidden" name="status" value="{{ $status }}">
                <input type="text" name="q" value="{{ request('q') }}" placeholder="搜尋房間代碼…"
                       class="admin-search-input">
                <button type="submit" class="btn btn-sm">搜尋</button>
            </form>
        </div>

        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>代碼</th>
                        <th>類型</th>
                        <th>狀態</th>
                        <th>玩家數</th>
                        <th>建立時間</th>
                        <th>最後更新</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($games as $game)
                    <tr>
                        <td>{{ $game->id }}</td>
                        <td><code>{{ $game->code }}</code></td>
                        <td>{{ $game->game_type ?? '—' }}</td>
                        <td>
                            @if($game->status === 'waiting') <span class="badge-premium">等待中</span>
                            @elseif($game->status === 'playing') <span class="badge-admin">進行中</span>
                            @else <span style="color:var(--text-dim)">已結束</span>
                            @endif
                        </td>
                        <td>{{ $game->players_count }} / {{ $game->max_players }}</td>
                        <td>{{ $game->created_at->format('Y-m-d H:i') }}</td>
                        <td>{{ $game->updated_at->format('Y-m-d H:i') }}</td>
                        <td>
                            <form action="{{ route('admin.games.destroy', $game) }}" method="POST"
                                  onsubmit="return confirm('確定要刪除場次 {{ $game->code }} 嗎？')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline" style="color:#dc2626;border-color:#dc2626">刪除</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" style="text-align:center;padding:24px">沒有找到場次</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div style="margin-top:16px">{{ $games->links() }}</div>
    </div>
</section>
@endsection
