<?php

namespace Database\Seeders;

use App\Models\TruthDareCard;
use Illuminate\Database\Seeder;

class TruthDareCardSeeder extends Seeder
{
    public function run(): void
    {
        $cards = [
            // ── Truth (真心話) — Free（成人向、曖昧級）──
            ['truth', '你第一次對另一半動心，是被哪個身體部位吸引？', 'free'],
            ['truth', '你最近一次想入非非，是在想誰？', 'free'],
            ['truth', '你被撩到過最有感覺的一句話是什麼？', 'free'],
            ['truth', '你身上最希望被親吻的部位是哪裡？', 'free'],
            ['truth', '你談過最刺激的一段感情發生過什麼？', 'free'],
            ['truth', '你最容易在什麼情境下被挑起慾望？', 'free'],
            ['truth', '你談戀愛時最主動的一次做了什麼？', 'free'],
            ['truth', '你偷偷幻想過在哪個地方和另一半親熱？', 'free'],
            ['truth', '你覺得自己身上哪裡最性感？', 'free'],
            ['truth', '你喜歡主導，還是被對方主導？', 'free'],

            // ── Truth — Premium (18+) ──
            ['truth', '你對另一半最私密的幻想是什麼？', 'premium'],
            ['truth', '你最敏感的身體部位在哪裡？', 'premium'],
            ['truth', '你曾經在什麼意想不到的地方和另一半親熱過？', 'premium'],
            ['truth', '你覺得你們之間最火辣的一次經驗是什麼？', 'premium'],
            ['truth', '你最想嘗試但還沒開口的情趣玩法是什麼？', 'premium'],
            ['truth', '你有什麼穿著打扮特別容易被撩到？', 'premium'],
            ['truth', '你對角色扮演有興趣嗎？最想扮演什麼？', 'premium'],
            ['truth', '你覺得另一半做什麼動作最性感？', 'premium'],

            // ── Dare (大冒險) — Free（成人向、曖昧級）──
            ['dare', '在另一半耳邊吹一口氣，再說一句最撩的話', 'free'],
            ['dare', '用最性感的眼神盯著另一半 15 秒不能笑', 'free'],
            ['dare', '親另一半的手背，一路往上親到手肘', 'free'],
            ['dare', '從背後環抱另一半，下巴靠在他肩上 15 秒', 'free'],
            ['dare', '用指尖在另一半的手心慢慢畫圈 20 秒', 'free'],
            ['dare', '貼著另一半的耳朵，低聲說你今晚想做什麼', 'free'],
            ['dare', '含住另一半的一根手指 3 秒', 'free'],
            ['dare', '把另一半輕輕壓向牆或沙發，對視 10 秒', 'free'],
            ['dare', '對另一半跳 15 秒撩人的舞', 'free'],
            ['dare', '用嘴唇輕輕蹭過另一半的下巴到耳側', 'free'],

            // ── Dare — Premium (18+) ──
            ['dare', '給另一半一個持續 30 秒的深吻', 'premium'],
            ['dare', '用嘴唇從對方的脖子慢慢親到耳後', 'premium'],
            ['dare', '幫另一半按摩大腿內側 1 分鐘', 'premium'],
            ['dare', '用最撩人的語氣在對方耳邊說出你想對他做的事', 'premium'],
            ['dare', '蒙住眼睛，讓另一半用手指在你身上畫字，猜出內容', 'premium'],
            ['dare', '用冰塊沿著對方的鎖骨慢慢滑動', 'premium'],
            ['dare', '選一首歌，對另一半跳一段性感的舞', 'premium'],
            ['dare', '幫另一半脫掉一件衣物（外套、襪子等皆可）', 'premium'],

            // ── Couple (情侶題) — Free（成人向、曖昧級）──
            ['couple', '說出你們第一次親熱時最難忘的細節', 'free'],
            ['couple', '互相指出對方身上最讓你心癢的部位', 'free'],
            ['couple', '說出你最想在對方身上多花時間的地方', 'free'],
            ['couple', '回憶你們最激情的一次是在哪裡', 'free'],
            ['couple', '說出你最想和對方一起嘗試的親密玩法', 'free'],
            ['couple', '告訴對方，他做哪個動作最挑起你', 'free'],
            ['couple', '用一句最露骨的話形容此刻對對方的渴望', 'free'],
            ['couple', '說出你最想被對方怎麼撩', 'free'],
            ['couple', '互相說出對方最性感的一個習慣', 'free'],
            ['couple', '說出你第一次對對方產生慾望的瞬間', 'free'],

            // ── Couple — Premium (18+) ──
            ['couple', '互相按摩對方身上最敏感的部位 2 分鐘', 'premium'],
            ['couple', '從背後環抱對方，在耳邊低語你最想做的事', 'premium'],
            ['couple', '和對方玩「主人與僕人」遊戲 3 分鐘', 'premium'],
            ['couple', '用嘴巴從對方的手指尖親到手腕', 'premium'],
            ['couple', '替對方塗上護唇膏——但不能用手', 'premium'],
            ['couple', '和對方面對面坐在腿上，凝視 1 分鐘不能笑', 'premium'],
            ['couple', '說出你最喜歡對方在親密時的一個小動作', 'premium'],
            ['couple', '用身體語言向對方表達你現在想做什麼，不能說話', 'premium'],

            // ── Party (派對題) — Free（成人向、曖昧級）──
            ['party', '讓右邊的人在你耳邊說一句最撩的話', 'free'],
            ['party', '對在場你覺得最性感的人放電 10 秒', 'free'],
            ['party', '用最色氣的方式吃掉一口食物給大家看', 'free'],
            ['party', '和左邊的人玩 15 秒 Pocky Game', 'free'],
            ['party', '用身體擺一個你自認最性感的姿勢 10 秒', 'free'],
            ['party', '對指定的人跳一段撩人的舞', 'free'],
            ['party', '說出在場你最想壁咚的人', 'free'],
            ['party', '和右邊的人十指交扣、對視 20 秒', 'free'],
            ['party', '用最誘惑的語氣念出下一題', 'free'],
            ['party', '讓大家票選你最性感的部位，展示 10 秒', 'free'],

            // ── Party — Premium (18+) ──
            ['party', '被指定的人要把飲料一口喝完，喝不完就脫一件', 'premium'],
            ['party', '和指定的人玩 30 秒 Pocky Game', 'premium'],
            ['party', '由大家票選你最性感的身體部位，你要展示 10 秒', 'premium'],
            ['party', '用最色氣的方式吃掉一根香蕉', 'premium'],
            ['party', '讓指定的人在你身上任選一個部位親一下', 'premium'],
            ['party', '和左邊的人身體貼緊維持 30 秒', 'premium'],
            ['party', '模仿一段浮誇的撒嬌，讓全場投票過不過關', 'premium'],
            ['party', '輸的人要做 5 下性感深蹲，其他人打分數', 'premium'],
        ];

        foreach ($cards as [$category, $content, $tier]) {
            TruthDareCard::firstOrCreate(
                ['category' => $category, 'content' => $content],
                ['tier' => $tier]
            );
        }
    }
}
