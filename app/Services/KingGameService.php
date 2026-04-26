<?php

namespace App\Services;

class KingGameService
{
    private const COMMANDS_MILD = [
        '{A} 跟 {B} 互相凝視 10 秒不能笑',
        '{A} 對 {B} 說一句讚美的話',
        '{A} 模仿 {B} 的招牌動作',
        '{A} 跟 {B} 比心拍照',
        '{A} 幫 {B} 倒一杯飲料',
        '{A} 對 {B} 做鬼臉 5 秒',
        '{A} 用搞笑語氣叫 {B} 的名字',
        '{A} 跟 {B} 猜拳，輸的做 10 下深蹲',
    ];

    private const COMMANDS_MEDIUM = [
        '{A} 親 {B} 的臉頰',
        '{A} 從背後抱住 {B} 10 秒',
        '{A} 對 {B} 的耳邊說悄悄話',
        '{A} 幫 {B} 按摩肩膀 30 秒',
        '{A} 跟 {B} 牽手繞房間一圈',
        '{A} 摸 {B} 的頭 10 秒',
        '{A} 餵 {B} 吃一口東西',
        '{A} 跟 {B} 臉貼臉拍照',
    ];

    private const COMMANDS_INTENSE = [
        '{A} 親 {B} 的嘴唇 5 秒',
        '{A} 坐在 {B} 的腿上 30 秒',
        '{A} 讓 {B} 選一個部位親',
        '{A} 用最撩的方式對 {B} 說「我想你」',
        '{A} 從 {B} 的脖子親到鎖骨',
        '{A} 跟 {B} 到隱蔽處獨處 1 分鐘',
        '{A} 幫 {B} 脫掉一件外層衣物',
        '{A} 在 {B} 耳邊說最想做的事',
    ];

    public static function getCommandPools(bool $isPremium = false): array
    {
        $pools = [
            'mild' => self::COMMANDS_MILD,
            'medium' => self::COMMANDS_MEDIUM,
        ];

        if ($isPremium) {
            $pools['intense'] = self::COMMANDS_INTENSE;
        }

        return $pools;
    }
}
