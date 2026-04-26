<?php

namespace App\Services;

class DiceGameService
{
    private const ACTIONS_MILD = ['親', '摸', '吹', '按摩', '輕拍', '搓'];
    private const PARTS_MILD = ['手心', '臉頰', '額頭', '肩膀', '手臂', '頭頂'];

    private const ACTIONS_MEDIUM = ['親', '摸', '吹', '咬', '按摩', '舔'];
    private const PARTS_MEDIUM = ['耳朵', '脖子', '嘴唇', '手指', '臉頰', '鎖骨'];

    private const ACTIONS_INTENSE = ['深吻', '咬', '舔', '吸', '按摩', '抱緊'];
    private const PARTS_INTENSE = ['嘴唇', '脖子', '耳垂', '鎖骨', '腰', '大腿'];

    private const DURATIONS = ['3 秒', '5 秒', '10 秒', '15 秒', '30 秒', '1 分鐘'];

    public static function getDicePools(bool $isPremium = false): array
    {
        $pools = [
            'mild' => [
                'actions' => self::ACTIONS_MILD,
                'parts' => self::PARTS_MILD,
                'durations' => self::DURATIONS,
            ],
            'medium' => [
                'actions' => self::ACTIONS_MEDIUM,
                'parts' => self::PARTS_MEDIUM,
                'durations' => self::DURATIONS,
            ],
        ];

        if ($isPremium) {
            $pools['intense'] = [
                'actions' => self::ACTIONS_INTENSE,
                'parts' => self::PARTS_INTENSE,
                'durations' => self::DURATIONS,
            ];
        }

        return $pools;
    }
}
