// Global utilities
function copyCode(code) {
    navigator.clipboard.writeText(code).then(() => {
        const btn = document.querySelector('.copy-btn');
        if (btn) {
            const orig = btn.textContent;
            btn.textContent = '✓ ' + (btn.dataset.copied || 'OK');
            setTimeout(() => btn.textContent = orig, 1500);
        }
    });
}

/*
 * 複製一段長文字時,剪貼簿後面附上出處與網址。
 *
 * 不擋右鍵、不鎖選取:那些擋不住想抄的人(檢視原始碼就拿到了),只會讓正常
 * 使用者覺得網站很難用。這裡只讓被貼出去的內容帶著來源走。
 * 短的(房號、分享碼、一兩個字)與輸入框裡的不加,免得複製個代碼還附一串字。
 */
document.addEventListener('copy', function (e) {
    var credit = document.body && document.body.dataset.copyCredit;
    var t = e.target;
    if (!credit || !e.clipboardData || (t && t.closest && t.closest('input, textarea, [contenteditable]'))) return;
    var sel = String(window.getSelection ? window.getSelection() : '');
    if (sel.trim().length < 40) return;
    var url = location.origin + location.pathname;
    e.clipboardData.setData('text/plain', sel + '\n\n— ' + credit + ' ' + url);
    e.preventDefault();
});
