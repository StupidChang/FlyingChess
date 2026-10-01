<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * 把一份測驗切成好幾頁,作答一頁一頁存在 session,最後一頁才交卷。
 *
 * 為什麼是真的換頁而不是 JS 切題組:每一頁是一次獨立的瀏覽,廣告跟著重新投放。
 * JS 切換不會讓廣告重新曝光,而自己去刷新廣告違反聯播網規定。
 *
 * 切頁照題目的 section 邊界走,一頁最多 PER_PAGE 題 —— 段落標題留在每一頁的
 * 開頭,不會在一段的中間被切斷。整段就超過上限的話,那一段自己一頁。
 *
 * 只有作答存在 session,**分數不存** —— 交卷那一刻才算,跟原本一樣。
 * session 過期的話最後一頁會發現前面缺題,把人帶回第一個沒答完的頁。
 */
final class QuizSteps
{
    public const PER_PAGE = 20;

    /** @var array<int, int[]> 頁碼(從 1 起)=> 這一頁的題號 */
    private array $pages = [];

    /**
     * @param  array<int, array>  $structure  config 裡的題目結構(看 section 切頁)
     */
    public function __construct(
        private readonly string $sessionKey,
        private readonly array $structure,
        private readonly int $min,
        private readonly int $max,
    ) {
        $page = [];
        foreach (array_keys($structure) as $i) {
            $startsSection = isset($structure[$i]['section']);
            if ($page && $startsSection && count($page) + $this->sectionLength($i) > self::PER_PAGE) {
                $this->pages[count($this->pages) + 1] = $page;
                $page = [];
            }
            $page[] = $i;
        }
        if ($page) {
            $this->pages[count($this->pages) + 1] = $page;
        }
    }

    public function count(): int
    {
        return count($this->pages);
    }

    public function total(): int
    {
        return count($this->structure);
    }

    /** 網址上的 ?p= 收斂到合法範圍內,亂填的一律當第一頁。 */
    public function clamp(mixed $p): int
    {
        $p = (int) $p;

        return $p >= 1 && $p <= $this->count() ? $p : 1;
    }

    /** @return int[] */
    public function questionsOn(int $page): array
    {
        return $this->pages[$page] ?? [];
    }

    /** @return array<int, int> 題號 => 答案 */
    public function saved(Request $request): array
    {
        return (array) $request->session()->get($this->sessionKey, []);
    }

    /** 第一個還沒答完的頁;全部答完回 null。 */
    public function firstIncomplete(array $saved, ?int $before = null): ?int
    {
        foreach ($this->pages as $n => $indices) {
            if ($before !== null && $n >= $before) {
                return null;
            }
            foreach ($indices as $i) {
                if (! array_key_exists($i, $saved)) {
                    return $n;
                }
            }
        }

        return null;
    }

    /**
     * 存下這一頁的作答。$strict 時這一頁每題都要答 —— 「下一頁」要擋,
     * 「上一頁」不擋(答一半往回翻是正常的,答了的那幾題照樣留著)。
     *
     * @return array<int, int> 存完之後的全部作答
     */
    public function store(Request $request, int $page, bool $strict, string $attribute, string $unansweredKey): array
    {
        $saved = $this->saved($request);
        $given = (array) $request->input('a', []);
        $missing = 0;

        foreach ($this->questionsOn($page) as $i) {
            $v = $given[$i] ?? null;
            if ($v === null || $v === '') {
                $missing++;

                continue;
            }
            if (! is_numeric($v) || (int) $v != $v || $v < $this->min || $v > $this->max) {
                throw ValidationException::withMessages(['a.'.$i => trans('validation.between.numeric', [
                    'attribute' => $attribute, 'min' => $this->min, 'max' => $this->max,
                ])]);
            }
            $saved[$i] = (int) $v;
        }

        $request->session()->put($this->sessionKey, $saved);

        if ($strict && $missing > 0) {
            throw ValidationException::withMessages(['a' => trans($unansweredKey, ['n' => $missing])]);
        }

        return $saved;
    }

    /** 交卷之後清掉,重做一次要從空白開始。 */
    public function forget(Request $request): void
    {
        $request->session()->forget($this->sessionKey);
    }

    /** 從第 $i 題(一段的開頭)起,這一段有幾題。 */
    private function sectionLength(int $start): int
    {
        $n = 0;
        foreach ($this->structure as $i => $q) {
            if ($i < $start) {
                continue;
            }
            if ($i > $start && isset($q['section'])) {
                break;
            }
            $n++;
        }

        return $n;
    }
}
