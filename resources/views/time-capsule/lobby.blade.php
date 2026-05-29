@extends('layouts.app')
@section('title', __('games.tc_title') . ' | ' . __('ui.site_name'))
@section('meta_description', __('games.tc_meta'))
@section('og_title', __('games.tc_title'))
@section('og_description', __('games.tc_og_desc'))
@section('canonical', route('time-capsule.lobby'))

@section('styles')
<style>
.tc-page{max-width:560px;margin:0 auto;padding:48px 16px}
.tc-hero{text-align:center;margin-bottom:36px}
.tc-hero h1{font-size:1.7rem;color:var(--gold);margin-bottom:10px}
.tc-hero p{color:var(--text-dim);font-size:.95rem;line-height:1.7}
.tc-form{background:var(--surface);border:1px solid var(--border);border-radius:14px;padding:28px}
.tc-form h2{color:var(--gold);font-size:1.05rem;margin-bottom:16px;text-align:center;font-weight:600}
.tc-form .form-group{margin-bottom:16px}
.tc-form label{display:block;color:var(--text-dim);font-size:.85rem;margin-bottom:6px}
.tc-form input{width:100%;padding:12px 14px;background:var(--bg);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:1rem}
.tc-form input:focus{outline:none;border-color:var(--gold)}
.tc-form .btn-submit{width:100%;font-size:1.05rem;padding:13px}
.tc-tip{font-size:.85rem;color:var(--text-dim);margin-top:18px;text-align:center;line-height:1.6}
.tc-features{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:24px}
.tc-feature{background:var(--bg);border:1px solid var(--border);border-radius:8px;padding:12px 8px;text-align:center}
.tc-feature .icon{font-size:1.4rem;margin-bottom:4px}
.tc-feature .label{font-size:.78rem;color:var(--text);line-height:1.3}
.tc-error{color:#ef4444;font-size:.85rem;margin-bottom:12px}
</style>
@endsection

@section('content')
<div class="tc-page">
    <div class="tc-hero">
        <h1>📦 情侶時間膠囊</h1>
        <p>今天的我們，寫一封信給未來的我們</p>
    </div>

    <div class="tc-features">
        <div class="tc-feature"><div class="icon">📝</div><div class="label">回答 10 個問題</div></div>
        <div class="tc-feature"><div class="icon">🔒</div><div class="label">封存到開封日</div></div>
        <div class="tc-feature"><div class="icon">💌</div><div class="label">一起開封回顧</div></div>
    </div>

    <div class="tc-form">
        <h2>建立新膠囊</h2>
        <form method="POST" action="{{ route('time-capsule.create') }}">
            @csrf
            <div class="form-group">
                <label>膠囊標題</label>
                <input type="text" name="title" placeholder="例：給一年後的我們" maxlength="100" required value="{{ old('title') }}">
                @error('title') <div class="tc-error">{{ $message }}</div> @enderror
            </div>
            <div class="form-group">
                <label>開封日期（必須是明天以後，建議 1 年後的同一天）</label>
                <input type="date" name="open_at" required value="{{ old('open_at', \Carbon\Carbon::today()->addYear()->toDateString()) }}" min="{{ \Carbon\Carbon::tomorrow()->toDateString() }}">
                @error('open_at') <div class="tc-error">{{ $message }}</div> @enderror
            </div>
            <div class="form-group">
                <label>提醒 Email（選填，開封日當天會寄信提醒）</label>
                <input type="email" name="notify_email" placeholder="your@email.com" maxlength="100" value="{{ old('notify_email') }}">
                @error('notify_email') <div class="tc-error">{{ $message }}</div> @enderror
            </div>
            <button type="submit" class="btn btn-gold btn-submit">建立膠囊</button>
        </form>
        <div class="tc-tip">
            建立後產生分享連結，傳給另一半就能一起寫；填好後創建者按「封存」即鎖定，到開封日才能查看
        </div>
    </div>
</div>
@endsection
