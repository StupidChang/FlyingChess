<nav class="admin-nav">
    <div class="container">
        <a href="{{ route('admin.dashboard') }}"
           class="admin-nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">總覽</a>
        <a href="{{ route('admin.boards') }}"
           class="admin-nav-link {{ request()->routeIs('admin.boards*') ? 'active' : '' }}">棋盤</a>
        <a href="{{ route('admin.cards') }}"
           class="admin-nav-link {{ request()->routeIs('admin.cards*') ? 'active' : '' }}">卡片</a>
        <a href="{{ route('admin.wheel-segments') }}"
           class="admin-nav-link {{ request()->routeIs('admin.wheel-segments*') ? 'active' : '' }}">轉盤</a>
        <a href="{{ route('admin.users') }}"
           class="admin-nav-link {{ request()->routeIs('admin.users*') ? 'active' : '' }}">會員</a>
        <a href="{{ route('admin.games') }}"
           class="admin-nav-link {{ request()->routeIs('admin.games*') ? 'active' : '' }}">遊戲</a>
    </div>
</nav>
