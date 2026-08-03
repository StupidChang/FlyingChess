@php
    /* 未處理的回報數。每個後台頁面多一次 count() —— 值得,因為回報沒有人通知,
       不在每一頁都看得到就會積在那裡沒人理。 */
    $pendingFeedback = \App\Models\Feedback::where('status', \App\Models\Feedback::STATUS_NEW)->count();
@endphp
<nav class="admin-nav">
    <div class="container">
        <a href="{{ route('admin.dashboard') }}"
           class="admin-nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">總覽</a>
        <a href="{{ route('admin.boards') }}"
           class="admin-nav-link {{ request()->routeIs('admin.boards') || request()->routeIs('admin.boards.edit') || request()->routeIs('admin.boards.update') ? 'active' : '' }}">棋盤</a>
        <a href="{{ route('admin.boards.reviews') }}"
           class="admin-nav-link {{ request()->routeIs('admin.boards.reviews') ? 'active' : '' }}">發佈審核</a>
        <a href="{{ route('admin.cards') }}"
           class="admin-nav-link {{ request()->routeIs('admin.cards*') ? 'active' : '' }}">卡片</a>
        <a href="{{ route('admin.prompts') }}"
           class="admin-nav-link {{ request()->routeIs('admin.prompts*') ? 'active' : '' }}">題庫</a>
        <a href="{{ route('admin.wheel-segments') }}"
           class="admin-nav-link {{ request()->routeIs('admin.wheel-segments*') ? 'active' : '' }}">轉盤</a>
        <a href="{{ route('admin.users') }}"
           class="admin-nav-link {{ request()->routeIs('admin.users*') ? 'active' : '' }}">會員</a>
        <a href="{{ route('admin.games') }}"
           class="admin-nav-link {{ request()->routeIs('admin.games*') ? 'active' : '' }}">遊戲</a>
        <a href="{{ route('admin.traffic') }}"
           class="admin-nav-link {{ request()->routeIs('admin.traffic') ? 'active' : '' }}">流量</a>
        <a href="{{ route('admin.feedback') }}"
           class="admin-nav-link {{ request()->routeIs('admin.feedback*') ? 'active' : '' }}">回報@if($pendingFeedback)<span class="admin-nav-count">{{ $pendingFeedback }}</span>@endif</a>
    </div>
</nav>

<style>
.admin-nav-count{display:inline-block;margin-left:5px;padding:1px 6px;border-radius:999px;
    background:var(--accent);color:#fff;font-size:.68rem;font-weight:700;vertical-align:1px}
</style>
