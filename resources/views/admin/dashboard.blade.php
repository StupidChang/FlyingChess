@extends('layouts.app')

@section('title', '後台管理 — 情侶飛行棋')
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
    </div>
</section>
@endsection
