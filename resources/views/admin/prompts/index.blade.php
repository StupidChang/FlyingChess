@extends('layouts.app')
@section('title', '題庫 — 管理後台')
@section('robots', 'noindex,nofollow')
@section('content')

@include('admin._nav')

<div class="container" style="padding:28px 20px 60px">
    <h1 class="section-title" style="font-size:1.3rem;text-align:left">題庫</h1>
    <p class="pr-sub">
        四個小遊戲的題目。資料表有內容就以這裡為準;一題都沒有的遊戲會回頭用
        程式碼裡的預設題庫,所以刪光也不會讓遊戲壞掉。
    </p>

    @if(session('success'))
        <div class="pr-flash">{{ session('success') }}</div>
    @endif

    {{-- 遊戲切換 --}}
    <div class="pr-tabs">
        @foreach(\App\Models\GamePrompt::GAMES as $key => $label)
            <a href="{{ route('admin.prompts', ['game' => $key]) }}"
               class="pr-tab {{ $game === $key ? 'active' : '' }}">{{ $label }}</a>
        @endforeach
    </div>

    @if($isEmpty)
        <div class="pr-empty">
            <p>這個遊戲還沒有匯入題目,目前用的是程式碼裡的預設題庫。匯入之後就能在這裡編輯。</p>
            <form method="POST" action="{{ route('admin.prompts.import') }}">
                @csrf
                <input type="hidden" name="game" value="{{ $game }}">
                <button type="submit" class="btn btn-gold btn-sm">匯入預設題目</button>
            </form>
        </div>
    @endif

    {{-- 篩選 --}}
    <form method="GET" class="pr-filters">
        <input type="hidden" name="game" value="{{ $game }}">
        <select name="pool" class="form-control">
            <option value="">全部分類</option>
            @foreach(\App\Models\GamePrompt::POOLS[$game] as $key => $label)
                <option value="{{ $key }}" @selected($pool === $key)>{{ $label }}</option>
            @endforeach
        </select>
        <input type="text" name="q" value="{{ request('q') }}" placeholder="搜尋內容" class="form-control">
        <button type="submit" class="btn btn-sm btn-outline">篩選</button>
    </form>

    {{-- 每頁筆數自己就是一個 form,不能包在上面那個篩選表單裡(巢狀 form 不合法,
         瀏覽器會把內層丟掉)。分頁連結統一由頁尾那組負責,這裡關掉。 --}}
    @include('admin._per-page', ['paginator' => $prompts, 'showLinks' => false])

    {{-- 新增 --}}
    <form method="POST" action="{{ route('admin.prompts.store') }}" class="pr-add">
        @csrf
        <input type="hidden" name="game" value="{{ $game }}">
        <select name="pool" class="form-control" required>
            @foreach(\App\Models\GamePrompt::POOLS[$game] as $key => $label)
                <option value="{{ $key }}" @selected($pool === $key)>{{ $label }}</option>
            @endforeach
        </select>
        <input type="text" name="content" maxlength="200" placeholder="新增一題" class="form-control" required>
        <input type="number" name="sort_order" value="0" min="0" max="9999" class="form-control pr-order" title="排序">
        <button type="submit" class="btn btn-sm btn-gold">新增</button>
    </form>
    @error('content')<p class="pr-error">{{ $message }}</p>@enderror

    {{-- 列表。每一列本身就是編輯表單,不用另外開一頁 —— 題目只有三個欄位。 --}}
    <table class="pr-table">
        <thead><tr><th>分類</th><th>內容</th><th style="width:70px">排序</th><th style="width:120px"></th></tr></thead>
        <tbody>
        @forelse($prompts as $prompt)
            <tr>
                <form method="POST" action="{{ route('admin.prompts.update', $prompt) }}" id="f{{ $prompt->id }}">
                    @csrf @method('PATCH')
                    <input type="hidden" name="game" value="{{ $prompt->game }}">
                </form>
                <td>
                    <select name="pool" form="f{{ $prompt->id }}" class="form-control">
                        @foreach(\App\Models\GamePrompt::POOLS[$game] as $key => $label)
                            <option value="{{ $key }}" @selected($prompt->pool === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </td>
                <td><input type="text" name="content" form="f{{ $prompt->id }}"
                           value="{{ $prompt->content }}" maxlength="200" class="form-control"></td>
                <td><input type="number" name="sort_order" form="f{{ $prompt->id }}"
                           value="{{ $prompt->sort_order }}" min="0" max="9999" class="form-control"></td>
                <td class="pr-actions">
                    <button type="submit" form="f{{ $prompt->id }}" class="btn btn-sm btn-outline">儲存</button>
                    <form method="POST" action="{{ route('admin.prompts.destroy', $prompt) }}"
                          onsubmit="return confirm('刪除這一題?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline pr-del">刪除</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="4" class="pr-none">沒有符合的題目</td></tr>
        @endforelse
        </tbody>
    </table>

    <div style="margin-top:20px">{{ $prompts->links() }}</div>
</div>
@endsection
