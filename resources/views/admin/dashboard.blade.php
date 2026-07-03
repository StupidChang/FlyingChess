@extends('layouts.app')

@section('title', '後台管理 — 枕邊遊戲')
@section('robots', 'noindex,nofollow')

@section('content')
@include('admin._nav')

<section class="section section--sm">
    <div class="container">
        <h1 style="margin-bottom:24px">後台總覽</h1>

        <div class="admin-stats">
            <div class="admin-stat-card">
                <span class="admin-stat-num">{{ number_format($stats['users']) }}</span>
                <span class="admin-stat-label">會員數</span>
            </div>
            <div class="admin-stat-card">
                <span class="admin-stat-num">{{ number_format($stats['premium']) }}</span>
                <span class="admin-stat-label">付費會員</span>
            </div>
            <div class="admin-stat-card">
                <span class="admin-stat-num">{{ number_format($stats['boards']) }}</span>
                <span class="admin-stat-label">棋盤數</span>
            </div>
            <div class="admin-stat-card">
                <span class="admin-stat-num">{{ number_format($stats['templates']) }}</span>
                <span class="admin-stat-label">範本數</span>
            </div>
            <div class="admin-stat-card">
                <span class="admin-stat-num">{{ number_format($stats['cards']) }}</span>
                <span class="admin-stat-label">卡片數</span>
            </div>
            <div class="admin-stat-card">
                <span class="admin-stat-num">{{ number_format($stats['wheel_segments']) }}</span>
                <span class="admin-stat-label">轉盤任務</span>
            </div>
            <div class="admin-stat-card">
                <span class="admin-stat-num">{{ number_format($stats['games']) }}</span>
                <span class="admin-stat-label">遊戲場次</span>
            </div>
            <div class="admin-stat-card">
                <span class="admin-stat-num">{{ number_format($stats['users_7d']) }}</span>
                <span class="admin-stat-label">近 7 天註冊</span>
            </div>
            <div class="admin-stat-card">
                <span class="admin-stat-num">{{ number_format($stats['games_7d']) }}</span>
                <span class="admin-stat-label">近 7 天場次</span>
            </div>
            <div class="admin-stat-card">
                <span class="admin-stat-num">{{ number_format($stats['users_today']) }}</span>
                <span class="admin-stat-label">今日新增會員</span>
            </div>
            <div class="admin-stat-card">
                <span class="admin-stat-num">{{ number_format($stats['games_today']) }}</span>
                <span class="admin-stat-label">今日新增場次</span>
            </div>
        </div>

        @php
            $maxUsers = max(1, $dailySeries->max('users'));
            $maxGames = max(1, $dailySeries->max('games'));
        @endphp
        <div class="admin-mini-charts">
            <div class="admin-stat-card admin-chart-card">
                <span class="admin-stat-label" style="margin-bottom:12px">近 7 天每日註冊</span>
                <div class="admin-bar-chart">
                    @foreach($dailySeries as $day)
                    <div class="admin-bar-col" title="{{ $day['date'] }}：{{ $day['users'] }} 人">
                        <span class="admin-bar-val">{{ $day['users'] }}</span>
                        <div class="admin-bar" style="height:{{ max(4, round($day['users'] / $maxUsers * 100)) }}%"></div>
                        <span class="admin-bar-label">{{ $day['label'] }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
            <div class="admin-stat-card admin-chart-card">
                <span class="admin-stat-label" style="margin-bottom:12px">近 7 天每日場次</span>
                <div class="admin-bar-chart">
                    @foreach($dailySeries as $day)
                    <div class="admin-bar-col" title="{{ $day['date'] }}：{{ $day['games'] }} 場">
                        <span class="admin-bar-val">{{ $day['games'] }}</span>
                        <div class="admin-bar admin-bar--alt" style="height:{{ max(4, round($day['games'] / $maxGames * 100)) }}%"></div>
                        <span class="admin-bar-label">{{ $day['label'] }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        <h2 style="margin:32px 0 16px">最近註冊會員</h2>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>名稱</th>
                        <th>Email</th>
                        <th>狀態</th>
                        <th>註冊時間</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentUsers as $user)
                    <tr>
                        <td>{{ $user->id }}</td>
                        <td>{{ $user->name }}</td>
                        <td>{{ $user->email }}</td>
                        <td>
                            @if($user->isAdmin()) <span class="badge-admin">Admin</span> @endif
                            @if($user->isPremium()) <span class="badge-premium">Premium</span> @endif
                        </td>
                        <td>{{ $user->created_at->format('Y-m-d H:i') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <h2 style="margin:32px 0 16px">最近 5 場遊戲</h2>
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
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentGames as $game)
                    <tr>
                        <td>{{ $game->id }}</td>
                        <td><code>{{ $game->code }}</code></td>
                        <td>{{ $game->game_type ?? '—' }}</td>
                        <td>
                            @if($game->status === 'waiting') 等待中
                            @elseif($game->status === 'playing') 進行中
                            @else 已結束
                            @endif
                        </td>
                        <td>{{ $game->players_count }} / {{ $game->max_players }}</td>
                        <td>{{ $game->created_at->format('Y-m-d H:i') }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="6" style="text-align:center;padding:24px">目前沒有遊戲場次</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div style="margin-top:12px">
            <a href="{{ route('admin.games') }}" class="btn btn-sm btn-outline">前往遊戲場次管理 →</a>
        </div>
    </div>
</section>

<style>
.admin-mini-charts{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:16px;margin-top:16px}
.admin-chart-card{display:flex;flex-direction:column;align-items:stretch}
.admin-bar-chart{display:flex;align-items:flex-end;gap:8px;height:120px;padding-top:18px}
.admin-bar-col{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:flex-end;height:100%;min-width:0}
.admin-bar{width:100%;max-width:32px;border-radius:4px 4px 0 0;background:var(--rose,#e0507a);opacity:.85}
.admin-bar--alt{background:#5b8def}
.admin-bar-val{font-size:.7rem;color:var(--text-dim,#999);margin-bottom:2px}
.admin-bar-label{font-size:.65rem;color:var(--text-dim,#999);margin-top:6px;white-space:nowrap}
</style>
@endsection
