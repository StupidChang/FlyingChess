{{-- 玩法指南底部的回報入口。帶上現在這一頁當 ?from=,回報表單會自動填好
     「在哪一頁遇到的」—— 使用者少打一行,我們也知道是哪一篇讓人卡住。 --}}
<aside class="gd-feedback" aria-labelledby="gd-feedback-title">
    <div class="gd-feedback-text">
        <h2 id="gd-feedback-title">{{ __('guides.feedback_title') }}</h2>
        <p>{{ __('guides.feedback_desc') }}</p>
    </div>
    <a class="btn btn-outline gd-feedback-btn"
       href="{{ route('feedback.show', ['from' => request()->fullUrl()]) }}">{{ __('guides.feedback_cta') }} →</a>
</aside>

@once
<style>
.gd-feedback{display:flex;align-items:center;justify-content:space-between;gap:20px;
  margin-top:40px;padding:22px 24px;border:1px solid var(--border);border-radius:14px;
  background:linear-gradient(158deg,var(--surface) 0%,var(--surface2) 100%)}
.gd-feedback-text h2{font-size:1.02rem;font-weight:700;color:var(--text);margin:0 0 6px}
.gd-feedback-text p{font-size:.88rem;line-height:1.75;color:var(--text-dim);margin:0;max-width:52ch}
.gd-feedback-btn{flex:0 0 auto;white-space:nowrap}
@media(max-width:600px){
  .gd-feedback{flex-direction:column;align-items:stretch;padding:18px}
  .gd-feedback-btn{text-align:center}
}
</style>
@endonce
