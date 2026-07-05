<?php

namespace App\Services;

class DiceGameService
{
    private const ACTIONS_MILD = ['親', '摸', '吹氣', '輕咬', '愛撫', '舔'];
    private const PARTS_MILD = ['耳朵', '脖子', '臉頰', '手指', '鎖骨', '嘴唇'];

    private const ACTIONS_MEDIUM = ['深吻', '舔', '吸', '咬', '愛撫', '揉'];
    private const PARTS_MEDIUM = ['嘴唇', '脖子', '耳垂', '鎖骨', '腰', '胸口'];

    private const ACTIONS_INTENSE = ['深吻', '舔', '吸吮', '輕咬', '愛撫', '挑逗'];
    private const PARTS_INTENSE = ['嘴唇', '耳垂', '脖子', '鎖骨', '大腿內側', '腰際'];

    private const DURATIONS = ['3 秒', '5 秒', '10 秒', '15 秒', '30 秒', '1 分鐘'];

    /**
     * Return a single merged pool per dice category (action / part / duration).
     * Difficulty tiers are no longer surfaced to the player — the free tiers
     * (mild + medium) are merged; premium additionally folds in the intense pool.
     * The client picks which dice to use and samples 6 faces per roll.
     */
    public static function getDicePools(bool $isPremium = false): array
    {
        $actions = array_merge(self::ACTIONS_MILD, self::ACTIONS_MEDIUM);
        $parts = array_merge(self::PARTS_MILD, self::PARTS_MEDIUM);
        $durations = self::DURATIONS;

        if ($isPremium) {
            $actions = array_merge($actions, self::ACTIONS_INTENSE);
            $parts = array_merge($parts, self::PARTS_INTENSE);
        }

        return [
            'actions' => array_values(array_unique($actions)),
            'parts' => array_values(array_unique($parts)),
            'durations' => array_values(array_unique($durations)),
        ];
    }
}
