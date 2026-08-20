<?php

if (! function_exists('asset_v')) {
    /**
     * asset() with a cache-busting version query derived from the file's
     * mtime. The public/css + public/js files are hand-maintained (no build
     * pipeline / no Vite manifest), and nginx serves them with long-lived
     * cache headers — without a version param every deploy leaves visitors
     * on stale CSS/JS until a hard refresh.
     */
    function asset_v(string $path): string
    {
        $full = public_path($path);
        $version = is_file($full) ? filemtime($full) : null;

        return asset($path).($version ? '?v='.$version : '');
    }
}

if (! function_exists('inline_emphasis')) {
    /**
     * 把文案裡的 **粗體** 轉成 <strong>,其餘一律當純文字。
     *
     * 給 lang/{locale}/guides.php 的條列項目用:每一點都以一個短標籤開頭,沒有粗體
     * 就變成一整片灰色的字,長文的可讀性會掉很多。
     *
     * **順序是安全性的關鍵**:先 e() escape 整段,之後才把「已被轉義的星號對」
     * 換成標籤。所以文案裡就算出現 <script> 也只會顯示成文字 —— 這個函式永遠只
     * 產出 <strong>,不可能產出其他標籤。反過來寫(先換標籤再 escape)會把標籤
     * 一起轉義,先 escape 再用寬鬆的規則替換則會開一個 XSS 的洞。
     *
     * 刻意不引入 Markdown 套件:這裡要的就只有粗體一種,一個正則比一個相依套件
     * 好維護,也不會哪天有人在文案裡寫出意外生效的語法。
     */
    function inline_emphasis(?string $text): string
    {
        return (string) preg_replace(
            '/\*\*(.+?)\*\*/u',
            '<strong>$1</strong>',
            e((string) $text)
        );
    }
}
