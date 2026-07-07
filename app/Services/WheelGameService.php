<?php

namespace App\Services;

use App\Models\WheelSegment;

class WheelGameService
{
    private const SEGMENTS_MILD = [
        '在對方耳邊吹一口氣',
        '用最撩的眼神盯著對方 10 秒',
        '親對方的手背，再往上親到手腕',
        '對對方說一句最露骨的甜言蜜語',
        '從背後環抱對方 10 秒',
        '用指尖在對方手心慢慢畫圈',
        '把對方的頭髮撥到耳後，順勢摸一下臉',
        '貼著對方的耳朵低聲說「我想你」',
    ];

    private const SEGMENTS_MEDIUM = [
        '親對方的脖子 5 秒',
        '坐在對方腿上 20 秒',
        '咬住對方的耳垂 3 秒',
        '在耳邊說一件想對他做的事',
        '舔一下對方的手指',
        '從背後貼緊對方、在耳邊喘一口氣',
        '隔著衣服在對方鎖骨落下一個吻',
        '按摩對方肩膀、順勢滑到腰 30 秒',
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
