<?php

namespace App\Services;

use App\Support\LocaleHelper;

/**
 * 色度測驗的計分 —— 兩條軸。
 *
 * 和 RepressionTestService / TraitTestService 同一個原則:計分**只在伺服器做**。
 * 關鍵資訊是「哪幾題是反向題」,那等於答案卷,一旦送到瀏覽器,任何人都能把分數
 * 刷成他想要的位置。前端只拿得到題目文字與五個選項。
 *
 * 結構在 config/horny.php,文案在 lang/{locale}/horny.php。
 *
 * 為什麼是兩條軸而不是一個指數:一個數字分不出「想要卻踩著煞車」和「本來就不太
 * 想要」這兩種人,而那兩種人需要的東西剛好相反。見 config/horny.php 的檔頭。
 */
class HornyTestService
{
    /** 量表是五點:0…4。兩條軸都是「有多少」不是「偏哪邊」,所以不用 -2…+2。 */
    public const MIN = 0;

    public const MAX = 4;

    /** 離中央那一塊這麼近就要提醒:重測很可能換一格。 */
    public const EDGE_NEAR = 5;

    /**
     * 顯示用的題目。只有文字與段落標題 —— 正反向留在伺服器。
     *
     * @return array<int, array{n:int, text:string, section:?string}>
     */
    public function questions(): array
    {
        $structure = (array) config('horny.questions');
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
     * 每一題先換算成「該面向的分」:正向題直接用答案,反向題用 MAX - 答案。所以
     * 同一個面向裡兩種題目都是「越高越多」,可以直接相加。
     *
     * 面向分數 = 該面向總分 ÷ 滿分 × 100。
     * 軸分數 = 該軸底下各面向的加權平均 —— 用面向平均而不是總題數平均,是為了讓
     * 「某個面向題目多寡」不影響軸分數,將來加題才不會默默改變所有人的位置。
     *
     * @param  array<int, int>  $answers  題號 => 0…4
     */
    public function score(array $answers): array
    {
        $dims = (array) config('horny.dimensions');
        $sum = [];
        $max = [];
        foreach (array_keys($dims) as $key) {
            $sum[$key] = 0;
            $max[$key] = 0;
        }

        foreach ((array) config('horny.questions') as $i => $q) {
            $a = max(self::MIN, min(self::MAX, (int) ($answers[$i] ?? 0)));

            $dim = $q['dim'] ?? null;
            if ($dim === null || ! isset($sum[$dim])) {
                continue;   // config 打錯字不該讓整個測驗炸掉
            }

            // 反向題:同意代表這個面向低,所以要翻過來才能和正向題相加
            $sum[$dim] += ($q['dir'] ?? 1) < 0 ? self::MAX - $a : $a;
            $max[$dim] += self::MAX;
        }

        /* 面向照 config 的順序回傳(油門四條在前、煞車五條在後)——
           結果頁的長條與雷達是分組畫的,順序不能被分數高低打亂。
           「最明顯的一項」用另外排序過的 ranked。 */
        $dimensions = [];
        $axisWeighted = [];
        $axisWeight = [];
        foreach ($sum as $key => $v) {
            $pct = $max[$key] > 0 ? $v / $max[$key] * 100 : 0;
            $axis = $dims[$key]['axis'] ?? null;
            $dimensions[] = ['key' => $key, 'axis' => $axis, 'pct' => (int) round($pct)];

            if ($axis !== null) {
                $w = (float) ($dims[$key]['weight'] ?? 1);
                $axisWeighted[$axis] = ($axisWeighted[$axis] ?? 0) + $pct * $w;
                $axisWeight[$axis] = ($axisWeight[$axis] ?? 0) + $w;
            }
        }

        $axes = [];
        foreach (array_keys((array) config('horny.axes')) as $axis) {
            $w = $axisWeight[$axis] ?? 0;
            $axes[$axis] = $w > 0 ? (int) round(($axisWeighted[$axis] ?? 0) / $w) : 0;
        }

        $ranked = $dimensions;
        usort($ranked, fn ($x, $y) => $y['pct'] <=> $x['pct']);

        /* 作答本身的統計。每題都選中間的人和每題都選兩端的人可以拿到很接近的
           位置,但前者的結果幾乎沒有區辨力 —— 只有這裡看得出來。 */
        $meta = ['total' => count((array) config('horny.questions')), 'decisive' => 0, 'neutral' => 0];
        $mid = (self::MAX + self::MIN) / 2;
        foreach ((array) config('horny.questions') as $i => $q) {
            $a = max(self::MIN, min(self::MAX, (int) ($answers[$i] ?? 0)));
            if ($a === self::MIN || $a === self::MAX) {
                $meta['decisive']++;
            } elseif ((float) $a === $mid) {
                $meta['neutral']++;
            }
        }

        return [
            'axes' => $axes,
            'quadrant' => $this->quadrantFor($axes['desire'] ?? 0, $axes['brake'] ?? 0),
            'dimensions' => $dimensions,
            'ranked' => $ranked,
            'meta' => $meta,
        ];
    }

    /**
     * 色度 → 級距(判定用)。
     *
     * 由低往高找最後一個「分數 >= 下界」的級距,所以 config 的 desire_levels 順序
     * (低到高)就是唯一的真相,不用在兩個地方各寫一次上下界。
     *
     * 為什麼象限之外還要這個:象限只講「你在哪一格」,講不出程度 —— 色度 62 和
     * 色度 98 都落在同一個角,但那兩個人想聽到的話完全不同。而這份測驗的主角
     * 是「你有多色」,不是「你卡在哪」。
     */
    public function desireLevel(int $pct): array
    {
        $levels = (array) config('horny.desire_levels');
        $hit = (string) array_key_first($levels);

        foreach ($levels as $key => $level) {
            if ($pct >= ($level['min'] ?? 0)) {
                $hit = $key;
            }
        }

        $text = $this->lang('desire_levels')[$hit] ?? [];

        return [
            'key' => $hit,
            'name' => $text['name'] ?? $hit,
            'line' => $text['line'] ?? '',
            'min' => (int) ($levels[$hit]['min'] ?? 0),
        ];
    }

    /** 中央那一塊的半徑(分)。 */
    public function middleBand(): int
    {
        return (int) config('horny.middle_band', 10);
    }

    /**
     * 兩條軸 → 象限代碼。
     *
     * 兩條軸都落在 50 ± middle_band 之內就是中央那一塊。少了這個中央帶,49 分和
     * 51 分會被丟到完全不同的兩頁,而那個差距在一份自陳量表裡沒有意義。
     */
    public function quadrantFor(int $desire, int $brake): string
    {
        $band = $this->middleBand();
        $quadrants = (array) config('horny.quadrants');

        if (abs($desire - 50) <= $band && abs($brake - 50) <= $band) {
            foreach ($quadrants as $key => $q) {
                if (($q['desire'] ?? null) === 'mid') {
                    return $key;
                }
            }
        }

        $want = [
            'desire' => $desire >= 50 ? 'high' : 'low',
            'brake' => $brake >= 50 ? 'high' : 'low',
        ];

        foreach ($quadrants as $key => $q) {
            if (($q['desire'] ?? null) === $want['desire'] && ($q['brake'] ?? null) === $want['brake']) {
                return $key;
            }
        }

        return (string) array_key_first($quadrants);
    }

    /**
     * 量測依據:幾題、兩條軸各幾題、每個面向正反向對不對稱、量表幾點。
     *
     * 全部從 config 現算,不是寫死的數字 —— 加題、改反向題,頁面上跟著改。
     * 手寫的話遲早對不上,而對不上的「依據」比沒有依據更糟。
     */
    public function basis(): array
    {
        $meta = $this->lang('dimensions');
        $dimsConfig = (array) config('horny.dimensions');
        $tally = [];
        $axisCount = [];

        foreach ((array) config('horny.questions') as $q) {
            $dim = $q['dim'] ?? null;
            if ($dim === null) {
                continue;
            }

            $side = ($q['dir'] ?? 1) < 0 ? 'reverse' : 'forward';
            $tally[$dim][$side] = ($tally[$dim][$side] ?? 0) + 1;

            $axis = $dimsConfig[$dim]['axis'] ?? null;
            if ($axis !== null) {
                $axisCount[$axis] = ($axisCount[$axis] ?? 0) + 1;
            }
        }

        $dimensions = [];
        $symmetric = true;
        foreach ($tally as $key => $t) {
            $forward = $t['forward'] ?? 0;
            $reverse = $t['reverse'] ?? 0;
            if ($forward !== $reverse) {
                $symmetric = false;   // 對稱壞了要看得出來,不是靜靜偏高
            }

            $dimensions[] = [
                'key' => $key,
                'axis' => $dimsConfig[$key]['axis'] ?? null,
                'name' => $meta[$key]['name'] ?? $key,
                'count' => $forward + $reverse,
                'forward' => $forward,
                'reverse' => $reverse,
            ];
        }

        return [
            'total' => count((array) config('horny.questions')),
            'points' => self::MAX - self::MIN + 1,
            'axis_counts' => $axisCount,
            'dimensions' => $dimensions,
            'symmetric' => $symmetric,
            'middle_band' => $this->middleBand(),
        ];
    }

    /**
     * 這一份結果有多站得住腳。
     *
     * 49 分和 51 分會被分到不同的頁、拿到不同的解讀,但那兩分之差在一份 64 題的
     * 自陳量表裡沒有意義。離中央那一塊很近就該講出來 —— 不講的話,這一頁看起來
     * 比它實際上更確定。
     */
    public function confidence(array $result): array
    {
        $meta = $this->lang('dimensions');
        $ranked = $result['ranked'] ?? [];
        if (! $ranked) {
            return [];
        }

        $desire = (int) ($result['axes']['desire'] ?? 0);
        $brake = (int) ($result['axes']['brake'] ?? 0);
        $quadrant = $result['quadrant'] ?? $this->quadrantFor($desire, $brake);
        $isMiddle = (config("horny.quadrants.{$quadrant}.desire") ?? null) === 'mid';

        /* 離中間多遠:取兩條軸裡**比較近**的那一條。比較近的那一條才是會先翻面的
           那一條,取平均或取遠的都會低估不確定性。 */
        $dist = min(abs($desire - 50), abs($brake - 50));

        $top = $ranked[0];
        $bottom = end($ranked);

        return [
            'axes' => ['desire' => $desire, 'brake' => $brake],
            'quadrant' => $quadrant,
            'is_middle' => $isMiddle,
            'middle_band' => $this->middleBand(),
            'dist' => $dist,
            // 中央那一塊本來就是「不確定」的那一格,不用再提醒一次
            'near_edge' => ! $isMiddle && $dist <= self::EDGE_NEAR,
            'gap' => abs($desire - $brake),
            'top' => $meta[$top['key']]['name'] ?? $top['key'],
            'top_pct' => $top['pct'],
            'low' => $meta[$bottom['key']]['name'] ?? $bottom['key'],
            'low_pct' => $bottom['pct'],
            'meta' => $result['meta'] ?? null,
        ];
    }

    /**
     * 依使用者自己的分數,挑出每一個面向該講哪一段。
     *
     * 「同一格的每個人拿到同一份範本」是這類測驗最常被批評的地方,所以這一段是
     * 照實際分數算的,不是照象限查表。
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
                'axis' => $d['axis'] ?? (config("horny.dimensions.{$d['key']}.axis") ?? null),
                'name' => $meta[$d['key']]['name'] ?? $d['key'],
                'note' => $meta[$d['key']]['note'] ?? '',
                'pct' => $d['pct'],
                'text' => $text[$d['key']][$band] ?? '',
            ];
        }

        return $out;
    }

    /** 網址片段 → 象限代碼。找不到回 null。 */
    public function keyFromSlug(string $slug): ?string
    {
        foreach ($this->lang('quadrants') as $key => $q) {
            if (($q['slug'] ?? null) === $slug) {
                return $key;
            }
        }

        return null;
    }

    public function slug(string $key): ?string
    {
        return $this->lang('quadrants')[$key]['slug'] ?? null;
    }

    /** 一個象限的全部文案(含結構面的顏色與方向)。 */
    public function quadrant(string $key): array
    {
        $q = $this->lang('quadrants')[$key] ?? [];
        $q['key'] = $key;
        $q['colour'] = config("horny.quadrants.{$key}.colour", 'gold');
        $q['desire'] = config("horny.quadrants.{$key}.desire", 'mid');
        $q['brake'] = config("horny.quadrants.{$key}.brake", 'mid');

        return $q;
    }

    /**
     * 全部五個象限,照 config 的順序 —— 結果頁的互連清單與題目頁的入口都用這個。
     *
     * @return array<int, array>
     */
    public function quadrants(): array
    {
        return array_map(fn ($key) => $this->quadrant($key), array_keys((array) config('horny.quadrants')));
    }

    /** 兩條軸的名稱與兩端的標籤(象限圖的四邊)。 */
    public function axes(): array
    {
        $meta = $this->lang('axes');
        $out = [];
        foreach (array_keys((array) config('horny.axes')) as $key) {
            $out[$key] = $meta[$key] ?? ['name' => $key, 'note' => '', 'low' => '', 'high' => ''];
        }

        return $out;
    }

    /** 面向的名稱與說明,照 config 的順序(油門在前)。 */
    public function dimensions(): array
    {
        $meta = $this->lang('dimensions');
        $out = [];
        foreach ((array) config('horny.dimensions') as $key => $cfg) {
            $out[$key] = ($meta[$key] ?? ['name' => $key, 'note' => '']) + ['axis' => $cfg['axis'] ?? null];
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
        return in_array($locale ?? app()->getLocale(), (array) config('horny.translated', []), true);
    }

    /** 讀文案。沒翻譯的語系一律退回預設語系,而不是讓畫面出現一串 key。 */
    private function lang(string $key): array
    {
        $locale = $this->isTranslated() ? app()->getLocale() : LocaleHelper::defaultLocale();

        return (array) trans("horny.{$key}", [], $locale);
    }
}
