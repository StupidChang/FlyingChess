@extends('layouts.app')

@section('title', ($card ? '編輯' : '新增') . '卡片 — 後台')
@section('robots', 'noindex,nofollow')

@section('content')
@include('admin._nav')

<section class="section section--sm">
    <div class="container" style="max-width:640px">
        <h1 style="margin-bottom:24px">{{ $card ? '編輯卡片 #'.$card->id : '新增卡片' }}</h1>

        @if($errors->any())
        <div class="toast toast-err" style="margin-bottom:16px">
            @foreach($errors->all() as $e) <div>{{ $e }}</div> @endforeach
        </div>
        @endif

        <form action="{{ $card ? route('admin.cards.update', $card) : route('admin.cards.store') }}"
              method="POST" class="admin-form">
            @csrf
            {{-- 帶著使用者原本停在列表的哪一頁、哪個篩選、哪個排序,存檔後照原樣
                 回去 —— 不然改一筆就要重找一次。 --}}
            <input type="hidden" name="return" value="{{ http_build_query($return ?? []) }}">
            @if($card) @method('PATCH') @endif

            <div class="form-group">
                <label for="category">類型</label>
                <select id="category" name="category" class="form-input" required>
                    @foreach(\App\Models\TruthDareCard::CATEGORIES as $k => $v)
                    <option value="{{ $k }}" {{ old('category', $card?->category) === $k ? 'selected' : '' }}>{{ $v }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label for="audience">適用人數</label>
                <select id="audience" name="audience" class="form-input" required>
                    @foreach(\App\Models\TruthDareCard::AUDIENCES as $k => $v)
                    <option value="{{ $k }}" {{ old('audience', $card?->audience ?? 'both') === $k ? 'selected' : '' }}>{{ $v }}</option>
                    @endforeach
                </select>
                <p style="font-size:.78rem;color:var(--text-dim);margin-top:6px;line-height:1.6">
                    指名「另一半」的題目選<strong>情侶</strong>,指名「在場的人／右邊的人」的選<strong>多人</strong>,
                    兩種場合都講得通的留<strong>通用</strong>。選錯的話,玩家會抽到對不上場合的題目。
                </p>
            </div>

            <div class="form-group">
                <label for="content">內容</label>
                <textarea id="content" name="content" class="form-input" rows="4"
                          required maxlength="500">{{ old('content', $card?->content) }}</textarea>
            </div>

            <div class="form-group">
                <label for="level">尺度</label>
                <select id="level" name="level" class="form-input" required>
                    @foreach(\App\Models\TruthDareCard::LEVELS as $k => $v)
                    <option value="{{ $k }}" {{ old('level', $card?->level ?? 'mild') === $k ? 'selected' : '' }}>{{ $v }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                {{-- 收費與尺度分開:尺度講內容多直接,收費講商業界線。中度裡
                     也可以有付費題目,不用把整級變成付費。 --}}
                <label class="form-check" style="display:flex;gap:8px;align-items:center;cursor:pointer">
                    <input type="checkbox" name="is_paid" value="1"
                           {{ old('is_paid', $card?->is_paid ?? \App\Models\TruthDareCard::defaultIsPaid(old('level', $card?->level))) ? 'checked' : '' }}>
                    <span>需付費或看廣告才抽得到</span>
                </label>
            </div>

            <div style="display:flex;gap:12px;margin-top:24px">
                <button type="submit" class="btn">{{ $card ? '儲存' : '新增' }}</button>
                <a href="{{ route('admin.cards', $return ?? []) }}" class="btn btn-outline">返回列表</a>
            </div>
        </form>
    </div>
</section>
@endsection
