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
<div class="mg-tool-page">
    <div class="mg-tool-hero">
        <h1>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.501 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z"/></svg>
            {{ __('feedback.h1') }}
        </h1>
        <p>{{ __('feedback.hero_sub') }}</p>
    </div>

    @if(session('feedback_ok'))
        {{-- 送出後停在同一頁,但整塊換成確認畫面。redirect 而不是直接 render,
             是為了讓重新整理不會重送一次(POST/redirect/GET)。 --}}
        <div class="fb-done" role="status">
            <span class="fb-done-mark" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"
                     stroke-linecap="round" stroke-linejoin="round"><path d="M4.5 12.75l6 6 9-13.5"/></svg>
            </span>
            <h2>{{ __('feedback.thanks_title') }}</h2>
            <p>{{ __('feedback.thanks_body') }}</p>
            <a href="{{ route('feedback.show') }}" class="btn btn-outline btn-sm">{{ __('feedback.thanks_again') }}</a>
        </div>
    @else
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
                                       @checked(old('type', \App\Models\Feedback::TYPE_BUG) === $type)>
                                <span class="fb-type-box">
                                    <span class="fb-type-name">{{ __('feedback.type_'.$type) }}</span>
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

                <div class="form-group">
                    <label for="fb-contact">{{ __('feedback.contact_label') }}</label>
                    <input type="text" class="form-control" id="fb-contact" name="contact" maxlength="120"
                           placeholder="{{ __('feedback.contact_placeholder') }}" value="{{ old('contact') }}">
                    <div class="fb-hint">{{ __('feedback.contact_hint') }}</div>
                    @error('contact') <div class="mg-error">{{ $message }}</div> @enderror
                </div>

                <div class="form-group">
                    <label for="fb-page">{{ __('feedback.page_label') }}</label>
                    <input type="text" class="form-control" id="fb-page" name="page_path" maxlength="200"
                           placeholder="/tw/wheel-game" value="{{ old('page_path', $pagePath) }}">
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
            <div class="mg-tool-tip">{{ __('feedback.tip') }}</div>
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
.fb-type-name{font-size:.9rem;font-weight:600;color:var(--text)}
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
