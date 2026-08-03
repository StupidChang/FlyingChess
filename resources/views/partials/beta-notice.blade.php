{{--
  測試版公告 —— 桌機在右下角浮一張小卡,手機收成底部一條細的。

  為什麼要有:題目與功能都還在調整,資料也可能重置。先講明白,使用者遇到變動
  時才不會當成故障。詳細理由與開關寫在 config/beta.php。

  行為:
    - 進站 700ms 後才浮出來,不跟首屏搶注意力,也不影響 LCP
    - 關掉之後記在 localStorage,同一個瀏覽器就不再出現(版號一動會重新宣布)
    - 已經關過的人不會看到任何一格閃現 —— 元素帶 hidden 出場,由 JS 決定要不要顯示
    - z-index 250:蓋得住 sticky header(100),但仍讓 .modal(300)蓋住它
    - 手機只留「徽章 + 一句話 + 關閉」,說明文與按鈕收起來,才不會擋住畫面
    - 尊重 prefers-reduced-motion:直接出現,不做位移
--}}
@if(config('beta.notice'))
    @php
        $betaKey = 'beta_notice_v'.config('beta.notice_version');
        $betaReportTo = config('support.email');
    @endphp

    <div class="beta-note" id="betaNote" role="status" aria-live="polite" data-key="{{ $betaKey }}" hidden>
        <div class="beta-note-rule" aria-hidden="true"></div>
        <button type="button" class="beta-note-x" aria-label="{{ __('ui.beta_close') }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"
                 stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>
        </button>
        <div class="beta-note-body">
            <span class="beta-note-badge">{{ __('ui.beta_badge') }}</span>
            <p class="beta-note-title">{{ __('ui.beta_title') }}</p>
            <p class="beta-note-desc">{{ __('ui.beta_desc') }}</p>
            <div class="beta-note-actions">
                <button type="button" class="beta-note-ok">{{ __('ui.beta_ok') }}</button>
                @if($betaReportTo)
                    {{-- 有客服信箱才給這個連結,不然「歡迎回報」會是個死路 --}}
                    <a class="beta-note-report" href="mailto:{{ $betaReportTo }}">{{ __('ui.beta_report') }}</a>
                @endif
            </div>
        </div>
    </div>

    <style>
    .beta-note{
        position:fixed;right:22px;bottom:22px;z-index:250;
        width:336px;max-width:calc(100vw - 32px);
        background:linear-gradient(158deg,var(--surface) 0%,var(--surface2) 100%);
        border:1px solid var(--border);border-radius:16px;overflow:hidden;
        box-shadow:0 20px 44px -14px rgba(0,0,0,.62),0 2px 8px rgba(0,0,0,.28);
        opacity:0;transform:translateY(14px) scale(.985);
        transition:opacity .46s cubic-bezier(.22,.72,.3,1),
                   transform .46s cubic-bezier(.22,.72,.3,1);
    }
    .beta-note[hidden]{display:none}
    .beta-note.is-in{opacity:1;transform:none}
    .beta-note.is-out{opacity:0;transform:translateY(10px) scale(.98);pointer-events:none}

    /* 頂邊一條金→粉的細線,是這張卡唯一的裝飾 */
    .beta-note-rule{height:2px;opacity:.9;
        background:linear-gradient(90deg,var(--gold) 0%,var(--accent) 58%,transparent 100%)}

    /* 右上角一團柔光,讓卡片看起來是「浮」著而不是硬貼上去 */
    .beta-note::after{content:'';position:absolute;top:-34px;right:-24px;
        width:176px;height:124px;pointer-events:none;
        background:radial-gradient(ellipse,rgba(var(--glow-rgb),.16),transparent 70%)}

    .beta-note-body{position:relative;z-index:1;padding:15px 18px 17px}

    .beta-note-badge{display:inline-block;font-size:.62rem;font-weight:700;
        letter-spacing:.16em;text-transform:uppercase;color:var(--gold2);
        background:rgba(217,164,65,.12);border:1px solid rgba(217,164,65,.32);
        padding:3px 9px;border-radius:999px;white-space:nowrap}

    .beta-note-title{margin:10px 0 5px;font-size:.95rem;font-weight:700;
        color:var(--text);line-height:1.45}
    .beta-note-desc{margin:0;font-size:.8rem;color:var(--text-dim);line-height:1.7}

    .beta-note-actions{display:flex;align-items:center;gap:14px;margin-top:13px}
    .beta-note-ok{font:inherit;font-size:.78rem;font-weight:600;
        background:none;border:1px solid var(--border);color:var(--text);
        padding:6px 15px;border-radius:999px;cursor:pointer;
        transition:border-color .18s,color .18s,background .18s}
    .beta-note-ok:hover,.beta-note-ok:focus-visible{border-color:var(--accent);
        color:var(--accent);background:rgba(var(--glow-rgb),.09);outline:none}
    .beta-note-report{font-size:.75rem;color:var(--text-dim);
        text-decoration:underline;text-underline-offset:3px;
        text-decoration-color:var(--border);transition:color .18s}
    .beta-note-report:hover,.beta-note-report:focus-visible{color:var(--accent);outline:none}

    .beta-note-x{position:absolute;top:10px;right:10px;z-index:2;
        width:28px;height:28px;display:flex;align-items:center;justify-content:center;
        background:none;border:0;border-radius:9px;color:var(--text-dim);
        cursor:pointer;transition:color .18s,background .18s}
    .beta-note-x:hover,.beta-note-x:focus-visible{color:var(--text);
        background:var(--surface2);outline:none}
    .beta-note-x svg{width:14px;height:14px;display:block}

    /* 手機:收成底部一條細的,只留徽章、一句話、關閉 */
    @media(max-width:768px){
        .beta-note{left:10px;right:10px;bottom:10px;width:auto;max-width:none;
            border-radius:12px}
        .beta-note::after{display:none}
        .beta-note-body{display:flex;align-items:center;gap:9px;
            padding:9px 42px 9px 12px}
        .beta-note-badge{flex:0 0 auto;font-size:.58rem;letter-spacing:.12em;
            padding:2px 7px}
        .beta-note-title{margin:0;font-size:.78rem;font-weight:600;line-height:1.4}
        .beta-note-desc,.beta-note-actions{display:none}
        .beta-note-x{top:50%;right:6px;transform:translateY(-50%);
            width:34px;height:34px}
    }

    @media (prefers-reduced-motion: reduce){
        .beta-note{transition:none;transform:none}
        .beta-note.is-out{transform:none}
    }
    </style>

    <script>
    (function(){
        var el = document.getElementById('betaNote');
        if(!el) return;

        // 無痕模式下讀 localStorage 有可能直接 throw,包起來以免整段掛掉
        var store = null;
        try { store = window.localStorage; } catch(e) {}

        var key = el.dataset.key;
        if(store && store.getItem(key) === '1'){ el.remove(); return; }

        setTimeout(function(){
            el.hidden = false;
            // 先讓瀏覽器套用 hidden 移除後的初始狀態,下一格畫面才會有轉場
            requestAnimationFrame(function(){ el.classList.add('is-in'); });
        }, 700);

        function dismiss(){
            el.classList.remove('is-in');
            el.classList.add('is-out');
            if(store){ try { store.setItem(key, '1'); } catch(e) {} }
            setTimeout(function(){ el.remove(); }, 500);
        }

        el.querySelector('.beta-note-x').addEventListener('click', dismiss);
        var ok = el.querySelector('.beta-note-ok');
        if(ok) ok.addEventListener('click', dismiss);
    })();
    </script>
@endif
