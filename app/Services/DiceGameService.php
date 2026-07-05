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

    // 道具骰（成人情趣道具，Premium 解鎖更大膽的）
    private const PROPS_FREE = ['冰塊', '羽毛', '絲巾', '眼罩', '精油', '低溫蠟燭'];
    private const PROPS_INTENSE = ['手銬', '跳蛋', '項圈', '按摩棒', '口枷', '拍子'];

    /**
     * Built-in dice as a flat list. Each category (action / part / prop) offers
     * three intensity variants — 溫柔 gentle / 大膽 bold / 狂野 wild — that the
     * player picks from; 狂野 (wild) is premium-only. Time has a single die.
     *
     * `faces` is omitted (empty) for premium dice a non-premium user can't use,
     * so paid content never ships to the client. Each entry:
     *   id, cat, intensity(null|gentle|bold|wild), premium(bool), locked(bool), faces[]
     */
    public static function getBuiltInDice(bool $isPremium = false): array
    {
        $defs = [
            ['action', 'gentle', false, self::ACTIONS_MILD],
            ['action', 'bold',   false, self::ACTIONS_MEDIUM],
            ['action', 'wild',   true,  self::ACTIONS_INTENSE],
            ['part',   'gentle', false, self::PARTS_MILD],
            ['part',   'bold',   false, self::PARTS_MEDIUM],
            ['part',   'wild',   true,  self::PARTS_INTENSE],
            ['prop',   'gentle', false, self::PROPS_FREE],
            ['prop',   'wild',   true,  self::PROPS_INTENSE],
            ['time',   null,     false, self::DURATIONS],
        ];

        $out = [];
        foreach ($defs as [$cat, $intensity, $premium, $faces]) {
            $locked = $premium && ! $isPremium;
            $out[] = [
                'id' => 'builtin_' . $cat . ($intensity ? '_' . $intensity : ''),
                'cat' => $cat,
                'intensity' => $intensity,
                'premium' => $premium,
                'locked' => $locked,
                'custom' => false,
                'faces' => $locked ? [] : array_values(array_unique($faces)),
            ];
        }

        return $out;
    }
}
