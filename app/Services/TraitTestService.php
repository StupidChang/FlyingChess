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
     * 兩型並排時,一條光譜上的距離要多遠才算「各據一邊」、多近才算「同一邊」。
     *
     * 位置只會落在 0(持平)或 ±0.6…±1.0(偏一邊,見 axisLean() 的門檻),所以
     * 距離也只有三種量級:同側 ≤0.4、一側對持平 0.6…1.0、對側 ≥1.2。門檻取在
     * 空隙中間,不是憑感覺調的。
     */
    public const CMP_ALIGNED = 0.4;

    public const CMP_APART = 1.2;

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

        }

        $tally = $this->axisTally($key);

        /* 只留下真的偏一邊的光譜。兩側題數差不多還說「偏某一側」是誤導 ——
           這一段是要當依據用的,寧可少講一條。 */
        $axes = [];
        foreach ($tally as $id => $t) {
            $lean = $this->axisLean($t);
            if ($lean === null) {
                continue;
            }

            $axes[] = [
                'label' => ($axisNames[$id]['left'] ?? '').' ⇄ '.($axisNames[$id]['right'] ?? ''),
                'lean' => $axisNames[$id][$lean['side']] ?? '',
                'n' => $lean['n'],
                'total' => $lean['total'],
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
    /**
     * 這一型在每一條光譜上各有幾題偏左、幾題偏右。basis() 與 axisProfile() 共用 ——
     * 「偏哪一側」的判斷只能有一套,兩邊各算一次遲早會給出互相矛盾的答案。
     *
     * @return array<string, array{left:int, right:int}>
     */
    private function axisTally(string $key): array
    {
        $tally = [];

        foreach (config('traits.questions') as $q) {
            $w = (int) ($q['weights'][$key] ?? 0);
            [$axisId, $dir] = $q['axis'] ?? [null, 0];

            if ($w > 0 && $axisId && $dir !== 0) {
                $side = $dir > 0 ? 'left' : 'right';
                $tally[$axisId][$side] = ($tally[$axisId][$side] ?? 0) + 1;
            }
        }

        return $tally;
    }

    /**
     * 偏一邊,還是不分上下。題數太少或兩側差不多就回 null ——
     * 一兩題就宣稱「這個屬性偏某一側」沒有意義。
     *
     * @param  array{left?:int, right?:int}  $tally
     * @return array{side:string, n:int, total:int, ratio:float}|null
     */
    private function axisLean(array $tally): ?array
    {
        $left = $tally['left'] ?? 0;
        $right = $tally['right'] ?? 0;
        $total = $left + $right;

        if ($total < self::AXIS_MIN || $left === $right || max($left, $right) / $total < 0.6) {
            return null;
        }

        return [
            'side' => $left > $right ? 'left' : 'right',
            'n' => max($left, $right),
            'total' => $total,
            'ratio' => round(max($left, $right) / $total, 3),
        ];
    }

    /**
     * 這一型在四條光譜上的位置:-1(最左)…0(持平)…+1(最右)。
     *
     * 這是**型別**的位置,不是某個人的分數 —— 個人分數要 score() 之後才有,而且只
     * 存在 session 裡。對照頁比的是兩個型別,所以用這個。
     *
     * @return array<string, array{side:?string, pos:float, ratio:float, label:string}>
     */
    public function axisProfile(string $key): array
    {
        $names = $this->axes();
        $tally = $this->axisTally($key);
        $out = [];

        foreach (array_keys($names) as $id) {
            $lean = $this->axisLean($tally[$id] ?? []);

            $out[$id] = [
                'side' => $lean['side'] ?? null,
                'pos' => $lean === null ? 0.0 : ($lean['side'] === 'left' ? -$lean['ratio'] : $lean['ratio']),
                'ratio' => $lean['ratio'] ?? 0.0,
                'label' => $lean === null ? '' : (string) ($names[$id][$lean['side']] ?? ''),
            ];
        }

        return $out;
    }

    /**
     * 兩型並排比。每一條光譜給雙方的位置與距離,另外挑出最一致與差最大的那一條。
     *
     * 用途是對照頁 —— 免費就看得到的那一半,因為它是從計分依據算出來的「描述」,
     * 跟 basis() 同一個層級;「該怎麼做」那一半(合拍／磨合／給對方的話)仍然
     * 鎖在付費後面,見 trait-test/compare.blade.php。
     *
     * @return array{rows:array<int, array<string, mixed>>, aligned:array<int, string>, tension:?array<string, mixed>}
     */
    public function comparison(string $a, string $b): array
    {
        $names = $this->axes();
        $pa = $this->axisProfile($a);
        $pb = $this->axisProfile($b);

        $rows = [];
        $blank = [];
        $aligned = [];
        $tension = null;

        foreach ($names as $id => $axis) {
            /* 兩邊都沒有偏向的那條線不畫。型別在四條光譜上的訊號很稀疏(20 型裡
               15 型只偏一條、3 型完全不偏),四條全畫的話多數組合會看到三條
               「兩人都在正中間」的軌道 —— 那不是資訊,是版面。沒訊號的軸改成
               下面一行帶過。 */
            if ($pa[$id]['side'] === null && $pb[$id]['side'] === null) {
                $blank[] = trim(($axis['left'] ?? '').'／'.($axis['right'] ?? ''), '／');

                continue;
            }

            $gap = round(abs($pa[$id]['pos'] - $pb[$id]['pos']), 3);

            $state = match (true) {
                $gap >= self::CMP_APART => 'apart',
                $gap <= self::CMP_ALIGNED => 'aligned',
                default => 'mixed',
            };

            $row = [
                'id' => $id,
                'left' => (string) ($axis['left'] ?? ''),
                'right' => (string) ($axis['right'] ?? ''),
                /* 摘要句裡要用的名字。`note` 是問句(「你偏 S 還是 M」),塞進
                   「你們在…這幾條線上站得很近」會變成病句,所以另外給一個。 */
                'name' => trim(($axis['left'] ?? '').'／'.($axis['right'] ?? ''), '／'),
                'note' => (string) ($axis['note'] ?? ''),
                'a' => $pa[$id],
                'b' => $pb[$id],
                'gap' => $gap,
                'state' => $state,
            ];

            $rows[] = $row;

            /* 兩邊都持平的那一條不算「一致」—— 說「你們在這條線上很像」而依據是
               雙方都沒有偏好,講了等於沒講。 */
            if ($state === 'aligned' && ($pa[$id]['side'] !== null || $pb[$id]['side'] !== null)) {
                $aligned[] = $id;
            }

            if ($tension === null || $gap > $tension['gap']) {
                $tension = $row;
            }
        }

        // 差最大的那一條若其實沒差,就不要硬指一條出來當地雷。
        if ($tension !== null && $tension['gap'] <= self::CMP_ALIGNED) {
            $tension = null;
        }

        return [
            'rows' => $rows,
            'blank' => $blank,
            'aligned' => $aligned,
            'tension' => $tension,
            'signal' => $this->pairSignal($a, $b),
            'named' => $this->pairNamed($a, $b),
        ];
    }

    /**
     * 這一組在題庫裡的共現:幾題同時把兩型往上推(同向)、幾題把一型往上另一型
     * 往下(反向)。
     *
     * 為什麼需要這個:四條光譜對**型別**來說很稀疏,但共現是逐組算的。190 組
     * 裡有 64 組有數字 —— 覆蓋率不高,所以是「有就講、沒有就不提」的加分項,
     * 不是這一頁的骨架。
     *
     * @return array{same:int, opposite:int}
     */
    public function pairSignal(string $a, string $b): array
    {
        $same = 0;
        $opposite = 0;

        foreach (config('traits.questions') as $q) {
            $wa = (int) ($q['weights'][$a] ?? 0);
            $wb = (int) ($q['weights'][$b] ?? 0);

            if ($wa === 0 || $wb === 0) {
                continue;
            }

            if (($wa > 0) === ($wb > 0)) {
                $same++;
            } else {
                $opposite++;
            }
        }

        return ['same' => $same, 'opposite' => $opposite];
    }

    /**
     * 這一組有沒有被寫進手寫的名單裡 —— `match`／`friction` 是逐型手寫的,裡面
     * 直接點名了其他型別(「和『M屬性』『忠犬型』最順」)。
     *
     * 回傳的是**布林值,不是文字**。名單本身是付費內容,這裡只揭露「這一組有沒有
     * 被寫到」:那是結論(描述),而付費的是為什麼與該怎麼做。190 組裡 34 組有。
     *
     * @return array{match:bool, friction:bool}
     */
    public function pairNamed(string $a, string $b): array
    {
        $items = $this->lang('items');
        $out = ['match' => false, 'friction' => false];

        foreach ([[$a, $b], [$b, $a]] as [$self, $other]) {
            $name = (string) ($items[$other]['name'] ?? '');
            if ($name === '') {
                continue;
            }

            foreach (['match', 'friction'] as $field) {
                if (str_contains((string) ($items[$self][$field] ?? ''), $name)) {
                    $out[$field] = true;
                }
            }
        }

        return $out;
    }

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
