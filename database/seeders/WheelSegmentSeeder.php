<?php

namespace Database\Seeders;

use App\Models\WheelSegment;
use Illuminate\Database\Seeder;

class WheelSegmentSeeder extends Seeder
{
    public function run(): void
    {
        $segments = [
            // ── Mild（曖昧調情，成人向）──
            ['在對方耳邊吹一口氣', 'mild'],
            ['用最撩的眼神盯著對方 10 秒', 'mild'],
            ['親對方的手背，再往上親到手腕', 'mild'],
            ['對對方說一句最露骨的甜言蜜語', 'mild'],
            ['從背後環抱對方 10 秒', 'mild'],
            ['用指尖在對方手心慢慢畫圈', 'mild'],
            ['把對方的頭髮撥到耳後，順勢摸一下臉', 'mild'],
            ['貼著對方的耳朵低聲說「我想你」', 'mild'],
            ['輕捏對方下巴，湊近到快要親上', 'mild'],
            ['十指交扣、額頭相抵凝視 10 秒', 'mild'],
            ['用嘴唇輕輕蹭過對方的下巴到耳側', 'mild'],
            ['隔著衣領在對方頸邊呼一口熱氣', 'mild'],

            // ── Medium（大膽親密，成人向）──
            ['親對方的脖子 5 秒', 'medium'],
            ['坐在對方腿上 20 秒', 'medium'],
            ['咬住對方的耳垂 3 秒', 'medium'],
            ['在耳邊說一件想對他做的事', 'medium'],
            ['舔一下對方的手指', 'medium'],
            ['從背後貼緊對方、在耳邊喘一口氣', 'medium'],
            ['隔著衣服在對方鎖骨落下一個吻', 'medium'],
            ['按摩對方肩膀、順勢滑到腰 30 秒', 'medium'],
            ['含住對方的指尖 3 秒', 'medium'],
            ['把對方壓在牆上對視 10 秒', 'medium'],
            ['沿著對方的頸線一路輕吻到耳後', 'medium'],
            ['把手滑進對方衣襬、貼著腰 10 秒', 'medium'],

            // ── Intense（火辣挑戰，成人向）──
            ['深吻對方 10 秒', 'intense'],
            ['讓對方選一個部位，任他親', 'intense'],
            ['從對方的脖子一路親到鎖骨', 'intense'],
            ['脫掉對方一件外層衣物', 'intense'],
            ['在耳邊說出現在最想做的事', 'intense'],
            ['和對方到隱蔽處獨處 1 分鐘', 'intense'],
            ['咬住對方耳垂、同時愛撫他的腰', 'intense'],
            ['蒙住對方的眼，在他身上落下三個吻', 'intense'],
            ['在對方大腿內側愛撫 15 秒', 'intense'],
            ['用冰塊沿著對方鎖骨慢慢滑', 'intense'],
            ['對對方跳一段最撩人的舞', 'intense'],
            ['讓對方在你身上任選一處，留下一個吻痕', 'intense'],
        ];

        foreach ($segments as [$content, $tier]) {
            WheelSegment::firstOrCreate(
                ['content' => $content, 'tier' => $tier]
            );
        }
    }
}
