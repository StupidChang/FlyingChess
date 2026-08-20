<?php

namespace App\Services;

use App\Support\LocaleHelper;

/**
 * 性壓抑指數測驗的計分。
 *
 * 和 TraitTestService 同一個原則:計分**只在伺服器做**。這份測驗的關鍵資訊是
 * 「哪幾題是反向題」—— 那等於答案卷,一旦送到瀏覽器,任何人都能把指數刷成他
 * 想要的數字。前端只拿得到題目文字與五個選項。
 *
 * 結構在 config/repression.php,文案在 lang/{locale}/repression.php。
 */
class RepressionTestService
{
    /** 量表是五點:0…4。這裡刻意不用 -2…+2 —— 壓抑是「有多少」不是「偏哪邊」。 */
    public const MIN = 0;

    public const MAX = 4;

    /**
     * 顯示用的題目。只有文字與段落標題 —— 正反向留在伺服器。
     *
     * @return array<int, array{n:int, text:string, section:?string}>
     */
    public function questions(): array
    {
        $structure = config('repression.questions');
        $text = $this->lang('questions');
        $sections = $this->lang('sections');

        $out = [];
        foreach ($structure as $i => $q) {
            $out[] = [
                'n' => $i,
                'text' => $text[$i] ?? '',
                'section' => isset($q['section']) ? ($sections[$q['section']] ?? null) : null,
            ];
        }

        return $out;
    }

    /**
     * 算出一份結果。
     *
     * 每一題先換算成「壓抑分」:正向題直接用答案,反向題用 MAX - 答案。所以
     * 兩種題目都是「分數越高 = 越壓抑」,可以直接相加。
     *
     * 面向分數 = 該面向的壓抑分總和 ÷ 滿分 × 100。
     * 指數 = 五個面向的加權平均(權重在 config,目前一致)——
     * 用面向平均而不是總題數平均,是為了讓「某個面向題目多寡」不影響指數,
     * 將來加題才不會默默改變所有人的分數。
     *
     * @param  array<int, int>  $answers  題號 => 0…4
     * @return array{index:int, band:string, dimensions:array<int, array{key:string, pct:int}>}
     */
    public function score(array $answers): array
    {
        $sum = [];
        $max = [];
        foreach (array_keys(config('repression.dimensions')) as $key) {
            $sum[$key] = 0;
            $max[$key] = 0;
        }

        foreach (config('repression.questions') as $i => $q) {
            $a = (int) ($answers[$i] ?? 0);
            $a = max(self::MIN, min(self::MAX, $a));

            $dim = $q['dim'] ?? null;
            if ($dim === null || ! isset($sum[$dim])) {
                continue;   // config 打錯字不該讓整個測驗炸掉
            }

            // 反向題:同意代表壓抑低,所以要翻過來才能和正向題相加
            $sum[$dim] += ($q['dir'] ?? 1) < 0 ? self::MAX - $a : $a;
            $max[$dim] += self::MAX;
        }

        $dimensions = [];
        $weighted = 0;
        $weightTotal = 0;
        foreach ($sum as $key => $v) {
            $pct = $max[$key] > 0 ? $v / $max[$key] * 100 : 0;
            $dimensions[] = ['key' => $key, 'pct' => (int) round($pct)];

            $w = (float) config("repression.dimensions.{$key}.weight", 1);
            $weighted += $pct * $w;
            $weightTotal += $w;
        }

        $index = $weightTotal > 0 ? (int) round($weighted / $weightTotal) : 0;

        // 同分時照 config 的順序,「最明顯的一項」才不會每次重整就換一個
        usort($dimensions, fn ($x, $y) => $y['pct'] <=> $x['pct']);

        return [
            'index' => $index,
            'band' => $this->bandFor($index),
            'dimensions' => $dimensions,
        ];
    }

    /**
     * 指數 → 級距代碼。
     *
     * 由高往低找第一個「指數 >= 下界」的級距,所以 config 的 bands 順序(低到高)
     * 就是唯一的真相,不用在兩個地方各寫一次上下界。
     */
    public function bandFor(int $index): string
    {
        $bands = (array) config('repression.bands');
        $hit = array_key_first($bands);

        foreach ($bands as $key => $band) {
            if ($index >= ($band['min'] ?? 0)) {
                $hit = $key;
            }
        }

        return $hit;
    }

    /**
     * 依使用者自己的分數,挑出每一個面向該講哪一段。
     *
     * 「同一個級距的每個人拿到同一份範本」是這類測驗最常被批評的地方,所以這一段
     * 是照實際分數算的,不是照級距查表。
     *
     * @param  array<int, array{key:string, pct:int}>  $dimensions
     */
    public function dimensionReading(array $dimensions): array
    {
        $meta = $this->lang('dimensions');
        $text = $this->lang('dimension_reading');
        $out = [];

        foreach ($dimensions as $d) {
            // 三分法。33/67 為界:要夠明顯才算高或低,不然每個人都是「偏高」
            $band = $d['pct'] >= 67 ? 'high' : ($d['pct'] <= 33 ? 'low' : 'mid');

            $out[] = [
                'key' => $d['key'],
                'name' => $meta[$d['key']]['name'] ?? $d['key'],
                'note' => $meta[$d['key']]['note'] ?? '',
                'pct' => $d['pct'],
                'text' => $text[$d['key']][$band] ?? '',
            ];
        }

        return $out;
    }

    /** 網址片段 → 級距代碼。找不到回 null。 */
    public function keyFromSlug(string $slug): ?string
    {
        foreach ($this->lang('bands') as $key => $band) {
            if (($band['slug'] ?? null) === $slug) {
                return $key;
            }
        }

        return null;
    }

    public function slug(string $key): ?string
    {
        return $this->lang('bands')[$key]['slug'] ?? null;
    }

    /** 一個級距的全部文案(含結構面的顏色)。 */
    public function band(string $key): array
    {
        $band = $this->lang('bands')[$key] ?? [];
        $band['key'] = $key;
        $band['colour'] = config("repression.bands.{$key}.colour", 'gold');
        $band['min'] = (int) config("repression.bands.{$key}.min", 0);

        return $band;
    }

    /**
     * 全部五個級距,由低到高 —— 結果頁的刻度尺與互連清單都用這個。
     *
     * @return array<int, array>
     */
    public function bands(): array
    {
        return array_map(fn ($key) => $this->band($key), array_keys((array) config('repression.bands')));
    }

    /** 面向的名稱與說明。 */
    public function dimensions(): array
    {
        $meta = $this->lang('dimensions');
        $out = [];
        foreach (array_keys((array) config('repression.dimensions')) as $key) {
            $out[$key] = $meta[$key] ?? ['name' => $key, 'note' => ''];
        }

        return $out;
    }

    /**
     * 這個語系有沒有翻譯過。
     *
     * 沒有的話頁面會退回繁中文案並標 noindex —— 讓搜尋引擎收錄一頁中文內容配
     * 英文網址,對排名是扣分不是加分。
     */
    public function isTranslated(?string $locale = null): bool
    {
        return in_array($locale ?? app()->getLocale(), (array) config('repression.translated', []), true);
    }

    /**
     * 讀文案。沒翻譯的語系一律退回預設語系,而不是讓畫面出現一串 key。
     */
    private function lang(string $key): array
    {
        $locale = $this->isTranslated() ? app()->getLocale() : LocaleHelper::defaultLocale();

        return (array) trans("repression.{$key}", [], $locale);
    }
}
