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
            @if($segment) @method('PATCH') @endif

            <div class="form-group">
                <label for="tier">強度</label>
                <select id="tier" name="tier" class="form-input" required>
                    @foreach(['mild' => '輕鬆', 'medium' => '親密', 'intense' => '大膽'] as $k => $v)
                    <option value="{{ $k }}" {{ old('tier', $segment?->tier) === $k ? 'selected' : '' }}>{{ $v }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label for="content">任務內容</label>
                <textarea id="content" name="content" class="form-input" rows="3"
                          required maxlength="200">{{ old('content', $segment?->content) }}</textarea>
            </div>

            <div style="display:flex;gap:12px;margin-top:24px">
                <button type="submit" class="btn">{{ $segment ? '儲存' : '新增' }}</button>
                <a href="{{ route('admin.wheel-segments') }}" class="btn btn-outline">返回列表</a>
            </div>
        </form>
    </div>
</section>
@endsection
