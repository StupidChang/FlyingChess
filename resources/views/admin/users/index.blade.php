@extends('layouts.app')

@section('title', '會員管理 — 後台')
@section('robots', 'noindex,nofollow')

@section('content')
@include('admin._nav')

<section class="section section--sm">
    <div class="container">
        <h1 style="margin-bottom:24px">會員管理</h1>

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
                @include('admin._filter-clear', ['params' => ['filter']])
                @include('admin._filter-tab', ['param' => 'filter', 'value' => 'premium', 'label' => '付費會員'])
                @include('admin._filter-tab', ['param' => 'filter', 'value' => 'admin', 'label' => '管理員'])
                @include('admin._filter-tab', ['param' => 'filter', 'value' => 'banned', 'label' => '已封鎖'])
            </div>
            {{-- 依註冊時的語系篩選(可複選)。最近瀏覽的語系會一直變,不拿來篩。 --}}
            <div class="admin-filter-tabs">
                @include('admin._filter-clear', ['params' => ['lang']])
                @foreach(\App\Support\LocaleHelper::supported() as $code => $cfg)
                    @include('admin._filter-tab', ['param' => 'lang', 'value' => $code, 'label' => $cfg['name']])
                @endforeach
                @include('admin._filter-tab', ['param' => 'lang', 'value' => 'none', 'label' => '未記錄'])
            </div>
            <form action="{{ route('admin.users') }}" method="GET" class="admin-search">
                {{-- 篩選現在是複選,搜尋時要把整組帶著走 --}}
                @foreach((array) request('filter', []) as $f)
                <input type="hidden" name="filter[]" value="{{ $f }}">
                @endforeach
                <input type="text" name="q" value="{{ request('q') }}" placeholder="搜尋名稱或 Email…"
                       class="admin-search-input">
                <button type="submit" class="btn btn-sm">搜尋</button>
            </form>
        </div>

        {{-- 群發收在 details 裡:很少用,而且是送出就收不回的動作,不該一進來就攤開 --}}
        <details class="admin-notify-all" style="margin:0 0 20px;padding:14px 16px;background:var(--surface);border:1px solid var(--border);border-radius:var(--radius)" @if($errors->hasAny(['title', 'body', 'url', 'audience'])) open @endif>
            <summary style="cursor:pointer;font-weight:600">群發站內通知</summary>
            <div style="margin-top:14px;max-width:560px">
                @include('admin.users._notify-form', [
                    'action' => route('admin.users.notify-all'),
                    'audiences' => \App\Http\Controllers\AdminController::NOTIFY_AUDIENCES,
                    'confirm' => '確定要群發這則通知嗎？送出後無法收回。',
                ])
            </div>
        </details>

        @include('admin._per-page', ['paginator' => $users, 'location' => 'top', 'showLinks' => false])
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        @include('admin._sort-header', ['key' => 'id', 'label' => 'ID'])
                        @include('admin._sort-header', ['key' => 'name', 'label' => '名稱'])
                        @include('admin._sort-header', ['key' => 'email', 'label' => 'Email'])
                        @include('admin._sort-header', ['key' => 'boards', 'label' => '棋盤數'])
                        @include('admin._sort-header', ['key' => 'locale', 'label' => '語系'])
                        <th>狀態</th>
                        @include('admin._sort-header', ['key' => 'created_at', 'label' => '註冊時間'])

                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                    <tr>
                        <td>{{ $user->id }}</td>
                        <td>{{ $user->name }}</td>
                        <td>{{ $user->email }}</td>
                        <td>{{ $user->boards_count }}</td>
                        {{-- 註冊時的語系;最近瀏覽用的語系不一樣時,小字標在下面 --}}
                        <td>
                            @include('admin._locale-name', ['locale' => $user->locale])
                            @if($user->last_locale && $user->last_locale !== $user->locale)
                                <div style="font-size:.75rem;color:var(--text-dim)">最近：@include('admin._locale-name', ['locale' => $user->last_locale])</div>
                            @endif
                        </td>
                        <td>
                            @if($user->isAdmin()) <span class="badge-admin">Admin</span> @endif
                            @if($user->isPremium()) <span class="badge-premium">Premium</span> @endif
                            @if($user->isBanned()) <span class="badge-admin" style="background:#dc2626">已封鎖</span> @endif
                        </td>
                        <td>{{ $user->created_at->format('Y-m-d H:i') }}</td>
                        <td>
                            <div style="display:flex;gap:6px;flex-wrap:wrap">
                                <a href="{{ route('admin.users.edit', [$user, 'return' => request()->getQueryString()]) }}" class="btn btn-sm">編輯</a>
                                @unless($user->isAdmin() || $user->id === auth()->id())
                                    @if($user->isBanned())
                                    <form action="{{ route('admin.users.unban', $user) }}" method="POST"
                                          data-confirm="確定要解除封鎖「{{ $user->name }}」嗎？"
                                          onsubmit="return confirm(this.dataset.confirm)">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline">解封</button>
                                    </form>
                                    @else
                                    <form action="{{ route('admin.users.ban', $user) }}" method="POST"
                                          data-confirm="確定要封鎖「{{ $user->name }}」嗎？被封鎖後將無法登入。"
                                          onsubmit="return confirm(this.dataset.confirm)">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline">封鎖</button>
                                    </form>
                                    @endif
                                    <form action="{{ route('admin.users.destroy', $user) }}" method="POST"
                                          data-confirm="確定要刪除「{{ $user->name }}」嗎？此操作會連帶刪除其建立的棋盤，且無法復原。"
                                          onsubmit="return confirm(this.dataset.confirm)">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline" style="color:#dc2626;border-color:#dc2626">刪除</button>
                                    </form>
                                @endunless
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" style="text-align:center;padding:24px">沒有找到會員</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @include('admin._per-page', ['paginator' => $users])
    </div>
</section>
@endsection
