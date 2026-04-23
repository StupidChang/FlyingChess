<?php

namespace App\Services;

class CardGameService
{
    private const MILD_ACTIVITIES = [
        '互相凝視 10 秒不能笑',
        '牽手轉一圈',
        '說出對方一個優點',
        '模仿對方的招牌動作',
        '互相比心',
        '用一個詞形容對方',
        '跟對方擊掌三次',
        '對對方做鬼臉 5 秒',
        '互相鞠躬打招呼',
        '用搞笑語氣叫對方名字',
    ];

    private const MEDIUM_ACTIVITIES = [
        '親對方臉頰 3 秒',
        '擁抱對方 10 秒',
        '幫對方按摩肩膀 30 秒',
        '對對方耳邊說悄悄話',
        '牽手散步到門口再回來',
        '摸摸對方的頭 10 秒',
        '從背後抱住對方 15 秒',
        '互餵對方吃一口東西',
        '面對面跳一段雙人舞',
        '幫對方整理髮型',
    ];

    private const INTENSE_ACTIVITIES = [
        '親對方嘴唇 5 秒',
        '到隱蔽處單獨相處 1 分鐘',
        '讓對方選一個部位親你',
        '坐在對方腿上 30 秒',
        '對方可以要求你做一件事',
        '用最撩人的方式說我想你',
        '幫對方脫掉一件外層衣物',
        '從脖子親到鎖骨',
        '互相按摩手臂和手掌 1 分鐘',
        '在對方耳邊說最想做的事',
    ];

    /**
     * Return activity pools for client-side use.
     * Intense pool is only included for premium users.
     */
    public static function getActivityPools(bool $isPremium = false): array
    {
        $pools = [
            'mild' => self::MILD_ACTIVITIES,
            'medium' => self::MEDIUM_ACTIVITIES,
        ];

        if ($isPremium) {
            $pools['intense'] = self::INTENSE_ACTIVITIES;
        }

        return $pools;
    }
}
