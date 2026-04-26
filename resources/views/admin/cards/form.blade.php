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
            @if($card) @method('PATCH') @endif

            <div class="form-group">
                <label for="category">分類</label>
                <select id="category" name="category" class="form-input" required>
                    @foreach(['truth' => '真心話', 'dare' => '大冒險', 'couple' => '情侶', 'party' => '派對'] as $k => $v)
                    <option value="{{ $k }}" {{ old('category', $card?->category) === $k ? 'selected' : '' }}>{{ $v }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label for="content">內容</label>
                <textarea id="content" name="content" class="form-input" rows="4"
                          required maxlength="500">{{ old('content', $card?->content) }}</textarea>
            </div>

            <div class="form-group">
                <label for="tier">版本</label>
                <select id="tier" name="tier" class="form-input" required>
                    <option value="free" {{ old('tier', $card?->tier) === 'free' ? 'selected' : '' }}>一般</option>
                    <option value="premium" {{ old('tier', $card?->tier) === 'premium' ? 'selected' : '' }}>18禁</option>
                </select>
            </div>

            <div style="display:flex;gap:12px;margin-top:24px">
                <button type="submit" class="btn">{{ $card ? '儲存' : '新增' }}</button>
                <a href="{{ route('admin.cards') }}" class="btn btn-outline">返回列表</a>
            </div>
        </form>
    </div>
</section>
@endsection
