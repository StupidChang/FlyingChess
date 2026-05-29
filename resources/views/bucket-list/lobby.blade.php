@extends('layouts.app')
@section('title', __('games.bl_title') . ' | ' . __('ui.site_name'))
@section('meta_description', __('games.bl_meta'))
@section('og_title', __('games.bl_title'))
@section('og_description', __('games.bl_og_desc'))
@section('canonical', route('bucket-list.lobby'))

@section('styles')
<style>
.bl-page{max-width:560px;margin:0 auto;padding:48px 16px}
.bl-hero{text-align:center;margin-bottom:36px}
.bl-hero h1{font-size:1.7rem;color:var(--gold);margin-bottom:10px}
.bl-hero p{color:var(--text-dim);font-size:.95rem;line-height:1.7}
.bl-form{background:var(--surface);border:1px solid var(--border);border-radius:14px;padding:28px}
.bl-form h2{color:var(--gold);font-size:1.05rem;margin-bottom:16px;text-align:center;font-weight:600}
.bl-form .form-group{margin-bottom:16px}
.bl-form input[type=text]{width:100%;padding:12px 14px;background:var(--bg);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:1rem}
.bl-form input[type=text]:focus{outline:none;border-color:var(--gold)}
.bl-form .btn-submit{width:100%;font-size:1.05rem;padding:13px}
.bl-tip{font-size:.85rem;color:var(--text-dim);margin-top:18px;text-align:center;line-height:1.6}
.bl-tip a{color:var(--gold);text-decoration:underline}
.bl-features{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:24px}
.bl-feature{background:var(--bg);border:1px solid var(--border);border-radius:8px;padding:12px 8px;text-align:center}
.bl-feature .icon{font-size:1.4rem;margin-bottom:4px}
.bl-feature .label{font-size:.78rem;color:var(--text);line-height:1.3}
</style>
@endsection

@section('content')
<div class="bl-page">
    <div class="bl-hero">
        <h1>📋 情侶共同清單</h1>
        <p>一起整理「我們兩個都想做」的事<br>你提，我投，找出共同願望</p>
    </div>

    <div class="bl-features">
        <div class="bl-feature"><div class="icon">✍️</div><div class="label">輪流提想做的事</div></div>
        <div class="bl-feature"><div class="icon">👍</div><div class="label">兩人都投票</div></div>
        <div class="bl-feature"><div class="icon">💚</div><div class="label">兩人都同意 = 達成</div></div>
    </div>

    <div class="bl-form">
        <h2>建立新清單</h2>
        <form method="POST" action="{{ route('bucket-list.create') }}">
            @csrf
            <div class="form-group">
                <input type="text" name="title" placeholder="清單名稱（例：今年想一起做的 30 件事）" maxlength="100" required value="{{ old('title') }}">
            </div>
            @error('title') <div style="color:#ef4444;font-size:.85rem;margin-bottom:12px">{{ $message }}</div> @enderror
            <button type="submit" class="btn btn-gold btn-submit">開始建立</button>
        </form>
        <div class="bl-tip">
            建立後會產生分享連結，把連結傳給另一半就能一起編輯
        </div>
    </div>
</div>
@endsection
