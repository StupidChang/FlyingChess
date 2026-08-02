@extends('layouts.app')

@section('title', ($segment ? '編輯' : '新增') . '轉盤任務 — 後台')
@section('robots', 'noindex,nofollow')

@section('content')
@include('admin._nav')

<section class="section section--sm">
    <div class="container" style="max-width:640px">
        <h1 style="margin-bottom:24px">{{ $segment ? '編輯任務 #'.$segment->id : '新增轉盤任務' }}</h1>

        @if($errors->any())
        <div class="toast toast-err" style="margin-bottom:16px">
            @foreach($errors->all() as $e) <div>{{ $e }}</div> @endforeach
        </div>
        @endif

        <form action="{{ $segment ? route('admin.wheel-segments.update', $segment) : route('admin.wheel-segments.store') }}"
              method="POST" class="admin-form">
            @csrf
            {{-- 帶著使用者原本停在列表的哪一頁、哪個篩選、哪個排序,存檔後照原樣
                 回去 —— 不然改一筆就要重找一次。 --}}
            <input type="hidden" name="return" value="{{ http_build_query($return ?? []) }}">
            @if($segment) @method('PATCH') @endif

            <div class="form-group">
                <label for="tier">強度</label>
                <select id="tier" name="tier" class="form-input" required>
                    @foreach(\App\Models\WheelSegment::TIERS as $k => $v)
                    <option value="{{ $k }}" {{ old('tier', $segment?->tier) === $k ? 'selected' : '' }}>{{ $v }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label for="content">任務內容</label>
                <textarea id="content" name="content" class="form-input" rows="3"
                          required maxlength="200">{{ old('content', $segment?->content) }}</textarea>
            </div>

            <div class="form-group">
                {{-- 收費與尺度分開:尺度講內容多直接,收費講商業界線。 --}}
                <label class="form-check" style="display:flex;gap:8px;align-items:center;cursor:pointer">
                    <input type="checkbox" name="is_paid" value="1" {{ old('is_paid', $segment?->is_paid ?? \App\Models\WheelSegment::defaultIsPaid(old('tier', $segment?->tier))) ? 'checked' : '' }}>
                    <span>需付費或看廣告才抽得到</span>
                </label>
            </div>

            <div class="form-group">
                {{-- 標記題:寫成獨一無二的句子,之後在別人的站上搜到就是證據。
                     它本身是正常題目,照樣會被抽到 —— 不然永遠不會流出去,
                     也就失去意義。 --}}
                <label class="form-check" style="display:flex;gap:8px;align-items:center;cursor:pointer">
                    <input type="checkbox" name="is_canary" value="1" {{ old('is_canary', $segment?->is_canary) ? 'checked' : '' }}>
                    <span>標記題(用來抓抄襲)</span>
                </label>
            </div>

            <div style="display:flex;gap:12px;margin-top:24px">
                <button type="submit" class="btn">{{ $segment ? '儲存' : '新增' }}</button>
                <a href="{{ route('admin.wheel-segments', $return ?? []) }}" class="btn btn-outline">返回列表</a>
            </div>
        </form>
    </div>
</section>
@endsection
