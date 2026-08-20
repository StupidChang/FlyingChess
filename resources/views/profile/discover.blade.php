@extends('layouts.app')
@section('title', __('profile.discover_title') . ' — ' . __('ui.site_name'))
@section('meta_description', __('profile.discover_intro'))
@section('robots', 'noindex,nofollow')

@section('content')
@php
    // 側欄廣告只在「有設定該版位」且「非付費會員」時才出現;沒設定就自動退回單欄置中。
    $showAds = ! auth()->check() || ! auth()->user()?->isPremium();
    $adapter = config('ads.adapter', 'exoclick');
    $adZone = $adapter === 'exoclick' ? config('ads.exoclick.zone_discover_side')
        : ($adapter === 'trafficjunky' ? config('ads.trafficjunky.spot_discover_side')
        : config('ads.adsense.slot_discover_side'));
    $railsOn = $showAds && $adZone;
@endphp
<div class="dz-page @if($railsOn) dz-has-rails @endif">

    @if($railsOn)
    <aside class="dz-rail">@include('partials.ad-unit', ['zone' => 'discover_side'])</aside>
    @endif

    <main class="dz-main">
        <header class="dz-head">
            <div class="dz-head-icon">
                @include('partials.icon', ['name' => 'users', 'cls' => 'dz-head-svg'])
            </div>
            <div>
                <h1>{{ __('profile.discover_heading') }}</h1>
                <p class="dz-sub">{{ __('profile.discover_intro') }}</p>
            </div>
        </header>

        <form method="GET" action="{{ route('profile.discover') }}" class="dz-search">
            <span class="dz-search-ico">@include('partials.icon', ['name' => 'search'])</span>
            <input type="text" name="city" value="{{ $city }}" placeholder="{{ __('profile.discover_city_placeholder') }}" maxlength="60">
            <button type="submit" class="btn btn-primary">{{ __('profile.discover_search') }}</button>
        </form>

        @if($users->total() > 0)
        <p class="dz-count">{{ __('profile.discover_count', ['n' => $users->total()]) }}</p>
        @endif

        @if($users->isEmpty())
            <div class="dz-empty">
                <div class="dz-empty-ico">@include('partials.icon', ['name' => 'search', 'cls' => 'dz-empty-svg'])</div>
                <p>{{ $city !== '' ? __('profile.discover_empty_city', ['city' => $city]) : __('profile.discover_empty') }}</p>
                @auth
                <a href="{{ route('profile.edit') }}" class="btn btn-primary btn-sm">{{ __('profile.edit_profile') }}</a>
                @endauth
            </div>
        @else
            <div class="dz-grid">
                @foreach($users as $u)
                @php $t = $u->themeMeta(); @endphp
                <a href="{{ route('profile.public', $u) }}" class="dz-card" style="--pf-accent:{{ $t['accent'] }};--pf-from:{{ $t['from'] }};--pf-to:{{ $t['to'] }}">
                    <div class="dz-cover" @if($u->bannerUrl()) style="background-image:url('{{ $u->bannerUrl() }}');background-position:{{ $u->bannerPosition() }}" @endif></div>
                    <div class="dz-avatar">
                        @if($u->avatarUrl())
                            <img src="{{ $u->avatarUrl() }}" alt="{{ $u->name }}" loading="lazy" style="object-position:{{ $u->avatarPosition() }}">
                        @else
                            <span class="pf-avatar-fallback">{{ $u->initial() }}</span>
                        @endif
                    </div>
                    <div class="dz-body">
                        <h2>{{ $u->name }}</h2>
                        @if($u->city)<p class="dz-city">@include('partials.icon', ['name' => 'pin'])<span>{{ $u->city }}</span></p>@endif
                        @if($u->looking_for)<p class="dz-looking">{{ \Illuminate\Support\Str::limit($u->looking_for, 42) }}</p>@endif
                        <span class="dz-view">{{ __('profile.discover_view') }} →</span>
                    </div>
                </a>
                @endforeach
            </div>

            <div class="dz-pager">{{ $users->links() }}</div>
        @endif
    </main>

    @if($railsOn)
    <aside class="dz-rail">@include('partials.ad-unit', ['zone' => 'discover_side'])</aside>
    @endif
</div>

<style>
.dz-page{max-width:1000px;margin:0 auto;padding:32px 20px 64px}
.dz-main{min-width:0}
.dz-rail{display:none}
@media(min-width:1300px){
  .dz-has-rails{display:grid;grid-template-columns:250px minmax(0,1fr) 250px;gap:28px;max-width:1380px;align-items:start}
  .dz-has-rails .dz-rail{display:block}
  .dz-has-rails .dz-rail .ad-unit{position:sticky;top:80px}
}

.dz-head{display:flex;align-items:center;gap:14px;margin-bottom:8px}
.dz-head-icon{width:44px;height:44px;border-radius:12px;flex:none;display:grid;place-items:center;color:#fff;
  background:linear-gradient(135deg,var(--accent),var(--accent-hover))}
.dz-head-icon svg{width:24px;height:24px}
.dz-head h1{font-size:clamp(1.4rem,3.6vw,1.9rem);font-weight:800;letter-spacing:-.01em;margin:0}
.dz-sub{color:var(--text-dim);font-size:.86rem;margin:2px 0 0;line-height:1.5}

.dz-search{position:relative;display:flex;gap:8px;margin:20px 0 6px;max-width:520px}
.dz-search-ico{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--text-dim);pointer-events:none}
.dz-search-ico svg{width:17px;height:17px}
.dz-search input{flex:1;padding:11px 13px 11px 38px;background:var(--surface);border:1px solid var(--border);
  border-radius:10px;color:var(--text);font-size:.9rem}
.dz-search input:focus{outline:2px solid var(--accent);outline-offset:1px;border-color:transparent}
.dz-count{color:var(--text-dim);font-size:.82rem;margin:0 0 18px}

.dz-empty{text-align:center;color:var(--text-dim);padding:56px 20px;background:var(--surface);
  border:1px dashed var(--border);border-radius:16px}
.dz-empty-ico{margin-bottom:12px;opacity:.55;color:var(--text-dim)}
.dz-empty-ico svg{width:42px;height:42px}
.dz-city .pf-ico{color:var(--pf-accent)}
.dz-empty p{margin:0 0 16px}

.dz-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:18px}
.dz-card{display:block;background:var(--surface);border:1px solid var(--border);border-radius:16px;
  overflow:hidden;transition:transform .14s,border-color .14s,box-shadow .14s;color:inherit}
.dz-card:hover{transform:translateY(-4px);border-color:color-mix(in srgb,var(--pf-accent) 60%,var(--border));
  box-shadow:0 12px 30px -14px rgba(0,0,0,.5)}
.dz-cover{height:80px;background:linear-gradient(135deg,var(--pf-from),var(--pf-to));background-size:cover;background-position:center}
.dz-avatar{width:68px;height:68px;border-radius:50%;overflow:hidden;margin:-34px auto 0;
  border:3px solid var(--surface);background:var(--surface2);position:relative}
.dz-avatar img{width:100%;height:100%;object-fit:cover;display:block}
.dz-avatar .pf-avatar-fallback{font-size:1.5rem}
.dz-body{padding:10px 16px 18px;text-align:center}
.dz-body h2{font-size:1.02rem;font-weight:700;margin:9px 0 3px}
.dz-city{font-size:.78rem;color:var(--pf-accent);margin:0 0 7px;display:flex;align-items:center;justify-content:center;gap:3px}
.dz-pin{font-size:.72rem}
.dz-looking{font-size:.82rem;color:var(--text-dim);line-height:1.5;margin:0 0 11px;min-height:1.2em}
.dz-view{font-size:.8rem;color:var(--pf-accent);font-weight:600}
.dz-pager{margin-top:30px}
</style>
@endsection
