{{--
    年齡確認覆蓋層(age_gate_mode = overlay,預設)。

    和 partials/age-gate-full 的差別只有一個,但那個差別是重點:這一份是**蓋在真正
    的頁面上**,不是取代它。所以沒確認年齡的訪客與 Googlebot 拿到的 HTML 完全一樣,
    不構成 cloaking —— 那份完整說明在 config/content.php 的 age_gate_mode。

    刻意不用 <dialog> 也不依賴 JS:確認的動作是一個普通的表單 POST,關掉 JS 一樣
    能用,爬蟲也不會因為執行不了腳本就卡在這裡。遮蔽與鎖捲動全部由 CSS 完成
    (body.age-locked),見 public/css/app.css。
--}}
<div class="age-overlay" role="dialog" aria-modal="true" aria-labelledby="age-overlay-title">
    <div class="age-overlay-card">
        <span class="age-badge" aria-hidden="true">18+</span>
        <h2 id="age-overlay-title">{{ __('legal.age_gate_title') }}</h2>
        <p class="warning">{{ __('legal.age_gate_text') }}</p>
        <p>{{ __('legal.age_gate_consent') }}</p>

        <div class="age-gate-btns">
            <form action="{{ route('age.verify') }}" method="POST">
                @csrf
                <button type="submit" class="btn-enter">{{ __('legal.enter_18') }}</button>
            </form>
            <a href="https://www.google.com" class="btn-leave" rel="nofollow noopener">{{ __('legal.leave') }}</a>
        </div>

        <div class="age-gate-links">
            <a href="{{ \App\Support\LocaleHelper::localizedUrl(app()->getLocale(), 'privacy') }}">{{ __('legal.privacy_title') }}</a>
            <a href="{{ \App\Support\LocaleHelper::localizedUrl(app()->getLocale(), 'terms') }}">{{ __('legal.terms_title') }}</a>
        </div>
    </div>
</div>
