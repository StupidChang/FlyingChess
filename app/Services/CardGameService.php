<?php

namespace App\Services;

class CardGameService
{
    private const MILD_ACTIVITIES = [
        '互相凝視 10 秒不能笑',
        '牽手轉一圈',
        '說出對方一個優點',
        '互相比心 5 秒',
        '用搞笑語氣叫對方的名字',
        '牌大的幫牌小的撥頭髮',
        '牌小的要對牌大的說「你好帥/美」',
        '牌大的戳牌小的臉頰 3 下',
        '兩人十指交扣 10 秒',
        '牌小的模仿牌大的招牌動作',
    ];

    private const MEDIUM_ACTIVITIES = [
        '牌大的親牌小的臉頰 3 秒',
        '兩人擁抱 15 秒',
        '牌大的幫牌小的按摩肩膀 20 秒',
        '牌小的坐在牌大的腿上 10 秒',
        '牌大的對牌小的耳邊說悄悄話',
        '牌小的從背後抱住牌大的 15 秒',
        '牌大的摸牌小的頭 10 秒',
        '兩人額頭貼額頭 10 秒',
        '牌小的幫牌大的捏臉 10 秒',
        '牌大的公主抱牌小的 5 秒',
    ];

    private const INTENSE_ACTIVITIES = [
        '牌大的親牌小的嘴唇 5 秒',
        '牌小的坐在牌大的腿上互動 20 秒',
        '牌大的摸牌小的屁股 10 秒',
        '兩人到隱蔽處單獨相處 1 分鐘',
        '牌小的讓牌大的選一個部位親',
        '牌大的從牌小的脖子親到鎖骨',
        '兩人在床上互動 30 秒',
        '牌大的幫牌小的脫掉一件外層衣物',
        '牌小的用最撩人的方式對牌大的說我想你',
        '牌大的按摩牌小的大腿 15 秒',
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
