{{--
  自訂轉盤(帶權重)—— 放在命運轉盤下方。
  使用者一項一項輸入內容與佔比,轉盤即時依比例重繪,轉出結果按權重加權。

  設計說明:
    - 純前端、無後端:內容只存在瀏覽器的 localStorage,不上傳、不與他人共享,
      因此不經過 NoBlockedWords 審核(沒有對外曝光的風險面)。
    - 佔比總和不必剛好 100:幾何上一律依比例換算(pct_i / total * 360),
      介面同時顯示總和與換算後的實際佔比,避免使用者被「必須湊到 100」卡住。
    - 轉盤本體旋轉、指針固定在 12 點鐘 —— 與命運轉盤的操作習慣一致。
--}}
<section class="cw" id="cw-root" aria-labelledby="cw-heading">
    <h2 class="cw-heading" id="cw-heading">{{ __('minigame.cw_title') }}</h2>
    <p class="cw-sub">{{ __('minigame.cw_subtitle') }}</p>

    <div class="cw-layout">
        {{-- 左:轉盤 --}}
        <div class="cw-wheel-col">
            <div class="cw-stage" id="cw-stage">
                <div class="cw-pointer"></div>
                <canvas id="cw-canvas" class="cw-canvas" role="img"
                        aria-label="{{ __('minigame.cw_title') }}"></canvas>
                <div class="cw-hub"></div>
            </div>

            <div class="cw-result" aria-live="polite">
                <span class="cw-result-text" id="cw-result"></span>
            </div>

            <button type="button" class="btn btn-primary btn-xl cw-spin" id="cw-spin">
                {{ __('minigame.wheel_spin_btn') }}
            </button>
        </div>

        {{-- 右:選項編輯 --}}
        <div class="cw-form-col">
            <form class="cw-add" id="cw-add-form" autocomplete="off">
                <div class="cw-add-row">
                    <input type="text" class="form-control cw-input-text" id="cw-input-text"
                           maxlength="24" required
                           placeholder="{{ __('minigame.cw_placeholder') }}"
                           aria-label="{{ __('minigame.wheel_segment') }}">
                    <div class="cw-pct-wrap">
                        <input type="number" class="form-control cw-input-pct" id="cw-input-pct"
                               min="1" max="100" step="1" value="10"
                               aria-label="{{ __('minigame.cw_percent') }}">
                        <span class="cw-pct-sign">%</span>
                    </div>
                    <button type="submit" class="btn btn-primary cw-add-btn">
                        {{ __('minigame.wheel_add') }}
                    </button>
                </div>
                <p class="cw-err" id="cw-err" role="alert" hidden></p>
            </form>

            <ul class="cw-list" id="cw-list"></ul>

            <p class="cw-empty" id="cw-empty">{{ __('minigame.cw_empty') }}</p>

            <div class="cw-total" id="cw-total"></div>

            <div class="cw-tools">
                <button type="button" class="btn btn-sm btn-outline" id="cw-even">
                    {{ __('minigame.cw_even') }}
                </button>
                <button type="button" class="btn btn-sm btn-outline" id="cw-clear">
                    {{ __('minigame.wheel_clear') }}
                </button>
            </div>

            {{-- 設定完成 → 進入與其他轉盤相同的遊戲流程 --}}
            <button type="button" class="btn btn-gold btn-full cw-start" id="cw-start">
                {{ __('minigame.cw_start') }}
            </button>
            <p class="cw-start-hint" id="cw-start-hint">{{ __('minigame.cw_start_hint') }}</p>

            @auth
                @if(auth()->user()->hasVerifiedEmail())
                    {{-- 儲存區:僅登入且已驗證信箱者可見 --}}
                    <div class="cw-save">
                        <div class="cw-save-row">
                            <input type="text" class="form-control cw-save-name" id="cw-save-name"
                                   maxlength="40" placeholder="{{ __('minigame.cw_save_placeholder') }}"
                                   aria-label="{{ __('minigame.cw_save_placeholder') }}">
                            <button type="button" class="btn btn-primary cw-save-btn" id="cw-save-btn">
                                {{ __('minigame.cw_save') }}
                            </button>
                        </div>
                        <p class="cw-save-msg" id="cw-save-msg" role="status" hidden></p>

                        <div class="cw-saved" id="cw-saved" hidden>
                            <div class="cw-saved-label">{{ __('minigame.cw_saved_list') }}</div>
                            <ul class="cw-saved-list" id="cw-saved-list"></ul>
                        </div>
                    </div>
                @endif
            @endauth
        </div>
    </div>
</section>

<style>
.cw{max-width:900px;margin:40px auto 8px;padding:24px 16px 8px;
    border-top:1px solid var(--border)}
.cw-heading{font-size:1.2rem;font-weight:800;margin:0 0 4px;text-align:center}
.cw-sub{margin:0 0 22px;font-size:.84rem;color:var(--text-dim);text-align:center}

/* 桌機兩欄、窄螢幕單欄 */
.cw-layout{display:grid;grid-template-columns:minmax(0,320px) minmax(0,1fr);
    gap:28px;align-items:start}
@media(max-width:768px){
    .cw-layout{grid-template-columns:1fr;gap:22px;justify-items:center}
    .cw-form-col{width:100%}
}

.cw-wheel-col{display:flex;flex-direction:column;align-items:center;gap:14px}
.cw-stage{--cw-size:min(300px,80vw);position:relative;
    width:var(--cw-size);height:var(--cw-size)}

/* 指針固定在 12 點鐘,轉盤本體旋轉 */
.cw-pointer{position:absolute;top:calc(var(--cw-size) * -.045);left:50%;
    transform:translateX(-50%);z-index:3;width:0;height:0;
    border-left:calc(var(--cw-size) * .042) solid transparent;
    border-right:calc(var(--cw-size) * .042) solid transparent;
    border-top:calc(var(--cw-size) * .085) solid var(--accent);
    filter:drop-shadow(0 2px 5px rgba(0,0,0,.5))}
.cw-canvas{position:relative;z-index:1;display:block;
    width:var(--cw-size);height:var(--cw-size);border-radius:50%;
    box-shadow:0 0 24px rgba(0,0,0,.38);
    transition:transform 4.4s cubic-bezier(.17,.67,.05,.99)}
.cw-hub{position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);
    width:calc(var(--cw-size) * .15);height:calc(var(--cw-size) * .15);
    border-radius:50%;z-index:2;pointer-events:none;
    background:radial-gradient(circle at 38% 34%,#fff,#f2e3ea);
    border:2px solid var(--accent);
    box-shadow:0 0 12px rgba(244,63,94,.4),0 2px 6px rgba(0,0,0,.3)}

.cw-result{min-height:2.6em;display:flex;align-items:center;justify-content:center;
    text-align:center;padding:0 6px}
.cw-result-text{font-size:1.05rem;font-weight:700;line-height:1.45;
    opacity:0;transform:translateY(6px);transition:opacity .3s,transform .3s}
.cw-result-text.show{opacity:1;transform:translateY(0)}
.cw-spin{min-width:min(240px,72vw)}
.cw-spin[disabled]{opacity:.55}

/* 新增列:窄螢幕改為兩行,% 與按鈕同排 */
.cw-add-row{display:grid;grid-template-columns:minmax(0,1fr) 92px auto;gap:8px}
@media(max-width:520px){
    .cw-add-row{grid-template-columns:minmax(0,1fr) 84px;grid-auto-rows:auto}
    .cw-add-btn{grid-column:1 / -1}
}
/* ---- 輸入框質感 ----
   基底沿用專案的 .form-control(surface2 底 + border + accent focus ring),
   這裡再疊三層:內凹陰影做出「刻進表面」的感覺、hover 時邊框微亮、
   focus 時 accent 光暈加上 1px 上浮。數值刻意收斂,維持整站的暗色調性。 */
.cw .form-control{
    background:linear-gradient(180deg,
        color-mix(in srgb, var(--surface2) 88%, #000) 0%,
        var(--surface2) 100%);
    box-shadow:inset 0 1px 2px rgba(0,0,0,.35);
    transition:border-color .18s, box-shadow .18s, transform .18s, background .18s;
}
.cw .form-control:hover:not(:focus){
    border-color:color-mix(in srgb, var(--border) 55%, var(--accent));
}
.cw .form-control:focus{
    transform:translateY(-1px);
    box-shadow:inset 0 1px 2px rgba(0,0,0,.25),
               0 0 0 3px rgba(var(--glow-rgb),.18),
               0 4px 14px rgba(var(--glow-rgb),.12);
}
.cw .form-control::placeholder{color:var(--text-dim);opacity:.75}

.cw-pct-wrap{position:relative}
.cw-input-pct{padding-right:28px!important;text-align:right;
    font-variant-numeric:tabular-nums;font-weight:600}
/* 隱藏 number input 的上下箭頭 —— 版面窄,箭頭會擠壓數字 */
.cw-input-pct::-webkit-outer-spin-button,
.cw-input-pct::-webkit-inner-spin-button{-webkit-appearance:none;margin:0}
.cw-input-pct{-moz-appearance:textfield;appearance:textfield}
.cw-pct-sign{position:absolute;right:10px;top:50%;transform:translateY(-50%);
    font-size:.78rem;color:var(--text-dim);pointer-events:none;
    transition:color .18s}
.cw-pct-wrap:focus-within .cw-pct-sign{color:var(--accent)}

.cw-err{margin:8px 0 0;font-size:.78rem;color:var(--accent);
    display:flex;align-items:center;gap:5px}
.cw-err::before{content:'';width:5px;height:5px;border-radius:50%;
    background:currentColor;flex:0 0 auto}

.cw-list{list-style:none;margin:16px 0 0;padding:0;display:flex;
    flex-direction:column;gap:7px}
.cw-item{display:grid;grid-template-columns:14px minmax(0,1fr) 74px 32px;
    gap:9px;align-items:center;
    background:var(--surface);border:1px solid var(--border);
    border-radius:9px;padding:8px 10px}
.cw-dot{width:14px;height:14px;border-radius:50%;flex:0 0 auto}
.cw-name{font-size:.88rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.cw-item-pct{width:100%;padding:5px 7px;text-align:right;font-size:.82rem;
    font-variant-numeric:tabular-nums;font-weight:600;
    background:linear-gradient(180deg,
        color-mix(in srgb, var(--surface2) 86%, #000) 0%, var(--surface2) 100%);
    color:var(--text);border:1px solid var(--border);border-radius:7px;
    box-shadow:inset 0 1px 2px rgba(0,0,0,.32);
    transition:border-color .18s, box-shadow .18s}
.cw-item-pct::-webkit-outer-spin-button,
.cw-item-pct::-webkit-inner-spin-button{-webkit-appearance:none;margin:0}
.cw-item-pct{-moz-appearance:textfield;appearance:textfield}
.cw-item-pct:hover:not(:focus){border-color:color-mix(in srgb, var(--border) 55%, var(--accent))}
.cw-item-pct:focus{outline:none;border-color:var(--accent);
    box-shadow:inset 0 1px 2px rgba(0,0,0,.22),0 0 0 3px rgba(var(--glow-rgb),.16)}
.cw-eff{display:block;font-size:.68rem;color:var(--text-dim);text-align:right;
    margin-top:2px;font-variant-numeric:tabular-nums}
.cw-del{background:none;border:none;color:var(--text-dim);cursor:pointer;
    font-size:1.15rem;line-height:1;padding:2px 4px;border-radius:6px}
.cw-del:hover,.cw-del:focus-visible{color:var(--accent);background:var(--surface2)}

.cw-empty{margin:16px 0 0;font-size:.82rem;color:var(--text-dim);text-align:center}
.cw-total{margin:14px 0 0;font-size:.82rem;color:var(--text-dim);
    font-variant-numeric:tabular-nums}
.cw-total b{color:var(--text)}
.cw-total.warn b{color:var(--accent)}
.cw-tools{display:flex;gap:8px;margin-top:14px;flex-wrap:wrap}

.cw-start{margin-top:18px}
.cw-start-hint{margin:8px 0 0;font-size:.76rem;color:var(--text-dim);text-align:center}

/* 儲存區 */
.cw-save{margin-top:22px;padding-top:18px;border-top:1px dashed var(--border)}
.cw-save-row{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:8px}
@media(max-width:520px){.cw-save-row{grid-template-columns:1fr}}
.cw-save-msg{margin:8px 0 0;font-size:.78rem;color:var(--text-dim)}
.cw-save-msg.ok{color:#4ade80}
.cw-save-msg.err{color:var(--accent)}

.cw-saved{margin-top:16px}
.cw-saved-label{font-size:.74rem;letter-spacing:.06em;color:var(--text-dim);
    text-transform:uppercase;margin-bottom:8px}
.cw-saved-list{list-style:none;margin:0;padding:0;display:flex;
    flex-direction:column;gap:6px}
.cw-saved-item{display:grid;grid-template-columns:minmax(0,1fr) auto auto;
    gap:8px;align-items:center;padding:7px 10px;
    background:var(--surface);border:1px solid var(--border);border-radius:8px}
.cw-saved-name{font-size:.86rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.cw-saved-meta{font-size:.72rem;color:var(--text-dim);white-space:nowrap}
.cw-saved-item button{background:none;border:0;cursor:pointer;font-size:.76rem;
    color:var(--text-dim);padding:3px 6px;border-radius:6px;transition:color .15s,background .15s}
.cw-saved-item button:hover{color:var(--accent);background:var(--surface2)}

@media (prefers-reduced-motion: reduce){
    .cw-canvas{transition:transform .25s linear}
    .cw-result-text{transition:none}
}
</style>

<script>
(function(){
    var LS_KEY = 'cw_items_v1';
    var MAX_ITEMS = 20;

    var canvas = document.getElementById('cw-canvas');
    if(!canvas) return;
    var ctx     = canvas.getContext('2d');
    var listEl  = document.getElementById('cw-list');
    var emptyEl = document.getElementById('cw-empty');
    var totalEl = document.getElementById('cw-total');
    var resEl   = document.getElementById('cw-result');
    var spinBtn = document.getElementById('cw-spin');
    var form    = document.getElementById('cw-add-form');
    var inpText = document.getElementById('cw-input-text');
    var inpPct  = document.getElementById('cw-input-pct');
    var errEl   = document.getElementById('cw-err');

    var T = {
        dup:    @json(__('minigame.cw_err_dup')),
        max:    @json(__('minigame.cw_err_max')),
        pct:    @json(__('minigame.cw_err_pct')),
        need:   @json(__('minigame.cw_err_need')),
        total:  @json(__('minigame.cw_total')),
        eff:    @json(__('minigame.cw_effective')),
        result: @json(__('minigame.wheel_result_prefix'))
    };

    // 與命運轉盤同一組配色
    var COLORS = [
        ['#e53935','#ff6659'],['#fb8c00','#ffbd45'],['#fdd835','#ffff6b'],
        ['#43a047','#76d275'],['#1e88e5','#6ab7ff'],['#8e24aa','#c158dc'],
        ['#f06292','#ff94c2'],['#26a69a','#64d8cb']
    ];

    var reduce = window.matchMedia
        && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var SPIN_MS = reduce ? 250 : 4400;

    var items = load();
    var spinning = false;
    var angle = 0;
    var size = 300;

    function load(){
        try{
            var raw = localStorage.getItem(LS_KEY);
            if(!raw) return [];
            var a = JSON.parse(raw);
            if(!Array.isArray(a)) return [];
            return a.filter(function(o){
                return o && typeof o.t === 'string' && isFinite(o.p);
            }).slice(0, MAX_ITEMS);
        }catch(e){ return []; }
    }
    function save(){
        try{ localStorage.setItem(LS_KEY, JSON.stringify(items)); }catch(e){}
    }

    function total(){
        return items.reduce(function(s,o){ return s + o.p; }, 0);
    }
    function showErr(msg){
        errEl.textContent = msg; errEl.hidden = false;
    }
    function clearErr(){ errEl.hidden = true; }

    function esc(s){
        var d = document.createElement('div');
        d.appendChild(document.createTextNode(s));
        return d.innerHTML;
    }

    /* ---------- 繪製 ---------- */
    function resize(){
        var rect = canvas.getBoundingClientRect();
        size = Math.max(160, Math.round(rect.width));
        var dpr = Math.min(window.devicePixelRatio || 1, 3);
        canvas.width  = Math.round(size * dpr);
        canvas.height = Math.round(size * dpr);
        ctx.setTransform(dpr,0,0,dpr,0,0);
        draw();
    }

    function draw(){
        var c = size/2, r = c - 3;
        ctx.clearRect(0,0,size,size);

        if(!items.length){
            ctx.beginPath();
            ctx.arc(c,c,r,0,2*Math.PI);
            ctx.fillStyle = 'rgba(255,255,255,.05)';
            ctx.fill();
            ctx.strokeStyle = 'rgba(255,255,255,.16)';
            ctx.lineWidth = 2;
            ctx.stroke();
            return;
        }

        var tot = total() || 1;
        var start = -Math.PI/2;                 // 從 12 點鐘開始
        var fs = Math.max(8, Math.round(size*0.036));

        items.forEach(function(it, i){
            var sweep = it.p / tot * 2 * Math.PI;
            var end = start + sweep;
            var col = COLORS[i % COLORS.length];

            var g = ctx.createRadialGradient(c,c,r*0.12,c,c,r);
            g.addColorStop(0, col[1]);
            g.addColorStop(1, col[0]);

            ctx.beginPath();
            ctx.moveTo(c,c);
            ctx.arc(c,c,r,start,end);
            ctx.closePath();
            ctx.fillStyle = g;
            ctx.fill();
            ctx.strokeStyle = 'rgba(255,255,255,.4)';
            ctx.lineWidth = 1.6;
            ctx.stroke();

            // 扇形太窄就不畫字,避免疊在一起看不清
            if(sweep > 0.19){
                ctx.save();
                ctx.translate(c,c);
                ctx.rotate(start + sweep/2);
                ctx.fillStyle = '#fff';
                ctx.shadowColor = 'rgba(0,0,0,.55)';
                ctx.shadowBlur = 3;
                ctx.textAlign = 'center';
                ctx.font = 'bold ' + fs + 'px sans-serif';

                var per = 6, lines = [], t = it.t;
                for(var p=0; p<t.length && lines.length<2; p+=per) lines.push(t.substr(p,per));
                if(t.length > per*2) lines[1] = lines[1].slice(0,-1) + '…';

                var lh = fs + 1;
                var y0 = -((lines.length-1)*lh)/2 + fs*0.34;
                for(var L=0; L<lines.length; L++) ctx.fillText(lines[L], r*0.6, y0 + L*lh);

                ctx.shadowBlur = 0;
                ctx.restore();
            }
            start = end;
        });

        ctx.beginPath();
        ctx.arc(c,c,r,0,2*Math.PI);
        ctx.strokeStyle = 'rgba(255,255,255,.15)';
        ctx.lineWidth = 2.5;
        ctx.stroke();
    }

    /* ---------- 清單 ----------
       拆成兩個函式:buildList() 重建 DOM,refresh() 只更新數字與轉盤。
       關鍵原因:若每次按鍵都重建 DOM,輸入框會被重新產生,
       使用者就無法連續輸入兩位數(打「1」後整列重建、游標與選取狀態全失)。 */
    function buildList(){
        listEl.innerHTML = '';
        items.forEach(function(it, i){
            var tot = total() || 1;
            var eff = (it.p / tot * 100);
            var li = document.createElement('li');
            li.className = 'cw-item';
            li.innerHTML =
                '<span class="cw-dot" style="background:' + COLORS[i % COLORS.length][0] + '"></span>' +
                '<span class="cw-name" title="' + esc(it.t) + '">' + esc(it.t) + '</span>' +
                '<span><input type="number" class="cw-item-pct" min="1" max="100" step="1" ' +
                    'value="' + it.p + '" data-i="' + i + '" aria-label="' + esc(T.eff) + '">' +
                    '<span class="cw-eff">' + eff.toFixed(1) + '%</span></span>' +
                '<button type="button" class="cw-del" data-i="' + i + '" ' +
                    'aria-label="' + esc(@json(__('minigame.wheel_remove'))) + '">&times;</button>';
            listEl.appendChild(li);
        });

        refresh();
    }

    /* 只更新「會隨佔比變動」的部分,不動輸入框本身 */
    function refresh(){
        var tot = total();

        emptyEl.hidden = items.length > 0;
        spinBtn.disabled = items.length < 2 || spinning;

        totalEl.innerHTML = T.total.replace(':n', '<b>' + tot + '</b>');
        totalEl.classList.toggle('warn', items.length > 0 && tot !== 100);

        var effs = listEl.querySelectorAll('.cw-eff');
        items.forEach(function(it, i){
            if(effs[i]) effs[i].textContent = (it.p / (tot || 1) * 100).toFixed(1) + '%';
        });

        draw();
    }

    /* ---------- 事件 ---------- */
    form.addEventListener('submit', function(e){
        e.preventDefault();
        clearErr();
        var t = inpText.value.trim();
        var p = parseInt(inpPct.value, 10);

        if(!t){ showErr(T.need); return; }
        if(!isFinite(p) || p < 1 || p > 100){ showErr(T.pct); return; }
        if(items.length >= MAX_ITEMS){ showErr(T.max); return; }
        if(items.some(function(o){ return o.t === t; })){ showErr(T.dup); return; }

        items.push({ t: t, p: p });
        save(); buildList();
        inpText.value = '';
        inpText.focus();
    });

    // 清單內的 % 即時修改
    listEl.addEventListener('input', function(e){
        var el = e.target;
        if(!el.classList.contains('cw-item-pct')) return;
        var i = +el.dataset.i;
        var v = parseInt(el.value, 10);
        if(!isFinite(v) || v < 1) return;      // 空白或非法值先不套用,等使用者輸入完
        items[i].p = Math.min(100, v);
        save();
        refresh();          // 只更新數字與轉盤,輸入框保持原狀
    });

    listEl.addEventListener('click', function(e){
        var btn = e.target.closest('.cw-del');
        if(!btn) return;
        items.splice(+btn.dataset.i, 1);
        save(); buildList();
    });

    document.getElementById('cw-even').addEventListener('click', function(){
        if(!items.length) return;
        var base = Math.floor(100 / items.length);
        var rest = 100 - base * items.length;
        items.forEach(function(o,i){ o.p = base + (i < rest ? 1 : 0); });
        save(); buildList();
    });

    document.getElementById('cw-clear').addEventListener('click', function(){
        items = []; save(); buildList();
        resEl.classList.remove('show');
    });

    function spin(){
        if(spinning || items.length < 2) return;
        spinning = true;
        spinBtn.disabled = true;
        resEl.classList.remove('show');

        // 依權重抽出得獎項:在 [0, total) 取一個點,落在哪個累積區間就是哪一項
        var tot = total();
        var pick = Math.random() * tot;
        var acc = 0, win = 0;
        for(var i=0;i<items.length;i++){
            acc += items[i].p;
            if(pick < acc){ win = i; break; }
        }

        // 該扇形的中心角(以 12 點鐘為 0、順時針為正)
        var before = 0;
        for(var j=0;j<win;j++) before += items[j].p;
        var centerDeg = (before + items[win].p/2) / tot * 360;

        // 轉盤本體旋轉,把該中心轉到指針位置
        var target = 360 - centerDeg;
        var cur = ((angle % 360) + 360) % 360;
        angle += (reduce ? 0 : 360*5) + (((target - cur) % 360) + 360) % 360;
        canvas.style.transform = 'rotate(' + angle + 'deg)';

        setTimeout(function(){
            spinning = false;
            spinBtn.disabled = items.length < 2;
            resEl.textContent = T.result + items[win].t;
            resEl.classList.add('show');
        }, SPIN_MS + 100);
    }

    spinBtn.addEventListener('click', spin);

    /* ---------- 進入遊戲流程 ----------
       把項目交給命運轉盤的遊戲流程(玩家設定 → 回合 → 任務面板)。
       權重的處理:遊戲流程的扇形是等分、抽獎是均勻的,所以這裡先按佔比
       把項目展開成多個扇形 —— 佔比高的佔更多格,視覺與機率同時正確,
       完全不必改動原本的抽獎邏輯。 */
    var startBtn = document.getElementById('cw-start');
    var startHint = document.getElementById('cw-start-hint');

    function expandByWeight(list, slots){
        var tot = list.reduce(function(s,o){ return s + o.p; }, 0) || 1;
        // 每項至少 1 格,其餘按佔比分配
        var counts = list.map(function(o){
            return Math.max(1, Math.round(o.p / tot * slots));
        });
        // 修正到剛好 slots:多了從最大的扣、少了給最大的加
        var sum = counts.reduce(function(a,b){ return a+b; }, 0);
        while(sum !== slots){
            var idx = 0;
            for(var i=1;i<counts.length;i++){
                if(sum > slots ? counts[i] > counts[idx] : counts[i] < counts[idx]) idx = i;
            }
            if(sum > slots){
                if(counts[idx] <= 1) break;      // 不能把任何項目扣到 0
                counts[idx]--; sum--;
            } else { counts[idx]++; sum++; }
        }
        var out = [];
        list.forEach(function(o,i){
            for(var k=0;k<counts[i];k++) out.push(o.t);
        });
        return out;
    }

    function updateStartState(){
        if(!startBtn) return;
        startBtn.disabled = items.length < 2;
    }

    if(startBtn){
        startBtn.addEventListener('click', function(){
            if(items.length < 2) return;
            // 12 格與內建轉盤一致
            var segs = expandByWeight(items, Math.max(items.length, Math.min(12, items.length * 2)));
            if(typeof window.startCustomWheelGame === 'function'){
                window.startCustomWheelGame(segs);
            } else if(startHint){
                startHint.textContent = @json(__('minigame.cw_start_unavailable'));
            }
        });
    }

    /* ---------- 儲存 / 載入 / 刪除(登入才有這些節點) ---------- */
    var saveBtn  = document.getElementById('cw-save-btn');
    var saveName = document.getElementById('cw-save-name');
    var saveMsg  = document.getElementById('cw-save-msg');
    var savedBox = document.getElementById('cw-saved');
    var savedUl  = document.getElementById('cw-saved-list');

    var URLS = {
        index: @json(route('custom-wheel.index')),
        store: @json(route('custom-wheel.store')),
        destroy: @json(route('custom-wheel.destroy', ['customWheel' => '__ID__']))
    };
    var CSRF = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';

    function msg(text, kind){
        if(!saveMsg) return;
        saveMsg.textContent = text;
        saveMsg.className = 'cw-save-msg' + (kind ? ' ' + kind : '');
        saveMsg.hidden = false;
    }

    function renderSaved(list){
        if(!savedUl) return;
        savedUl.innerHTML = '';
        savedBox.hidden = !list.length;
        list.forEach(function(w){
            var li = document.createElement('li');
            li.className = 'cw-saved-item';
            li.innerHTML =
                '<span class="cw-saved-name" title="' + esc(w.name) + '">' + esc(w.name) + '</span>' +
                '<span class="cw-saved-meta">' + w.items.length + '</span>' +
                '<span><button type="button" data-load="' + w.id + '">' +
                    esc(@json(__('minigame.cw_load'))) + '</button>' +
                '<button type="button" data-del="' + w.id + '">' +
                    esc(@json(__('minigame.wheel_remove'))) + '</button></span>';
            li.dataset.items = JSON.stringify(w.items);
            savedUl.appendChild(li);
        });
    }

    function fetchSaved(){
        if(!savedUl) return;
        fetch(URLS.index, {headers: {'Accept':'application/json'}, credentials:'same-origin'})
            .then(function(r){ return r.ok ? r.json() : Promise.reject(r); })
            .then(function(d){ renderSaved(d.wheels || []); })
            .catch(function(){ /* 讀取失敗就不顯示清單,不干擾主要功能 */ });
    }

    if(saveBtn){
        saveBtn.addEventListener('click', function(){
            if(items.length < 2){ msg(@json(__('minigame.cw_save_need')), 'err'); return; }
            var name = (saveName.value || '').trim();
            if(!name){ msg(@json(__('minigame.cw_save_need_name')), 'err'); return; }

            saveBtn.disabled = true;
            fetch(URLS.store, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': CSRF
                },
                body: JSON.stringify({name: name, items: items})
            })
            .then(function(r){ return r.json().then(function(d){ return {ok:r.ok, d:d}; }); })
            .then(function(res){
                if(res.ok){
                    msg(res.d.message || '', 'ok');
                    saveName.value = '';
                    fetchSaved();
                } else {
                    var e = res.d.errors ? Object.values(res.d.errors)[0][0] : res.d.message;
                    msg(e || @json(__('minigame.cw_save_failed')), 'err');
                }
            })
            .catch(function(){ msg(@json(__('minigame.cw_save_failed')), 'err'); })
            .finally(function(){ saveBtn.disabled = false; });
        });
    }

    if(savedUl){
        savedUl.addEventListener('click', function(e){
            var b = e.target.closest('button');
            if(!b) return;
            var li = b.closest('.cw-saved-item');

            if(b.dataset.load){
                try{
                    items = JSON.parse(li.dataset.items).slice(0, MAX_ITEMS);
                    save(); buildList();
                    msg(@json(__('minigame.cw_loaded')), 'ok');
                }catch(err){}
                return;
            }
            if(b.dataset.del){
                b.disabled = true;
                fetch(URLS.destroy.replace('__ID__', b.dataset.del), {
                    method: 'DELETE',
                    credentials: 'same-origin',
                    headers: {'X-CSRF-TOKEN': CSRF, 'Accept':'application/json'}
                }).then(function(){ fetchSaved(); })
                  .catch(function(){ b.disabled = false; });
            }
        });
        fetchSaved();
    }

    var rt = null;
    window.addEventListener('resize', function(){
        if(spinning) return;
        clearTimeout(rt);
        rt = setTimeout(resize, 150);
    });

    buildList();
    resize();
})();
</script>
