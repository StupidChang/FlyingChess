<div id="age-gate" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,.88);z-index:9999;align-items:center;justify-content:center">
    <div style="background:#1a1a2e;border:1px solid #9b7928;border-radius:12px;padding:40px 32px;max-width:420px;width:90%;text-align:center;color:#e8e0cc">
        <h2 style="font-size:1.4rem;color:#d4af37;margin:0 0 16px">年齡確認</h2>
        <p style="line-height:1.7;margin:0 0 8px;font-size:.95rem">
            本站包含成人向趣味遊戲內容。
        </p>
        <p style="line-height:1.7;margin:0 0 28px;font-size:.95rem">
            請確認您已年滿 18 歲再進入。
        </p>
        <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap">
            <button
                id="age-gate-enter"
                onclick="agegateConfirm()"
                style="background:#d4af37;color:#1a1a2e;border:none;border-radius:6px;padding:12px 28px;font-size:1rem;font-weight:700;cursor:pointer;flex:1;min-width:140px">
                我已年滿 18 歲，進入
            </button>
            <button
                onclick="agegateLeave()"
                style="background:transparent;color:#888;border:1px solid #555;border-radius:6px;padding:12px 28px;font-size:1rem;cursor:pointer;flex:1;min-width:100px">
                離開
            </button>
        </div>
    </div>
</div>

<script>
(function () {
    try {
        if (localStorage.getItem('age_verified') !== '1') {
            var gate = document.getElementById('age-gate');
            if (gate) {
                gate.style.display = 'flex';
                document.body.style.overflow = 'hidden';
            }
        }
    } catch (e) {}
})();

function agegateConfirm() {
    try { localStorage.setItem('age_verified', '1'); } catch (e) {}
    var gate = document.getElementById('age-gate');
    if (gate) gate.style.display = 'none';
    document.body.style.overflow = '';
}

function agegateLeave() {
    window.location.href = 'https://www.google.com';
}
</script>
