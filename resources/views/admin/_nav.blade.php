@php
    /* 未處理的回報數。每個後台頁面多一次 count() —— 值得,因為回報沒有人通知,
       不在每一頁都看得到就會積在那裡沒人理。 */
    $pendingFeedback = \App\Models\Feedback::where('status', \App\Models\Feedback::STATUS_NEW)->count();
@endphp
<nav class="admin-nav">
    <div class="container">
        <a href="{{ route('admin.dashboard') }}" style="--c:#6fb7f5"
           class="admin-nav-link tab {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">總覽</a>

        {{-- 內容庫:棋盤 / 卡片 / 題庫 / 轉盤 是同一類「可編輯的遊戲內容」,擺在一起
             並共用青色系。群組底色與四個項目同高、切齊其他分頁,不再是浮起來的膠囊。 --}}
        <span class="admin-nav-group">
            <a href="{{ route('admin.boards') }}" style="--c:#5eead4"
               class="admin-nav-link tab {{ request()->routeIs('admin.boards') || request()->routeIs('admin.boards.edit') || request()->routeIs('admin.boards.update') ? 'active' : '' }}">棋盤</a>
            <a href="{{ route('admin.cards') }}" style="--c:#5eead4"
               class="admin-nav-link tab {{ request()->routeIs('admin.cards*') ? 'active' : '' }}">卡片</a>
            <a href="{{ route('admin.prompts') }}" style="--c:#5eead4"
               class="admin-nav-link tab {{ request()->routeIs('admin.prompts*') ? 'active' : '' }}">題庫</a>
            <a href="{{ route('admin.wheel-segments') }}" style="--c:#5eead4"
               class="admin-nav-link tab {{ request()->routeIs('admin.wheel-segments*') ? 'active' : '' }}">轉盤</a>
        </span>

        <a href="{{ route('admin.boards.reviews') }}" style="--c:#e6c34d"
           class="admin-nav-link tab {{ request()->routeIs('admin.boards.reviews') ? 'active' : '' }}">發佈審核</a>
        <a href="{{ route('admin.users') }}" style="--c:#a99cf5"
           class="admin-nav-link tab {{ request()->routeIs('admin.users*') ? 'active' : '' }}">會員</a>
        <a href="{{ route('admin.games') }}" style="--c:#6fdc86"
           class="admin-nav-link tab {{ request()->routeIs('admin.games*') ? 'active' : '' }}">遊戲</a>
        <a href="{{ route('admin.traffic') }}" style="--c:#cf8bf0"
           class="admin-nav-link tab {{ request()->routeIs('admin.traffic') ? 'active' : '' }}">流量</a>
        <a href="{{ route('admin.pricing') }}" style="--c:#f0a25a"
           class="admin-nav-link tab {{ request()->routeIs('admin.pricing*') ? 'active' : '' }}">定價</a>
        <a href="{{ route('admin.feedback') }}" style="--c:#fb7185"
           class="admin-nav-link tab {{ request()->routeIs('admin.feedback*') ? 'active' : '' }}">回報@if($pendingFeedback)<span class="admin-nav-count">{{ $pendingFeedback }}</span>@endif</a>
    </div>
</nav>

<style>
.admin-nav-count{display:inline-block;margin-left:5px;padding:1px 6px;border-radius:999px;
    background:var(--accent);color:#fff;font-size:.68rem;font-weight:700;vertical-align:1px}

/* 每個分頁一個顏色(由各連結的 --c 帶入):平時收斂成偏灰的色調、hover 與選中時上滿色。 */
.admin-nav-link.tab{color:color-mix(in srgb, var(--c, #9aa1b5) 60%, var(--text-dim))}
.admin-nav-link.tab:hover{color:var(--c, #e9ebf2)}
.admin-nav-link.tab.active{color:var(--c, #f43f5e);border-bottom-color:var(--c, #f43f5e)}

/* 內容庫群組:淡青底、與其他分頁同高切齊(不加上下邊距,才不會浮起來)。 */
.admin-nav-group{display:inline-flex;align-items:stretch;background:rgba(45,212,191,.07);border-radius:8px}
</style>
