<?php

namespace App\Services;

class CardGameService
{
    private const MILD_ACTIVITIES = [
        '牌大的在牌小的耳邊吹一口氣',
        '牌大的用最撩的眼神盯著牌小的 10 秒',
        '牌大的親牌小的手背，再往上親到手腕',
        '牌小的對牌大的說一句最露骨的甜言蜜語',
        '牌大的從背後環抱牌小的 10 秒',
        '牌大的用指尖在牌小的手心慢慢畫圈',
        '牌大的把牌小的頭髮撥到耳後，順勢摸一下臉',
        '牌小的貼著牌大的耳朵低聲說「我想你」',
        '牌大的輕捏牌小的下巴，湊近到快要親上',
        '兩人十指交扣、額頭相抵凝視 10 秒',
    ];

    private const MEDIUM_ACTIVITIES = [
        '牌大的親牌小的脖子 5 秒',
        '牌小的坐在牌大的腿上 20 秒',
        '牌大的幫牌小的按摩肩膀、順勢滑到腰 20 秒',
        '牌大的咬住牌小的耳垂 3 秒',
        '牌大的在牌小的耳邊說一件想對他做的事',
        '牌小的從背後貼緊牌大的，在耳邊喘一口氣',
        '牌大的舔一下牌小的手指',
        '牌大的隔著衣服在牌小的鎖骨落下一個吻',
        '牌小的用嘴唇輕輕蹭過牌大的下巴到耳側',
        '兩人臉貼臉，鼻尖磨蹭到快要親上 15 秒',
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
