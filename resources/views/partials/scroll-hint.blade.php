{{--
  向下捲動提示 —— 用在「上一個區塊剛好滿版、使用者不會知道下面還有東西」的地方。

  參數:
    $target 必填,要捲到的元素選擇器,例如 '#cw-root'

  設計取捨:
    - 用「呼吸式的明暗 + 微幅上下位移」而不是硬閃,避免廉價感也不刺眼
    - 可點擊,點了平滑捲到目標(不只是裝飾)
    - 目標一進入視窗就淡出並停用 —— 提示的任務結束就該消失
    - IntersectionObserver 不支援時 fallback 成一直顯示,不會壞掉
    - 尊重 prefers-reduced-motion:停止動畫但保留可點擊
--}}
@php $target = $target ?? '#cw-root'; @endphp

<div class="sh" data-target="{{ $target }}" id="sh-{{ md5($target) }}">
    <button type="button" class="sh-btn" aria-label="{{ __('ui.scroll_more') }}">
        <span class="sh-text">{{ __('ui.scroll_more') }}</span>
        <span class="sh-chev" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M6 9l6 6 6-6"/>
            </svg>
        </span>
    </button>
</div>

<style>
.sh{display:flex;justify-content:center;padding:8px 16px 34px;
    transition:opacity .45s ease, visibility .45s ease}
.sh.is-done{opacity:0;visibility:hidden;pointer-events:none}

.sh-btn{position:relative;display:flex;flex-direction:column;align-items:center;
    gap:7px;padding:10px 22px 12px;background:none;border:0;cursor:pointer;
    color:var(--text-dim);border-radius:14px;
    transition:color .2s}
.sh-btn:hover,.sh-btn:focus-visible{color:var(--accent);outline:none}
.sh-btn:focus-visible{box-shadow:0 0 0 3px rgba(var(--glow-rgb),.22)}

/* 背後的柔光,讓提示在深色背景上「浮」起來而不是硬貼著 */
.sh-btn::before{content:'';position:absolute;left:50%;top:46%;
    width:190px;height:64px;transform:translate(-50%,-50%);
    background:radial-gradient(ellipse, rgba(var(--glow-rgb),.14), transparent 70%);
    opacity:0;transition:opacity .3s;pointer-events:none}
.sh-btn:hover::before,.sh-btn:focus-visible::before{opacity:1}

.sh-text{font-size:.76rem;letter-spacing:.14em;font-weight:600;
    text-transform:uppercase;white-space:nowrap;
    animation:sh-breathe 2.6s ease-in-out infinite}

.sh-chev{display:block;width:22px;height:22px;
    animation:sh-nudge 2.6s ease-in-out infinite}
.sh-chev svg{width:100%;height:100%;display:block}

/* 呼吸式明暗,不是硬閃 */
@keyframes sh-breathe{
    0%,100%{opacity:.55}
    50%    {opacity:1}
}
/* 箭頭同步微幅下沉,暗示方向 */
@keyframes sh-nudge{
    0%,100%{transform:translateY(-2px);opacity:.6}
    50%    {transform:translateY(3px); opacity:1}
}

@media(max-width:520px){
    .sh{padding-bottom:26px}
    .sh-text{font-size:.72rem;letter-spacing:.1em}
}

@media (prefers-reduced-motion: reduce){
    .sh-text,.sh-chev{animation:none;opacity:.85}
    .sh{transition:none}
}
</style>

<script>
(function(){
    var host = document.currentScript ? document.currentScript.previousElementSibling : null;
    // 上一個兄弟節點是 <style>,再往前才是 .sh
    while(host && !host.classList.contains('sh')) host = host.previousElementSibling;
    if(!host) return;

    var sel = host.dataset.target;
    var btn = host.querySelector('.sh-btn');
    var target = sel ? document.querySelector(sel) : null;

    btn.addEventListener('click', function(){
        if(!target) return;
        var reduce = window.matchMedia
            && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        target.scrollIntoView({behavior: reduce ? 'auto' : 'smooth', block: 'start'});
    });

    // 目標進入視窗就淡出 —— 提示已經完成任務
    if(target && 'IntersectionObserver' in window){
        var io = new IntersectionObserver(function(entries){
            entries.forEach(function(e){
                if(e.isIntersecting){
                    host.classList.add('is-done');
                    io.disconnect();
                }
            });
        }, {rootMargin: '0px 0px -25% 0px'});
        io.observe(target);
    }
})();
</script>
