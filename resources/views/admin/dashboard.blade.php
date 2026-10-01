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
            {{-- 未處理回報:有的時候標成強調色,沒有的時候就是一個普通的 0 --}}
            <a href="{{ route('admin.feedback') }}" class="admin-stat-card">
                <span class="admin-stat-num" @if($stats['pending_feedback']) style="color:var(--accent)" @endif>
                    {{ number_format($stats['pending_feedback']) }}
                </span>
                <span class="admin-stat-label">未處理回報</span>
            </a>
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

        {{-- 廣告版位。放連結是為了不必每次翻 .env 或 docs 才找得到後台;放版位狀態
             是因為那個更容易出事 —— zone id 沒填不會有任何錯誤,那一塊就只是不出現,
             而不出現的廣告永遠不會有人回報。 --}}
        <h2 style="margin:32px 0 16px">廣告</h2>
        <div class="admin-ads">
            <div class="admin-ads-main">
                <div class="admin-ads-head">
                    <span class="admin-ads-label">目前使用</span>
                    <strong class="admin-ads-name">{{ $ads['current']['label'] ?? $ads['adapter'] }}</strong>
                    <span class="admin-ads-count">{{ $ads['filled'] }} / {{ $ads['total'] }} 個版位已設定</span>
                </div>
                @if($ads['current'])
                <p class="admin-ads-hint">{{ $ads['current']['hint'] }}</p>
                <div class="admin-ads-actions">
                    <a href="{{ $ads['current']['dashboard'] }}" target="_blank" rel="noopener noreferrer"
                       class="btn btn-sm btn-gold">開啟 {{ $ads['current']['label'] }} 後台 ↗</a>
                    <a href="{{ $ads['current']['site'] }}" target="_blank" rel="noopener noreferrer"
                       class="btn btn-sm btn-outline">官網 ↗</a>
                </div>
                @endif

                <div class="admin-ads-slots">
                    @foreach($ads['slots'] as $slot)
                    <span class="admin-ads-slot {{ $slot['filled'] ? 'is-on' : 'is-off' }}"
                          title="{{ $slot['filled'] ? $slot['value'] : '未設定' }}">{{ $slot['key'] }}</span>
                    @endforeach
                </div>

                <p class="admin-ads-txt">
                    /ads.txt：
                    @if($ads['ads_txt'] !== '')
                        <b class="is-on-text">已設定</b>（{{ substr_count($ads['ads_txt'], '|') + 1 }} 行）
                    @else
                        <b class="is-off-text">未設定</b> —— 目前這個網址回 404。聯播網要求填自己的那一行才算授權賣量。
                    @endif
                </p>
            </div>

            <div class="admin-ads-others">
                <span class="admin-ads-label">其他聯播網</span>
                @foreach($ads['networks'] as $key => $net)
                    @continue($key === $ads['adapter'])
                    <a href="{{ $net['dashboard'] }}" target="_blank" rel="noopener noreferrer"
                       class="admin-ads-other {{ !empty($net['forbidden']) ? 'is-forbidden' : '' }}">
                        <b>{{ $net['label'] }} ↗</b>
                        <em>{{ $net['hint'] }}</em>
                    </a>
                @endforeach
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
                            @elseif($game->isAbandoned()) <span style="color:var(--text-dim)">已關閉（閒置）</span>
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

/* ── 廣告面板 ── */
.admin-ads{display:grid;gap:14px;grid-template-columns:1fr}
@media (min-width:900px){.admin-ads{grid-template-columns:1.6fr 1fr}}
.admin-ads-main,.admin-ads-others{border:1px solid var(--border);border-radius:12px;
  background:var(--surface);padding:16px 18px}
.admin-ads-label{display:block;font-size:.72rem;color:var(--text-dim);letter-spacing:.04em;margin-bottom:6px}
.admin-ads-head{display:flex;flex-wrap:wrap;align-items:baseline;gap:10px}
.admin-ads-head .admin-ads-label{margin:0}
.admin-ads-name{font-size:1.15rem;font-weight:800;color:var(--gold)}
.admin-ads-count{font-size:.78rem;color:var(--text-dim)}
.admin-ads-hint{font-size:.82rem;color:var(--text-dim);line-height:1.7;margin:8px 0 12px}
.admin-ads-actions{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:14px}
.admin-ads-slots{display:flex;flex-wrap:wrap;gap:6px;margin-bottom:12px}
.admin-ads-slot{font-size:.7rem;padding:3px 9px;border-radius:99px;border:1px solid var(--border);
  font-family:monospace;cursor:help}
.admin-ads-slot.is-on{color:#6fdc86;border-color:color-mix(in srgb,#6fdc86 40%,transparent)}
.admin-ads-slot.is-off{color:var(--text-dim);opacity:.7}
.admin-ads-txt{font-size:.8rem;color:var(--text-dim);line-height:1.7;margin:0}
.admin-ads-txt .is-on-text{color:#6fdc86}
.admin-ads-txt .is-off-text{color:var(--accent)}
.admin-ads-other{display:block;padding:10px 12px;border:1px solid var(--border);border-radius:10px;
  background:var(--bg);margin-bottom:8px;transition:border-color .14s}
.admin-ads-other:hover{border-color:var(--gold)}
.admin-ads-other b{display:block;font-size:.88rem;color:var(--text)}
.admin-ads-other em{display:block;font-size:.74rem;color:var(--text-dim);font-style:normal;line-height:1.6;margin-top:3px}
/* AdSense:成人內容啟用會違反政策,連結留著但要看得出是紅字警告 */
.admin-ads-other.is-forbidden b{color:var(--accent)}
.admin-ads-other.is-forbidden:hover{border-color:var(--accent)}
.admin-bar-chart{display:flex;align-items:flex-end;gap:8px;height:120px;padding-top:18px}
.admin-bar-col{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:flex-end;height:100%;min-width:0}
.admin-bar{width:100%;max-width:32px;border-radius:4px 4px 0 0;background:var(--rose,#e0507a);opacity:.85}
.admin-bar--alt{background:#5b8def}
.admin-bar-val{font-size:.7rem;color:var(--text-dim,#999);margin-bottom:2px}
.admin-bar-label{font-size:.65rem;color:var(--text-dim,#999);margin-top:6px;white-space:nowrap}
</style>
@endsection
