<?php

namespace App\Services;

use App\Models\WheelSegment;

class WheelGameService
{
    private const SEGMENTS_MILD = [
        '互相凝視 10 秒',
        '說出對方的優點',
        '模仿對方動作',
        '牽手轉一圈',
        '互相比心',
        '用一個詞形容對方',
        '對方做鬼臉',
        '幫對方按手',
    ];

    private const SEGMENTS_MEDIUM = [
        '親對方臉頰',
        '擁抱 10 秒',
        '按摩肩膀 30 秒',
        '耳邊說悄悄話',
        '摸對方的頭',
        '從背後擁抱',
        '互餵吃東西',
        '面對面跳舞',
    ];

    private const SEGMENTS_INTENSE = [
        '親嘴 5 秒',
        '坐在對方腿上',
        '讓對方選部位親',
        '脫一件外層衣物',
        '從脖子親到鎖骨',
        '耳邊說想做的事',
        '獨處 1 分鐘',
        '對方可以要求一件事',
    ];

    public static function getSegmentPools(bool $isPremium = false): array
    {
        $pools = self::loadFromDb($isPremium);

        if (!empty($pools['mild']) && !empty($pools['medium'])) {
            return $pools;
        }

        // Fallback to hardcoded constants when DB is empty
        $pools = [
            'mild' => self::SEGMENTS_MILD,
            'medium' => self::SEGMENTS_MEDIUM,
        ];

        if ($isPremium) {
            $pools['intense'] = self::SEGMENTS_INTENSE;
        }

        return $pools;
    }

    private static function loadFromDb(bool $isPremium): array
    {
        $query = WheelSegment::query();

        if (!$isPremium) {
            $query->whereIn('tier', ['mild', 'medium']);
        }

        $segments = $query->get();

        if ($segments->isEmpty()) {
            return [];
        }

        $pools = [];
        foreach ($segments->groupBy('tier') as $tier => $items) {
            $pools[$tier] = $items->pluck('content')->toArray();
        }

        return $pools;
    }
}
