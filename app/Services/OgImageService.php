<?php

namespace App\Services;

use GdImage;

/**
 * 分享卡片。兩份測驗的結果頁各自產一張 1200×630 的 PNG 當 og:image。
 *
 * 為什麼不是把畫面上那張 SVG 轉出來:結果頁的象限圖／雷達畫的是**這個人的分數**,
 * 而分數只在 session 裡 —— 分享出去的網址是 `/tw/trait-test/{slug}`,別人打開只會
 * 看到那一型的介紹頁,沒有分數。所以卡片畫的是「型別／級距」這個網址真正代表的
 * 東西:名稱、一句話、以及這一型偏向哪幾條軸。
 *
 * 尺度:卡片上只用 `line`(那一句概述),不用 `bedroom`／`long`。這一層是各家社群
 * 平台的抓取器會拿去顯示的,寫得太具體會被判成成人素材而擋掉連結預覽;`line`
 * 是暗示性的,不是描述性的。
 *
 * 繪圖用 GD(這台機器有,而且不必外部服務)。沒有 CJK 字型的環境會畫成一排豆腐字,
 * 所以 available() 先確認,不成立就由呼叫端退回站台預設圖。
 */
class OgImageService
{
    /** 社群平台的標準尺寸。Facebook / X / LINE 都吃 1200×630。 */
    private const W = 1200;

    private const H = 630;

    /** 卡片內縮的面板。四邊留深色底,看起來像一張卡而不是一張滿版圖。 */
    private const PAD = 48;

    private const BG = '#0d0f16';

    private const PANEL = '#151823';

    private const BORDER = '#2a2f42';

    private const TEXT = '#e9ebf2';

    private const DIM = '#9aa1b5';

    private const TRACK = '#232838';

    /**
     * 四個色系。刻意寫死十六進位、不讀 CSS 變數 —— app.css 裡 `--rose` 在深色
     * 主題下會被換成靛色,卡片沒有主題可言,跟著變只會讓同一型每次產出不同顏色。
     */
    private const PALETTE = [
        'rose' => '#f43f5e',
        'indigo' => '#818cf8',
        'gold' => '#d9a441',
        'green' => '#4ade80',
    ];

    public function __construct(private readonly TraitTestService $traits) {}

    /** @var array<string, string> 解析過的字型路徑。每一次畫字都要用,不重複 stat。 */
    private array $fontCache = [];

    /** GD 與中文字型都到位了嗎。缺任何一個就不要畫(會畫出一排方框)。 */
    public function available(): bool
    {
        return function_exists('imagettftext')
            && $this->font(false) !== ''
            && $this->font(true) !== '';
    }

    /**
     * 快取指紋。語系檔一改,卡片文字就該跟著換 —— 這台機器上改 lang 檔是存檔即
     * 生效(不經過 build),所以拿 mtime 當鍵最省事,不必記得手動清。
     */
    public function fingerprint(string $kind, string $key, string $locale): string
    {
        $file = lang_path("{$locale}/".($kind === 'trait' ? 'traits' : $kind).'.php');

        return substr(hash('sha256', implode('|', [
            config('og.version'),
            $kind,
            $key,
            $locale,
            is_file($file) ? filemtime($file) : 0,
        ])), 0, 16);
    }

    /** 枕邊屬性測驗:20 型的卡片。回傳 PNG 二進位。 */
    public function traitCard(string $key): string
    {
        $item = $this->traits->item($key);
        $accent = self::PALETTE[$item['colour'] ?? 'gold'] ?? self::PALETTE['gold'];

        $im = $this->canvas($accent);

        $this->eyebrow($im, $accent, (string) __('traits.title'), (string) __('traits.og.count', [
            'n' => count((array) config('traits.traits', [])),
        ]));
        $bodyTop = $this->heading($im, (string) $item['name'], $accent);
        $this->body($im, (string) ($item['line'] ?? ''), $bodyTop);

        /* 「這一型偏哪幾條軸」是免費就看得到的計分依據,拿來當卡片下緣的標籤 ——
           20 張卡片才不會只差一個名字。 */
        $leans = [];
        foreach ($this->traits->basis($key)['axes'] ?? [] as $axis) {
            if (! empty($axis['lean'])) {
                $leans[] = (string) $axis['lean'];
            }
        }
        $this->chips($im, array_slice($leans, 0, 3), $accent);

        $this->footer($im, (string) __('traits.title'));

        return $this->png($im);
    }

    /* ────────────────────────── 版面 ────────────────────────── */

    /** 底色 + 面板 + 面板上緣那條主色。 */
    private function canvas(string $accent): GdImage
    {
        $im = imagecreatetruecolor(self::W, self::H);
        imagealphablending($im, true);
        imagefill($im, 0, 0, $this->rgb($im, self::BG));

        $x1 = self::PAD;
        $y1 = self::PAD;
        $x2 = self::W - self::PAD;
        $y2 = self::H - self::PAD;

        $this->roundedRect($im, $x1, $y1, $x2, $y2, 28, $this->rgb($im, self::PANEL));
        $this->roundedRectOutline($im, $x1, $y1, $x2, $y2, 28, $this->rgb($im, self::BORDER));

        // 面板上緣的主色細條。圓角處收在直線段裡,不用畫弧。
        imagefilledrectangle($im, $x1 + 28, $y1, $x2 - 28, $y1 + 5, $this->rgb($im, $accent));

        return $im;
    }

    /** 最上面那一行:主色圓點 + 測驗名稱,右邊一個小標(型數／分數區間)。 */
    private function eyebrow(GdImage $im, string $accent, string $label, string $right): void
    {
        $y = 132;
        $x = 96;

        imagefilledellipse($im, $x + 7, $y - 9, 15, 15, $this->rgb($im, $accent));
        $this->text($im, $label, $x + 30, $y, 27, self::DIM);

        if ($right !== '') {
            $w = $this->textWidth($right, 27, false);
            $this->text($im, $right, self::W - 96 - $w, $y, 27, self::DIM);
        }
    }

    /** 大標(型別／級距名稱)。回傳下一段可以開始畫的 y。 */
    private function heading(GdImage $im, string $name, string $accent): int
    {
        // 名字最長的是「情侶／炮友」這種帶符號的,72 級放得下;真的太長就縮一級。
        $size = $this->textWidth($name, 78, true) > 880 ? 62 : 78;
        $this->text($im, $name, 96, 250, $size, $accent, true);

        // 大標底下一段短的主色線,讓標題與內文之間有階層。
        imagefilledrectangle($im, 96, 282, 96 + 84, 285, $this->rgb($im, $accent));

        return 348;
    }

    /** 一句話概述。最多三行,超出的話收尾用刪節號。 */
    private function body(GdImage $im, string $line, int $top): void
    {
        foreach ($this->wrap($line, 34, self::W - 96 - 96, 3) as $i => $text) {
            $this->text($im, $text, 96, $top + ($i * 52), 34, self::TEXT);
        }
    }

    /** 屬性卡下緣的標籤:這一型偏向的軸。 */
    private function chips(GdImage $im, array $labels, string $accent): void
    {
        $x = 96;
        $y = 486;

        foreach ($labels as $label) {
            $w = $this->textWidth($label, 25, false) + 44;
            $this->roundedRect($im, $x, $y, $x + $w, $y + 52, 26, $this->rgb($im, self::TRACK));
            $this->text($im, $label, $x + 22, $y + 35, 25, $accent);
            $x += $w + 14;
        }
    }

    /** 站名與網址。 */
    private function footer(GdImage $im, string $section): void
    {
        $left = __('ui.site_name').' · '.$section;
        $this->text($im, (string) $left, 96, 578, 23, self::DIM);

        $host = (string) (parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'pillownight.com');
        $this->text($im, $host, self::W - 96 - $this->textWidth($host, 23, false), 578, 23, self::DIM);
    }

    /* ────────────────────────── GD 工具 ────────────────────────── */

    private function png(GdImage $im): string
    {
        ob_start();
        imagepng($im, null, 9);
        $data = (string) ob_get_clean();
        imagedestroy($im);

        return $data;
    }

    private function rgb(GdImage $im, string $hex): int
    {
        [$r, $g, $b] = sscanf(ltrim($hex, '#'), '%2x%2x%2x');

        return (int) imagecolorallocate($im, (int) $r, (int) $g, (int) $b);
    }

    /**
     * 這台機器上實際存在的字型檔。候選清單見 config/og.php;都不中的時候再 glob
     * 一次 —— Noto CJK 的檔名在不同發行版之間換過(NotoSansCJK-Regular.ttc /
     * NotoSansCJK-VF.otf.ttc),寫死檔名會在升級套件之後突然失效。
     */
    private function font(bool $bold): string
    {
        $slot = $bold ? 'bold' : 'regular';

        if (isset($this->fontCache[$slot])) {
            return $this->fontCache[$slot];
        }

        foreach ((array) config("og.fonts.{$slot}", []) as $path) {
            if (is_string($path) && $path !== '' && is_readable($path)) {
                return $this->fontCache[$slot] = $path;
            }
        }

        $needle = $bold ? 'Bold' : 'Regular';
        foreach (['/usr/share/fonts/opentype/noto', '/usr/share/fonts/noto', '/usr/share/fonts/truetype/noto'] as $dir) {
            foreach ((array) glob($dir.'/*CJK*') as $found) {
                if (is_readable($found) && str_contains($found, $needle)) {
                    return $this->fontCache[$slot] = $found;
                }
            }
        }

        return $this->fontCache[$slot] = '';
    }

    /** y 是**基線**,不是文字頂端 —— GD 的 imagettftext 就是這個約定。 */
    private function text(GdImage $im, string $text, int $x, int $y, int $size, string $hex, bool $bold = false): void
    {
        imagettftext($im, $size, 0, $x, $y, $this->rgb($im, $hex), $this->font($bold), $text);
    }

    private function textWidth(string $text, int $size, bool $bold): int
    {
        $box = imagettfbbox($size, 0, $this->font($bold), $text);

        return $box === false ? 0 : (int) abs($box[2] - $box[0]);
    }

    /**
     * 中文斷行。**不能用空白切詞** —— 中文整句就是一個詞,那樣切等於不斷行。
     * 這裡逐字累加、量寬度,並且不把標點留在行首。
     *
     * @return array<int, string>
     */
    private function wrap(string $text, int $size, int $maxWidth, int $maxLines): array
    {
        $text = trim(preg_replace('/\s+/u', ' ', $text) ?? '');
        if ($text === '') {
            return [];
        }

        $chars = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $lines = [];
        $current = '';

        foreach ($chars as $char) {
            $candidate = $current.$char;

            if ($this->textWidth($candidate, $size, false) > $maxWidth && $current !== '') {
                $lines[] = $current;
                $current = $char;

                if (count($lines) === $maxLines) {
                    break;
                }

                continue;
            }

            $current = $candidate;
        }

        if (count($lines) < $maxLines && $current !== '') {
            $lines[] = $current;
        }

        // 塞不完就在最後一行收尾。截掉一個字再加省略號,免得又超寬。
        $rendered = mb_strlen(implode('', $lines));
        if ($rendered < mb_strlen($text) && $lines !== []) {
            $last = (string) array_pop($lines);
            $lines[] = mb_substr($last, 0, max(0, mb_strlen($last) - 1)).'…';
        }

        return $lines;
    }

    private function roundedRect(GdImage $im, int $x1, int $y1, int $x2, int $y2, int $r, int $colour): void
    {
        $r = (int) min($r, floor(($x2 - $x1) / 2), floor(($y2 - $y1) / 2));

        imagefilledrectangle($im, $x1 + $r, $y1, $x2 - $r, $y2, $colour);
        imagefilledrectangle($im, $x1, $y1 + $r, $x2, $y2 - $r, $colour);

        $d = $r * 2;
        imagefilledellipse($im, $x1 + $r, $y1 + $r, $d, $d, $colour);
        imagefilledellipse($im, $x2 - $r, $y1 + $r, $d, $d, $colour);
        imagefilledellipse($im, $x1 + $r, $y2 - $r, $d, $d, $colour);
        imagefilledellipse($im, $x2 - $r, $y2 - $r, $d, $d, $colour);
    }

    private function roundedRectOutline(GdImage $im, int $x1, int $y1, int $x2, int $y2, int $r, int $colour): void
    {
        imageline($im, $x1 + $r, $y1, $x2 - $r, $y1, $colour);
        imageline($im, $x1 + $r, $y2, $x2 - $r, $y2, $colour);
        imageline($im, $x1, $y1 + $r, $x1, $y2 - $r, $colour);
        imageline($im, $x2, $y1 + $r, $x2, $y2 - $r, $colour);

        $d = $r * 2;
        imagearc($im, $x1 + $r, $y1 + $r, $d, $d, 180, 270, $colour);
        imagearc($im, $x2 - $r, $y1 + $r, $d, $d, 270, 360, $colour);
        imagearc($im, $x1 + $r, $y2 - $r, $d, $d, 90, 180, $colour);
        imagearc($im, $x2 - $r, $y2 - $r, $d, $d, 0, 90, $colour);
    }
}
