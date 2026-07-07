<?php

namespace App\Services;

class WhoMostLikelyService
{
    // 內容一律成人向（情侶/派對），與 KingGameService / DiceGameService 相同：硬寫繁中，不隨語系翻譯。
    // 每一句都會接在「誰最有可能……」後面呈現。
    // mild = 曖昧調情、medium = 大膽性暗示、intense = 火辣（Premium）。

    private const PROMPTS_MILD = [
        '約會時主動靠過去、故意貼很近',
        '用眼神放電勾引另一半',
        '傳深夜「睡了沒」的曖昧訊息',
        '忍不住偷看伴侶換衣服',
        '第一次約會就想接吻',
        '在公開場合偷偷摸一把',
        '主動提議一起洗澡',
        '為了讓對方心動而精心打扮性感',
        '在床上先撒嬌討抱討親',
        '把約會氣氛帶往曖昧的方向',
        '講話故意留一堆性暗示',
        '主動牽手、十指緊扣不放開',
    ];

    private const PROMPTS_MEDIUM = [
        '主動開口求歡',
        '在沙發上就忍不住黏上去',
        '傳性感自拍給另一半',
        '提議玩大膽一點的情侶遊戲',
        '大白天就忍不住想要',
        '主動嘗試新姿勢',
        '偷偷去買情趣用品',
        '在浴室裡擦槍走火',
        '一整晚不讓對方好好睡',
        '約會約到一半就想找地方獨處',
        '在對方耳邊說很撩的話',
        '主動幫對方寬衣解帶',
    ];

    private const PROMPTS_INTENSE = [
        '提議嘗試比較重口味的玩法',
        '在可能被聽到的地方忍不住出聲',
        '主動要求被綁起來或主導對方',
        '一個晚上要好幾次還不滿足',
        '提議角色扮演的情境',
        '在臥室以外的地方忍不住做',
        '用最露骨的話撩到對方臉紅',
        '挑最大膽的情趣道具來玩',
        '主動蒙眼、玩感官刺激',
        '整晚主導、不讓對方喘息',
        '把一次的約會直接變成過夜',
        '提議來一場從早到晚的放縱',
    ];

    public static function getPromptPools(bool $isPremium = false): array
    {
        $pools = [
            'mild' => self::PROMPTS_MILD,
            'medium' => self::PROMPTS_MEDIUM,
        ];

        if ($isPremium) {
            $pools['intense'] = self::PROMPTS_INTENSE;
        }

        return $pools;
    }
}
