<?php

namespace Database\Seeders;

use App\Models\WheelSegment;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WheelSegmentSeeder extends Seeder
{
    public function run(): void
    {
        $segments = [
            // 暖身：口語、曖昧，但不會一開始就太刺激。
            ['靠過去，在對方耳邊說：「你今天真的很可以。」', 'mild'],
            ['盯著對方放電 15 秒，誰先笑誰輸', 'mild'],
            ['牽起對方的手，從手背慢慢親到手腕', 'mild'],
            ['講一句你平常不好意思說的撩人話', 'mild'],
            ['從背後抱緊對方，貼著 20 秒再放開', 'mild'],
            ['捏住對方下巴，停在快親到的位置 10 秒', 'mild'],
            ['挑一個地方，讓對方親一下', 'mild'],
            ['摟著對方慢慢晃 30 秒', 'mild'],
            ['幫對方按摩肩頸 30 秒，不准隨便敷衍', 'mild'],
            ['用鼻尖蹭對方的臉，最後親一下臉頰', 'mild'],
            ['靠在對方胸口，安靜聽 20 秒心跳', 'mild'],
            ['說出對方今天最好看的地方', 'mild'],
            ['十指交扣、額頭貼著額頭 20 秒', 'mild'],
            ['對著對方耳朵呼一口氣，再叫一次他的名字', 'mild'],
            ['用手指在對方手心寫一句話，讓他猜', 'mild'],
            ['選一首歌，抱著對方慢慢搖到副歌結束', 'mild'],

            // 升溫：接吻、衣物內觸碰與更直接的挑逗。
            ['親對方脖子 15 秒，位置讓對方選', 'medium'],
            ['坐到對方腿上，面對面貼著 30 秒', 'medium'],
            ['含住對方耳垂，再問一句：「這樣喜歡嗎？」', 'medium'],
            ['貼著耳朵講一件今晚真的想做的事', 'medium'],
            ['含住對方一根手指 5 秒', 'medium'],
            ['把對方輕輕壓在牆邊，深吻 20 秒', 'medium'],
            ['從鎖骨一路親到胸口，衣服內外都可以', 'medium'],
            ['沿著大腿摸到內側，停在對方說可以的位置', 'medium'],
            ['幫對方脫掉一件外層衣物', 'medium'],
            ['隔著衣服慢慢摸對方胸口 30 秒', 'medium'],
            ['坐到對方身上，用身體貼著磨蹭 20 秒', 'medium'],
            ['讓對方把手伸進衣襬，貼著腰摸 20 秒', 'medium'],
            ['在對方身上留一個雙方都OK的吻痕', 'medium'],
            ['不准用手，接吻 30 秒', 'medium'],
            ['說出自己最敏感的位置，讓對方隔著衣服摸 20 秒', 'medium'],
            ['從背後貼緊對方，手放在腰或屁股 30 秒', 'medium'],

            // 火辣：尺度對齊成人棋盤；任何一方不想做都可換題。
            ['把對方壓到床上或沙發上，深吻 1 分鐘', 'intense'],
            ['跨坐在對方腿上，照喜歡的節奏磨蹭 30 秒', 'intense'],
            ['伸進衣服裡，摸一個對方同意的私密部位 30 秒', 'intense'],
            ['兩個人各脫一件衣物，再抱緊親吻 30 秒', 'intense'],
            ['指定一個私密部位，讓對方親 30 秒', 'intense'],
            ['用手幫對方刺激 1 分鐘，力道由對方決定', 'intense'],
            ['幫對方口交 1 分鐘；不想做就換同等級任務', 'intense'],
            ['挑一個雙方都想試的體位，維持或模擬 1 分鐘', 'intense'],
            ['蒙住對方眼睛，在身上親五個不同位置', 'intense'],
            ['幫對方脫到只剩雙方都舒服的程度', 'intense'],
            ['用嘴或手挑逗對方最敏感的位置 45 秒', 'intense'],
            ['輪流講一句指令，對方照做，共 2 分鐘', 'intense'],
            ['趴好，讓對方從背後貼緊互動 1 分鐘', 'intense'],
            ['挑一個情趣道具；沒有就用冰塊或絲巾替代', 'intense'],
            ['讓對方決定前戲怎麼進行，照做 2 分鐘', 'intense'],
            ['講清楚下一步想做什麼，選一件兩個人都說OK的立刻做', 'intense'],
            ['坐到對方臉上磨蹭 30 秒，姿勢以舒服為主', 'intense'],
            ['跪在對方面前，用嘴隔著內褲挑逗 30 秒', 'intense'],
            ['脫掉對方內褲，再親大腿內側 45 秒', 'intense'],
            ['用潤滑液幫對方按摩私密處 1 分鐘', 'intense'],
            ['讓對方選：口交 1 分鐘，或手玩 2 分鐘', 'intense'],
            ['示範自己最喜歡被摸的方式 45 秒', 'intense'],
            ['選一個最想重現的成人姿勢，模擬 1 分鐘', 'intense'],
            ['用舌頭挑逗對方乳頭或其他敏感點 45 秒', 'intense'],
            ['戴上眼罩，讓對方決定接下來三個觸碰位置', 'intense'],
            ['從背後抱住對方，用手刺激 1 分鐘', 'intense'],
            ['把對方的手帶到自己現在最想被碰的位置', 'intense'],
            ['互相描述最想做的前戲，選一個立刻開始', 'intense'],
            ['挑一個安全的束縛方式，控制對方 2 分鐘', 'intense'],
            ['用嘴含住對方最敏感的位置 30 秒', 'intense'],
            ['一個人決定快慢，另一個人決定深淺，互動 1 分鐘', 'intense'],
            ['各說一個界線和一個想玩的項目，選共同項目進行', 'intense'],
            ['挑一個你們都想玩的體位，直接試 2 分鐘', 'intense'],
            ['選前面還是後面，進去後跟著對方的節奏來 1 分鐘', 'intense'],
            ['用手指幫對方，進去後快慢都聽對方的 1 分鐘', 'intense'],
            ['幫對方口交，做到對方喊停或 2 分鐘', 'intense'],
            ['挑一樣情趣玩具，玩在彼此都說可以的地方 2 分鐘', 'intense'],
            ['戴好保險套，挑個兩人都想玩的體位來 3 分鐘', 'intense'],
            ['一個人選姿勢，另一個人說要多快、多深', 'intense'],
            ['先玩一輪都說好的前戲，還想繼續就自己決定', 'intense'],
        ];

        DB::transaction(function () use ($segments): void {
            // This table is the system-managed wheel library. Replace it as a
            // whole so retired formal wording cannot continue to be drawn.
            WheelSegment::query()->delete();

            foreach ($segments as [$content, $tier]) {
                WheelSegment::create(compact('content', 'tier'));
            }
        });
    }
}
