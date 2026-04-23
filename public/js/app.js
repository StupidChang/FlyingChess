// Global utilities
function copyCode(code) {
    navigator.clipboard.writeText(code).then(() => {
        const btn = document.querySelector('.copy-btn');
        if (btn) {
            const orig = btn.textContent;
            btn.textContent = '✓ 已複製！';
            setTimeout(() => btn.textContent = orig, 1500);
        }
    });
}
