<?php

namespace App\Services;

use App\Support\LocaleHelper;

/**
 * 枕邊屬性測驗的計分。
 *
 * 計分**只在伺服器做**。權重表等於這個測驗的答案卷 —— 送到瀏覽器的話,別人抄走
 * 的就不只是三十句題目,是整個測驗。前端只拿得到題目文字與五個選項。
 *
 * 結構在 config/traits.php,文案在 lang/{locale}/traits.php。
 */
class TraitTestService
{
    /** 量表是五點:-2…+2。 */
    public const MIN = -2;

    public const MAX = 2;

    /** 光譜換算後的刻度,-8…+8。四條線的尺度要一致才畫得成同一張圖。 */
    public const AXIS_SCALE = 8;

    /**
     * 顯示用的題目。只有文字與段落標題 —— 權重留在伺服器。
     *
     * @return array<int, array{n:int, text:string, section:?string}>
     */
    public function questions(): array
    {
        $structure = config('traits.questions');
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
     * 屬性:Σ(答案 × 權重) ÷ Σ(2 × |權重|),負的算 0 —— 「完全不像」就該是 0%,
     * 不是 50%。這跟光譜不一樣:光譜是兩極之間的位置,屬性是「你有多像它」。
     *
     * @param  array<int, int>  $answers  題號 => -2…+2
     * @return array{traits: array<int, array{key:string, pct:int}>, axes: array<string,int>, top: string}
     */
    public function score(array $answers): array
    {
        $structure = config('traits.questions');
        $sum = [];
        $max = [];
        $axis = [];

        foreach (array_keys(config('traits.traits')) as $key) {
            $sum[$key] = 0;
            $max[$key] = 0;
        }
        foreach (config('traits.axes') as $id) {
            $axis[$id] = ['v' => 0, 'n' => 0];
        }

        foreach ($structure as $i => $q) {
            $a = (int) ($answers[$i] ?? 0);
            $a = max(self::MIN, min(self::MAX, $a));

            foreach ($q['weights'] ?? [] as $key => $w) {
                if (! isset($sum[$key])) {
                    continue;   // config 打錯字不該讓整個測驗炸掉
                }
                $sum[$key] += $a * $w;
                $max[$key] += self::MAX * abs($w);
            }

            [$axisId, $dir] = $q['axis'] ?? [null, 0];
            if ($axisId && $dir !== 0 && isset($axis[$axisId])) {
                $axis[$axisId]['v'] += $a * $dir;
                $axis[$axisId]['n'] += self::MAX;
            }
        }

        $traits = [];
        foreach ($sum as $key => $v) {
            $traits[] = [
                'key' => $key,
                'pct' => $max[$key] > 0 ? (int) round(max(0, $v) / $max[$key] * 100) : 0,
            ];
        }

        // 同分時照 config 的順序,結果才不會每次重整就換一個主屬性
        usort($traits, fn ($x, $y) => $y['pct'] <=> $x['pct']);

        $axes = [];
        foreach ($axis as $id => $a) {
            $axes[$id] = $a['n'] > 0 ? (int) round($a['v'] / $a['n'] * self::AXIS_SCALE) : 0;
        }

        return ['traits' => $traits, 'axes' => $axes, 'top' => $traits[0]['key']];
    }

    /**
     * 依使用者自己的分數,挑出每一條光譜該講哪一段。
     *
     * 「同一型的每個人拿到同一份範本」是這類測驗最常被批評的地方,所以這一段
     * 是照實際分數算的,不是照主屬性查表。
     *
     * @param  array<string,int>  $axes  -8…+8
     */
    public function axisReading(array $axes): array
    {
        $names = $this->axes();
        $text = $this->lang('axis_reading');
        $out = [];

        foreach ($axes as $id => $v) {
            // 三分之一為界:偏一邊要夠明顯才算偏,不然每個人都是「偏左」
            $band = $v >= self::AXIS_SCALE / 3 ? 'left'
                : ($v <= -self::AXIS_SCALE / 3 ? 'right' : 'mid');

            $out[$id] = [
                'label' => ($names[$id]['left'] ?? '').' ⇄ '.($names[$id]['right'] ?? ''),
                'lean' => $band === 'mid' ? null : ($band === 'left' ? $names[$id]['left'] : $names[$id]['right']),
                'strength' => (int) round(abs($v) / self::AXIS_SCALE * 100),
                'text' => $text[$id][$band] ?? '',
            ];
        }

        return $out;
    }

    /** 網址片段 → 屬性代碼。找不到回 null。 */
    public function keyFromSlug(string $slug): ?string
    {
        foreach ($this->lang('items') as $key => $item) {
            if (($item['slug'] ?? null) === $slug) {
                return $key;
            }
        }

        return null;
    }

    public function slug(string $key): ?string
    {
        return $this->lang('items')[$key]['slug'] ?? null;
    }

    /** 一個屬性的全部文案。 */
    public function item(string $key): array
    {
        $item = $this->lang('items')[$key] ?? [];
        $item['colour'] = config("traits.traits.{$key}.colour", 'gold');

        return $item;
    }

    /** 光譜的兩極名稱與說明。 */
    public function axes(): array
    {
        $names = $this->lang('axes');
        $out = [];
        foreach (config('traits.axes') as $id) {
            $out[$id] = $names[$id] ?? ['left' => $id, 'right' => '', 'note' => ''];
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
        return in_array($locale ?? app()->getLocale(), (array) config('traits.translated', []), true);
    }

    /**
     * 讀文案。沒翻譯的語系一律退回預設語系,而不是讓畫面出現一串 key。
     */
    private function lang(string $key): array
    {
        $locale = $this->isTranslated() ? app()->getLocale() : LocaleHelper::defaultLocale();

        return (array) trans("traits.{$key}", [], $locale);
    }
}
