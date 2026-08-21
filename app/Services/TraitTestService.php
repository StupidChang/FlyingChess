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

    /** 差在這個百分點以內就算不分上下 —— 領先 1% 也印王冠會誤導人。 */
    public const TIED_WITHIN = 5;

    /** 到這個百分比才算「這一種也很像你」。 */
    public const STRONG_AT = 60;

    /** 一條光譜至少要有這麼多題餵給同一個屬性,才拿來當「題目偏哪一側」的依據。 */
    public const AXIS_MIN = 3;

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

        /* 作答本身的統計。全部選中間的人和每題都選兩端的人可以拿到很像的百分比,
           但前者的結果幾乎沒有區辨力 —— 那件事只有這裡看得出來,所以一起帶走。 */
        $meta = ['total' => count($structure), 'decisive' => 0, 'neutral' => 0];
        foreach ($structure as $i => $q) {
            $a = max(self::MIN, min(self::MAX, (int) ($answers[$i] ?? 0)));
            if ($a === 0) {
                $meta['neutral']++;
            } elseif (abs($a) === self::MAX) {
                $meta['decisive']++;
            }
        }

        // 同分時照 config 的順序,結果才不會每次重整就換一個主屬性
        usort($traits, fn ($x, $y) => $y['pct'] <=> $x['pct']);

        $axes = [];
        foreach ($axis as $id => $a) {
            $axes[$id] = $a['n'] > 0 ? (int) round($a['v'] / $a['n'] * self::AXIS_SCALE) : 0;
        }

        return ['traits' => $traits, 'axes' => $axes, 'top' => $traits[0]['key'], 'meta' => $meta];
    }

    /**
     * 這個屬性的量測依據:由幾題算出來、涵蓋哪些段落、有沒有反向題、題目偏哪一側。
     *
     * 全部從 config/traits.php 現算,不是寫死的文案 —— 題目改了頁面上的數字跟著改。
     * 手寫的話遲早會對不上,而對不上的「依據」比沒有依據更糟。
     *
     * @return array{count:int, total:int, weight:int, reverse:int, sections:list<string>, axes:list<array{label:string,lean:string,n:int,total:int}>}
     */
    public function basis(string $key): array
    {
        $structure = config('traits.questions');
        $sectionNames = $this->lang('sections');
        $axisNames = $this->axes();

        $count = 0;
        $weight = 0;
        $reverse = 0;
        $sections = [];
        $tally = [];
        $section = null;

        foreach ($structure as $q) {
            // section 只標在該段第一題,所以要一路帶著走才知道這一題屬於哪一段
            if (isset($q['section'])) {
                $section = $q['section'];
            }

            $w = (int) ($q['weights'][$key] ?? 0);
            if ($w === 0) {
                continue;
            }

            $count++;
            $weight += abs($w);
            if ($w < 0) {
                $reverse++;
            }

            $name = $sectionNames[$section] ?? $section;
            if ($name !== null && ! in_array($name, $sections, true)) {
                $sections[] = $name;
            }

            [$axisId, $dir] = $q['axis'] ?? [null, 0];
            if ($w > 0 && $axisId && $dir !== 0) {
                $side = $dir > 0 ? 'left' : 'right';
                $tally[$axisId][$side] = ($tally[$axisId][$side] ?? 0) + 1;
            }
        }

        /* 只留下真的偏一邊的光譜。兩側題數差不多還說「偏某一側」是誤導 ——
           這一段是要當依據用的,寧可少講一條。 */
        $axes = [];
        foreach ($tally as $id => $t) {
            $left = $t['left'] ?? 0;
            $right = $t['right'] ?? 0;
            $total = $left + $right;
            // 一兩題就宣稱「這個屬性偏某一側」沒有意義,少於三題不列
            if ($total < self::AXIS_MIN || $left === $right || max($left, $right) / $total < 0.6) {
                continue;
            }

            $lean = $left > $right ? 'left' : 'right';
            $axes[] = [
                'label' => ($axisNames[$id]['left'] ?? '').' ⇄ '.($axisNames[$id]['right'] ?? ''),
                'lean' => $axisNames[$id][$lean] ?? '',
                'n' => max($left, $right),
                'total' => $total,
            ];
        }

        return [
            'count' => $count,
            'total' => count($structure),
            'weight' => $weight,
            'reverse' => $reverse,
            'sections' => $sections,
            'axes' => $axes,
        ];
    }

    /**
     * 和這個屬性共用題目的其他屬性。
     *
     * 一題會同時餵給好幾個屬性,所以同一題正權重餵到的兩個屬性天生會一起升高,
     * 一正一負的則互為反面。這不是編出來的關聯,是權重表本身的結構 —— 也因此
     * 不必另外維護一份「相關屬性」清單,那種清單一定會跟題目脫節。
     *
     * @return array{together: list<array<string,mixed>>, against: list<array<string,mixed>>}
     */
    public function related(string $key, int $limit = 6): array
    {
        $items = $this->lang('items');
        $with = [];
        $against = [];

        foreach (config('traits.questions') as $q) {
            $w = (int) ($q['weights'][$key] ?? 0);
            if ($w <= 0) {
                continue;
            }

            foreach ($q['weights'] as $other => $ow) {
                if ($other === $key || ! isset($items[$other])) {
                    continue;
                }
                if ($ow > 0) {
                    $with[$other] = ($with[$other] ?? 0) + 1;
                } elseif ($ow < 0) {
                    $against[$other] = ($against[$other] ?? 0) + 1;
                }
            }
        }

        return [
            // 兩邊都出現過的取淨值:三題同向一題反向,講「同向兩題」才誠實
            'together' => $this->rank($with, $against, $limit),
            'against' => $this->rank($against, $with, 3),
        ];
    }

    /**
     * 這一份結果本身有多站得住腳。
     *
     * 主屬性領先第二名一個百分點就印一頂王冠,是這類測驗最容易誤導人的地方。
     * 差距、有幾種同時偏高、作答夠不夠明確,都要講出來讓人自己判斷。
     */
    public function confidence(array $result): array
    {
        $items = $this->lang('items');
        $traits = $result['traits'] ?? [];
        if (! isset($traits[0])) {
            return [];
        }

        $top = $traits[0];
        $tied = [];
        foreach (array_slice($traits, 1) as $t) {
            if ($top['pct'] - $t['pct'] <= self::TIED_WITHIN) {
                $tied[] = $items[$t['key']]['name'] ?? $t['key'];
            }
        }

        return [
            'top' => $items[$top['key']]['name'] ?? $top['key'],
            'pct' => $top['pct'],
            'gap' => $top['pct'] - ($traits[1]['pct'] ?? 0),
            'tied' => $tied,
            'strong' => count(array_filter($traits, fn ($t) => $t['pct'] >= self::STRONG_AT)),
            'meta' => $result['meta'] ?? null,
        ];
    }

    /**
     * 共現次數排序。同時出現在反向清單裡的先扣掉,剩下的才是淨共用題數。
     *
     * @param  array<string,int>  $counts
     * @param  array<string,int>  $offset
     */
    private function rank(array $counts, array $offset, int $limit): array
    {
        $items = $this->lang('items');
        $order = array_keys((array) config('traits.traits'));
        $out = [];

        foreach ($counts as $other => $n) {
            $net = $n - ($offset[$other] ?? 0);
            if ($net <= 0) {
                continue;
            }

            $out[] = [
                'key' => $other,
                'name' => $items[$other]['name'] ?? $other,
                'slug' => $items[$other]['slug'] ?? '',
                'line' => $items[$other]['line'] ?? '',
                'colour' => config("traits.traits.{$other}.colour", 'gold'),
                'shared' => $net,
            ];
        }

        // 同分時照 config 的順序,不然每次重整這一區的排列都不一樣
        usort($out, fn ($a, $b) => $b['shared'] <=> $a['shared']
            ?: array_search($a['key'], $order, true) <=> array_search($b['key'], $order, true));

        return array_slice($out, 0, $limit);
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
