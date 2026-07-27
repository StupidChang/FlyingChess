<?php

namespace App\Services;

use App\Models\WheelSegment;

class WheelGameService
{
    private const SEGMENTS_MILD = [
        '靠過去，在對方耳邊說：「你今天真的很可以。」',
        '盯著對方放電 15 秒，誰先笑誰輸',
        '牽起對方的手，從手背慢慢親到手腕',
        '講一句你平常不好意思說的撩人話',
        '從背後抱緊對方，貼著 20 秒再放開',
        '捏住對方下巴，停在快親到的位置 10 秒',
        '挑一個地方，讓對方親一下',
        '摟著對方慢慢晃 30 秒',
    ];

    private const SEGMENTS_MEDIUM = [
        '親對方脖子 15 秒，位置讓對方選',
        '坐到對方腿上，面對面貼著 30 秒',
        '含住對方耳垂，再問一句：「這樣喜歡嗎？」',
        '貼著耳朵講一件今晚真的想做的事',
        '含住對方一根手指 5 秒',
        '把對方輕輕壓在牆邊，深吻 20 秒',
        '從鎖骨一路親到胸口，衣服內外都可以',
        '沿著大腿摸到內側，停在對方說可以的位置',
    ];

    private const SEGMENTS_INTENSE = [
        '把對方壓到床上或沙發上，深吻 1 分鐘',
        '跨坐在對方腿上，照喜歡的節奏磨蹭 30 秒',
        '伸進衣服裡，摸一個對方同意的私密部位 30 秒',
        '兩個人各脫一件衣物，再抱緊親吻 30 秒',
        '指定一個私密部位，讓對方親 30 秒',
        '用手幫對方刺激 1 分鐘，力道由對方決定',
        '幫對方口交 1 分鐘；不想做就換同等級任務',
        '挑一個雙方都想試的體位，維持或模擬 1 分鐘',
        '坐到對方臉上磨蹭 30 秒，姿勢以舒服為主',
        '跪在對方面前，用嘴隔著內褲挑逗 30 秒',
        '脫掉對方內褲，再親大腿內側 45 秒',
        '用潤滑液幫對方按摩私密處 1 分鐘',
        '讓對方選：口交 1 分鐘，或手玩 2 分鐘',
        '示範自己最喜歡被摸的方式 45 秒',
        '選一個最想重現的成人姿勢，模擬 1 分鐘',
        '用舌頭挑逗對方乳頭或其他敏感點 45 秒',
        '戴上眼罩，讓對方決定接下來三個觸碰位置',
        '從背後抱住對方，用手刺激 1 分鐘',
        '把對方的手帶到自己現在最想被碰的位置',
        '互相描述最想做的前戲，選一個立刻開始',
        '挑一個安全的束縛方式，控制對方 2 分鐘',
        '用嘴含住對方最敏感的位置 30 秒',
        '一個人決定快慢，另一個人決定深淺，互動 1 分鐘',
        '各說一個界線和一個想玩的項目，選共同項目進行',
    ];

    public static function getSegmentPools(bool $isPremium = false): array
    {
        $pools = self::loadFromDb($isPremium);

        if (! empty($pools['mild']) && ! empty($pools['medium']) && ! empty($pools['intense'])) {
            return $pools;
        }

        // Fallback to hardcoded constants when DB is empty
        $pools = [
            'mild' => self::SEGMENTS_MILD,
            'medium' => self::SEGMENTS_MEDIUM,
        ];

        $pools['intense'] = $isPremium
            ? self::SEGMENTS_INTENSE
            : array_slice(self::SEGMENTS_INTENSE, 0, intdiv(count(self::SEGMENTS_INTENSE), 2));

        return $pools;
    }

    private static function loadFromDb(bool $isPremium): array
    {
        $query = WheelSegment::query();

        $segments = $query->orderBy('id')->get();

        if ($segments->isEmpty()) {
            return [];
        }

        $pools = [];
        foreach ($segments->groupBy('tier') as $tier => $items) {
            $pools[$tier] = $items->pluck('content')->toArray();
        }

        if (! $isPremium && ! empty($pools['intense'])) {
            $pools['intense'] = array_slice(
                $pools['intense'],
                0,
                intdiv(count($pools['intense']), 2),
            );
        }

        return $pools;
    }
}
