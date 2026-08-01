<?php

namespace App\Services;

class KingGameService
{
    private const COMMANDS_MILD = [
        '{A} 在 {B} 耳邊吹一口氣',
        '{A} 用最撩的眼神盯著 {B} 看 10 秒',
        '{A} 親 {B} 的手背，再往上親到手腕',
        '{A} 對 {B} 說一句最露骨的甜言蜜語',
        '{A} 從背後環抱 {B}，下巴靠在肩上 10 秒',
        '{A} 用指尖在 {B} 的手心慢慢畫圈',
        '{A} 幫 {B} 把頭髮撥到耳後，順勢摸一下臉',
        '{A} 貼著 {B} 的耳朵，低聲說「我想你」',
    ];

    private const COMMANDS_MEDIUM = [
        '{A} 親 {B} 的脖子 5 秒',
        '{A} 坐在 {B} 的腿上 20 秒',
        '{A} 對 {B} 的耳邊說一件想對他做的事',
        '{A} 幫 {B} 按摩肩膀、順勢滑到腰 30 秒',
        '{A} 咬住 {B} 的耳垂 3 秒',
        '{A} 舔一下 {B} 的手指',
        '{A} 從背後貼緊 {B}，在耳邊喘一口氣',
        '{A} 隔著衣服在 {B} 的鎖骨落下一個吻',
    ];

    private const COMMANDS_INTENSE = [
        '{A} 深吻 {B} 10 秒',
        '{A} 讓 {B} 選一個部位，任他親',
        '{A} 從 {B} 的脖子一路親到鎖骨',
        '{A} 用最露骨的話，說出現在最想對 {B} 做什麼',
        '{A} 跟 {B} 到隱蔽處獨處 1 分鐘',
        '{A} 幫 {B} 脫掉一件外層衣物',
        '{A} 蒙住 {B} 的眼，慢慢在他身上落下三個吻',
        '{A} 在 {B} 的大腿內側愛撫 15 秒',
        '{A} 幫 {B} 口交 1 分鐘',
        '{A} 用手刺激 {B} 的私密處 1 分鐘',
        '{A} 挑一個體位，和 {B} 直接試 2 分鐘',
        '{A} 用手指幫 {B}，進去後快慢都聽 {B} 的',
        '{A} 戴好保險套，姿勢和深淺都讓 {B} 決定',
        '{A} 挑一樣情趣玩具，陪 {B} 玩 1 分鐘',
        '{A} 從後面進入 {B}，照 {B} 喜歡的節奏來 1 分鐘',
        '{A} 和 {B} 各講一個不行、一個想玩，再挑共同的直接做',
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
