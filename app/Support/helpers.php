<?php

use Illuminate\Support\Facades\Route;

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

if (! function_exists('article_text')) {
    /**
     * 文章正文的行內語法。只有玩法指南在用。
     *
     * 為什麼要自己做一套而不是拉一個 Markdown 套件進來:這裡需要的只有四種標記,
     * 而 Markdown 套件會一起帶進來一堆我們不想開放的東西(原始 HTML、圖片、
     * 表格)。文案是自己人寫的,但「文案裡的字串可以產出任意 HTML」這件事
     * 不該存在 —— 這個函式先 e() 全部轉義,再只還原下面四種標籤。
     *
     *   **粗體**              → <strong>(關鍵字,掃頁時看得到)
     *   `可以照著講的一句話`   → <q class="ax-say">(句子晶片,帶顏色)
     *   ~~不要這樣~~          → <s class="ax-no">(反例,灰掉劃掉)
     *   [[route.name|錨文字]] → 站內連結;路由不存在時只印文字,不會壞掉
     *
     * 句子晶片是這幾篇文章最需要的東西:內容裡有大量「可以直接講出口的句子」,
     * 混在段落裡讀者會滑過去,變成一個有顏色的塊之後才看得出「這句可以照抄」。
     */
    function article_text(?string $text): string
    {
        $html = e((string) $text);

        // 站內連結。先做,免得錨文字裡的粗體被下一條吃掉之後對不上括號
        $html = (string) preg_replace_callback(
            '/\[\[([a-z0-9._-]+)\|(.+?)\]\]/u',
            function (array $m): string {
                /* 路由不存在、或它需要額外參數(那種只能從控制器帶),都只印文字。
                   文章少一個連結沒關係,整頁 500 才是問題 —— 而這種錯只有那一篇
                   被打開時才會炸,測試很容易漏掉。 */
                try {
                    if (! Route::has($m[1])) {
                        return $m[2];
                    }

                    return '<a class="ax-link" href="'.route($m[1]).'">'.$m[2].'</a>';
                } catch (Throwable) {
                    return $m[2];
                }
            },
            $html
        );

        $html = (string) preg_replace('/\*\*(.+?)\*\*/u', '<strong>$1</strong>', $html);
        $html = (string) preg_replace('/`([^`]+)`/u', '<q class="ax-say">$1</q>', $html);
        $html = (string) preg_replace('/~~(.+?)~~/u', '<s class="ax-no">$1</s>', $html);

        return $html;
    }
}

if (! function_exists('article_plain')) {
    /**
     * 把文章的行內語法**拆掉**,回傳純文字。
     *
     * 用在結構化資料、meta description、og:description 這些「機器讀的」欄位 ——
     * 那裡出現 `**` 或反引號不會壞掉,但 Google 就是照著印,搜尋結果會出現一排
     * 星號。FAQPage 的答案更嚴格:它必須和頁面上看得到的文字一致。
     *
     * 句子晶片還原成「」而不是直接拿掉引號 —— 純文字裡那還是一句引述。
     */
    function article_plain(?string $text): string
    {
        $t = (string) $text;
        $t = (string) preg_replace('/\[\[[a-z0-9._-]+\|(.+?)\]\]/u', '$1', $t);
        $t = (string) preg_replace('/\*\*(.+?)\*\*/u', '$1', $t);
        $t = (string) preg_replace('/`([^`]+)`/u', '「$1」', $t);

        return (string) preg_replace('/~~(.+?)~~/u', '$1', $t);
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
