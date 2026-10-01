@extends('layouts.app')

@section('title', __('feedback.seo_title'))
@section('meta_description', __('feedback.seo_description'))
@section('og_title', __('feedback.h1'))
@section('og_description', __('feedback.seo_description'))
@section('canonical', route('feedback.show'))
{{-- 表單頁沒有搜尋價值,索引它只會多一頁薄內容。follow 留著,讓爬蟲照樣走得到
     頁尾其他連結,不要變成死路。 --}}
@section('robots', 'noindex,follow')

@section('styles')
<link rel="stylesheet" href="{{ asset_v('css/minigames.css') }}">
@endsection

@section('content')
<div class="mg-tool-page fb-page">
    @if(session('feedback_ok'))
        {{-- 送出後停在同一頁,但整塊換成確認畫面。redirect 而不是直接 render,
             是為了讓重新整理不會重送一次(POST/redirect/GET)。 --}}
        <div class="mg-tool-hero">
            <h1>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.501 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z"/></svg>
                {{ __('feedback.h1') }}
            </h1>
            <p>{{ __('feedback.hero_sub') }}</p>
        </div>

        <div class="fb-done" role="status">
            <span class="fb-done-mark" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"
                     stroke-linecap="round" stroke-linejoin="round"><path d="M4.5 12.75l6 6 9-13.5"/></svg>
            </span>
            <h2>{{ __('feedback.thanks_title') }}</h2>
            <p>{{ __('feedback.thanks_body') }}</p>
            @if(session('feedback_id'))
                <p class="fb-done-ref">{{ __('feedback.thanks_ref', ['id' => session('feedback_id')]) }}</p>
            @endif
            <a href="{{ route('feedback.show') }}" class="btn btn-outline btn-sm">{{ __('feedback.thanks_again') }}</a>
        </div>
    @else
        {{-- 桌機左右兩欄:左邊說明、右邊表單;手機照原本上下排 --}}
        <div class="fb-layout">
        <div class="fb-aside">
        <div class="mg-tool-hero">
            <h1>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.501 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z"/></svg>
                {{ __('feedback.h1') }}
            </h1>
            <p>{{ __('feedback.hero_sub') }}</p>
        </div>
        <div class="mg-tool-features">
            <div class="mg-tool-feature">
                <svg class="mg-feature-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/></svg>
                <div class="label">{{ __('feedback.feature_1') }}</div>
            </div>
            <div class="mg-tool-feature">
                <svg class="mg-feature-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25"/></svg>
                <div class="label">{{ __('feedback.feature_2') }}</div>
            </div>
            <div class="mg-tool-feature">
                <svg class="mg-feature-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.964-7.178Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                <div class="label">{{ __('feedback.feature_3') }}</div>
            </div>
        </div>
            <div class="mg-tool-tip">{{ __('feedback.tip') }}</div>
        </div>
        <div class="mg-tool-form">
            <form method="POST" action="{{ route('feedback.store') }}">
                @csrf

                <div class="form-group">
                    <label>{{ __('feedback.type_label') }}</label>
                    <div class="fb-types">
                        @foreach([
                            \App\Models\Feedback::TYPE_BUG,
                            \App\Models\Feedback::TYPE_PROMPT,
                            \App\Models\Feedback::TYPE_FEATURE,
                            \App\Models\Feedback::TYPE_OTHER,
                        ] as $i => $type)
                            <label class="fb-type">
                                <input type="radio" name="type" value="{{ $type }}"
                                       @checked(old('type', $presetType) === $type)>
                                <span class="fb-type-box">
                                    <span class="fb-type-name">
                                        <svg class="fb-type-icon" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                                            @switch($type)
                                                @case(\App\Models\Feedback::TYPE_BUG)
                                                    <rect x="7.5" y="8.5" width="9" height="12" rx="4.5"/>
                                                    <path d="M9.5 8.5a2.5 2.5 0 0 1 5 0M12 12v8.5M7.5 13.5H4m16 0h-3.5M7.6 17.5 4.5 19m15 0-3.1-1.5M8.2 10 5 8.5m14 0L15.8 10M10.2 5 9 3.5m4.8 1.5L15 3.5"/>
                                                    @break
                                                @case(\App\Models\Feedback::TYPE_PROMPT)
                                                    <path d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"/>
                                                    @break
                                                @case(\App\Models\Feedback::TYPE_FEATURE)
                                                    <path d="M12 18v-5.25m0 0a6.01 6.01 0 0 0 1.5-.189m-1.5.189a6.01 6.01 0 0 1-1.5-.189m3.75 7.478a12.06 12.06 0 0 1-4.5 0m3.75 2.383a14.406 14.406 0 0 1-3 0M14.25 18v-.192c0-.983.658-1.823 1.508-2.316a7.5 7.5 0 1 0-7.517 0c.85.493 1.509 1.333 1.509 2.316V18"/>
                                                    @break
                                                @default
                                                    <path d="M20.25 12c0 4.14-3.694 7.5-8.25 7.5a9.1 9.1 0 0 1-3.34-.63L3.75 20.25l1.53-3.9A7.08 7.08 0 0 1 3.75 12c0-4.14 3.694-7.5 8.25-7.5s8.25 3.36 8.25 7.5Z"/>
                                                    <path d="M8.25 12h.01M12 12h.01M15.75 12h.01" stroke-width="2.2"/>
                                            @endswitch
                                        </svg>
                                        {{ __('feedback.type_'.$type) }}
                                    </span>
                                    <span class="fb-type-hint">{{ __('feedback.type_'.$type.'_hint') }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                    @error('type') <div class="mg-error">{{ $message }}</div> @enderror
                </div>

                <div class="form-group">
                    <label for="fb-message">{{ __('feedback.message_label') }}</label>
                    <textarea class="form-control fb-textarea" id="fb-message" name="message" rows="7"
                              maxlength="2000" required
                              placeholder="{{ __('feedback.message_placeholder') }}">{{ old('message') }}</textarea>
                    <div class="fb-counter" id="fb-counter" aria-live="polite"></div>
                    @error('message') <div class="mg-error">{{ $message }}</div> @enderror
                </div>

                @auth
                    {{-- 會員:聯絡方式直接用帳號的 email(FeedbackController::store),不給填 --}}
                    <div class="form-group">
                        <label>{{ __('feedback.member_label') }}</label>
                        <div class="fb-member">
                            <span class="fb-member-avatar" aria-hidden="true">{{ mb_substr(auth()->user()->name, 0, 1) }}</span>
                            <span class="fb-member-id">
                                <span class="fb-member-name">{{ auth()->user()->name }}</span>
                                <span class="fb-member-email">{{ auth()->user()->email }}</span>
                            </span>
                        </div>
                        <div class="fb-hint">{{ __('feedback.member_hint') }}</div>
                    </div>
                @else
                    <div class="form-group">
                        <label for="fb-contact">{{ __('feedback.contact_label') }}</label>
                        <input type="text" class="form-control" id="fb-contact" name="contact" maxlength="120"
                               placeholder="{{ __('feedback.contact_placeholder') }}" value="{{ old('contact') }}">
                        <div class="fb-hint">{{ __('feedback.contact_hint') }}</div>
                        @error('contact') <div class="mg-error">{{ $message }}</div> @enderror
                    </div>
                @endauth

                <div class="form-group">
                    <label for="fb-page">{{ __('feedback.page_label') }}</label>
                    <input type="text" class="form-control" id="fb-page" name="page_path" maxlength="500"
                           inputmode="url" placeholder="{{ url('/tw/wheel-game') }}" value="{{ old('page_path', $pagePath) }}">
                    <div class="fb-hint">{{ __('feedback.page_hint') }}</div>
                    @error('page_path') <div class="mg-error">{{ $message }}</div> @enderror
                </div>

                {{-- 蜜罐。機器人會把每個 input 都填滿,真人看不到這一格。
                     用 position:absolute 移出畫面而不是 display:none —— 有些
                     爬蟲會跳過 hidden 的欄位,那就擋不到了。 --}}
                <div class="fb-hp" aria-hidden="true">
                    <label for="fb-website">Website</label>
                    <input type="text" id="fb-website" name="website" tabindex="-1" autocomplete="off">
                </div>

                <button type="submit" class="btn btn-gold btn-submit">{{ __('feedback.submit') }}</button>
            </form>
        </div>
        </div>
    @endif
</div>

<style>
/* 類型選擇:四張可點的卡,而不是一個下拉 —— 選項只有四個,攤開來比較快 */
.fb-types{display:grid;grid-template-columns:repeat(2,1fr);gap:10px}
.fb-type{position:relative;display:block;cursor:pointer}
.fb-type input{position:absolute;opacity:0;width:0;height:0}
.fb-type-box{display:flex;flex-direction:column;gap:3px;height:100%;
    padding:12px 14px;border:1px solid var(--border);border-radius:12px;
    background:var(--surface);transition:border-color .16s,background .16s}
.fb-type-name{display:flex;align-items:center;gap:6px;font-size:.9rem;font-weight:600;color:var(--text)}
/* 名稱前的小圖示:跟字一樣大、灰色,選中時跟著變主色 */
.fb-type-icon{flex:0 0 auto;width:15px;height:15px;color:var(--text-dim);transition:color .16s}
.fb-type input:checked + .fb-type-box .fb-type-icon{color:var(--accent)}
.fb-type-hint{font-size:.74rem;color:var(--text-dim);line-height:1.5}
.fb-type:hover .fb-type-box{border-color:var(--text-dim)}
.fb-type input:checked + .fb-type-box{border-color:var(--accent);
    background:rgba(var(--glow-rgb),.09)}
.fb-type input:focus-visible + .fb-type-box{outline:none;
    box-shadow:0 0 0 3px rgba(var(--glow-rgb),.24)}

.fb-textarea{min-height:150px;resize:vertical;line-height:1.7}
.fb-counter{margin-top:6px;font-size:.74rem;color:var(--text-dim);text-align:right}
.fb-hint{margin-top:6px;font-size:.76rem;color:var(--text-dim);line-height:1.6}

/* 蜜罐:移出畫面而不是 display:none */
.fb-hp{position:absolute;left:-9999px;top:auto;width:1px;height:1px;overflow:hidden}

/* 送出後的確認畫面 */
.fb-done{max-width:520px;margin:0 auto;text-align:center;
    padding:40px 28px;background:var(--surface);border:1px solid var(--border);
    border-radius:16px}
.fb-done-mark{display:inline-flex;align-items:center;justify-content:center;
    width:52px;height:52px;margin-bottom:16px;border-radius:50%;
    color:#34d399;background:rgba(52,211,153,.12);border:1px solid rgba(52,211,153,.3)}
.fb-done-mark svg{width:26px;height:26px}
.fb-done h2{font-size:1.2rem;margin-bottom:10px;color:var(--text)}
.fb-done p{font-size:.88rem;color:var(--text-dim);line-height:1.75;margin-bottom:22px}
.fb-done .fb-done-ref{margin-top:-10px;font-size:.8rem;font-variant-numeric:tabular-nums}

/* 會員資訊卡:取代聯絡方式那一格 */
.fb-member{display:flex;align-items:center;gap:12px;padding:12px 14px;
    border:1px solid var(--border);border-radius:12px;background:var(--bg)}
.fb-member-avatar{flex:0 0 auto;display:grid;place-items:center;width:36px;height:36px;
    border-radius:50%;background:var(--surface2);color:var(--text);font-weight:700;font-size:.9rem}
.fb-member-id{display:flex;flex-direction:column;min-width:0}
.fb-member-name{font-size:.9rem;font-weight:600;color:var(--text)}
.fb-member-email{font-size:.8rem;color:var(--text-dim);overflow:hidden;text-overflow:ellipsis;white-space:nowrap}

/* 版面:mg-tool-page 是 560px 的單欄(小工具共用),回報頁在桌機另外放寬成兩欄 */
@media(min-width:900px){
    .fb-page{max-width:1080px;padding:64px 32px}
    .fb-layout{display:grid;grid-template-columns:minmax(0,5fr) minmax(0,7fr);gap:48px;align-items:start}
    .fb-aside{position:sticky;top:88px}
    .fb-aside .mg-tool-hero{text-align:left;margin-bottom:28px}
    .fb-aside .mg-tool-hero h1{justify-content:flex-start;font-size:2rem}
    .fb-aside .mg-tool-features{grid-template-columns:1fr;gap:10px}
    .fb-aside .mg-tool-feature{display:flex;align-items:center;gap:12px;text-align:left;padding:14px 16px}
    .fb-aside .mg-tool-feature .mg-feature-icon{margin:0}
    .fb-aside .mg-tool-tip{text-align:left}
    .fb-page .mg-tool-form{padding:32px 36px}
}
/* 平板:還是單欄,但不用擠在 560px 裡 */
@media(min-width:640px) and (max-width:899px){
    .fb-page{max-width:720px}
}

@media(max-width:520px){
    .fb-types{grid-template-columns:1fr}
}
</style>

<script>
(function(){
    var ta = document.getElementById('fb-message');
    var out = document.getElementById('fb-counter');
    if(!ta || !out) return;

    var max = ta.getAttribute('maxlength');
    var tpl = @json(__('feedback.message_counter', ['n' => '__N__', 'max' => '__MAX__']));

    function paint(){
        out.textContent = tpl.replace('__N__', ta.value.length).replace('__MAX__', max);
    }
    ta.addEventListener('input', paint);
    paint();
})();
</script>
@endsection
