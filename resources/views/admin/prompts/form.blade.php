@extends('layouts.app')

@section('title', ($prompt ? '編輯' : '新增') . '題目 — 後台')
@section('robots', 'noindex,nofollow')

@section('content')
@include('admin._nav')

<section class="section section--sm">
    <div class="container" style="max-width:640px">
        <h1 style="margin-bottom:24px">{{ $prompt ? '編輯題目 #'.$prompt->id : '新增題目' }}</h1>

        @if($errors->any())
        <div class="toast toast-err" style="margin-bottom:16px">
            @foreach($errors->all() as $e) <div>{{ $e }}</div> @endforeach
        </div>
        @endif

        <form action="{{ $prompt ? route('admin.prompts.update', $prompt) : route('admin.prompts.store') }}"
              method="POST" class="admin-form">
            @csrf
            {{-- 帶著使用者原本停在列表的哪一頁、哪個篩選、哪個排序,存檔後照原樣
                 回去 —— 不然改一筆就要重找一次。 --}}
            <input type="hidden" name="return" value="{{ http_build_query($return ?? []) }}">
            @if($prompt) @method('PATCH') @endif

            <div class="form-group">
                <label for="game">遊戲</label>
                {{-- 分類的合法值取決於遊戲,所以改遊戲要重載一次表單才拿得到對的分類。
                     編輯既有題目時不給換遊戲 —— 換了等於是搬到另一個題庫,
                     那應該是刪掉重建,不是改一個下拉。 --}}
                @if($prompt)
                    <input type="text" class="form-input" value="{{ \App\Models\GamePrompt::GAMES[$prompt->game] }}" disabled>
                    <input type="hidden" name="game" value="{{ $prompt->game }}">
                @else
                    <select id="game" name="game" class="form-input" required
                            onchange="location.href='{{ route('admin.prompts.create') }}?game='+this.value">
                        @foreach(\App\Models\GamePrompt::GAMES as $k => $v)
                        <option value="{{ $k }}" {{ $game === $k ? 'selected' : '' }}>{{ $v }}</option>
                        @endforeach
                    </select>
                @endif
            </div>

            <div class="form-group">
                <label for="pool">分類 / 強度</label>
                <select id="pool" name="pool" class="form-input" required>
                    @foreach(\App\Models\GamePrompt::POOLS[$game] as $k => $v)
                    <option value="{{ $k }}" {{ old('pool', $prompt?->pool ?? $pool) === $k ? 'selected' : '' }}>{{ $v }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label for="content">題目內容</label>
                <textarea id="content" name="content" class="form-input" rows="3"
                          required maxlength="200">{{ old('content', $prompt?->content) }}</textarea>
            </div>

            <div class="form-group">
                <label for="sort_order">排序</label>
                <input type="number" id="sort_order" name="sort_order" class="form-input"
                       value="{{ old('sort_order', $prompt?->sort_order ?? 0) }}" min="0" max="9999">
            </div>

            <div style="display:flex;gap:12px;margin-top:24px">
                <button type="submit" class="btn">{{ $prompt ? '儲存' : '新增' }}</button>
                <a href="{{ route('admin.prompts', ($return ?? []) + ['game' => $game]) }}" class="btn btn-outline">返回列表</a>
            </div>
        </form>
    </div>
</section>
@endsection
