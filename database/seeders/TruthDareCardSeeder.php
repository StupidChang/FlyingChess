<?php

namespace Database\Seeders;

use App\Models\TruthDareCard;
use Illuminate\Database\Seeder;

class TruthDareCardSeeder extends Seeder
{
    public function run(): void
    {
        $cards = [
            // ── Truth (真心話) — Free ──
            ['truth', '你最近一次說謊是什麼時候？說了什麼？', 'free'],
            ['truth', '你覺得在場誰最有魅力？', 'free'],
            ['truth', '你最害羞的一次經歷是什麼？', 'free'],
            ['truth', '如果可以和任何人交換人生一天，你選誰？', 'free'],
            ['truth', '你最不想被別人知道的習慣是什麼？', 'free'],
            ['truth', '你覺得自己最大的優點和缺點分別是什麼？', 'free'],
            ['truth', '你做過最瘋狂的事是什麼？', 'free'],
            ['truth', '你最近一次偷偷做的事是什麼？', 'free'],
            ['truth', '如果明天就是世界末日，你今天要做什麼？', 'free'],
            ['truth', '你最喜歡自己身體的哪個部位？', 'free'],

            // ── Truth — Premium ──
            ['truth', '你最近一次對另一半有什麼不滿但沒說出口？', 'premium'],
            ['truth', '你覺得你們之間最需要改善的是什麼？', 'premium'],
            ['truth', '你曾經偷看過另一半的手機嗎？', 'premium'],
            ['truth', '你最想嘗試的親密互動方式是什麼？', 'premium'],
            ['truth', '你覺得另一半最吸引你的地方是哪裡？', 'premium'],

            // ── Dare (大冒險) — Free ──
            ['dare', '模仿一種動物叫聲 30 秒', 'free'],
            ['dare', '打電話給通訊錄第三個人，說「我想你了」', 'free'],
            ['dare', '用左手寫下在場每個人的名字', 'free'],
            ['dare', '做 10 個深蹲', 'free'],
            ['dare', '閉眼轉三圈然後走直線', 'free'],
            ['dare', '用搞笑的聲音讀一段新聞', 'free'],
            ['dare', '學右邊的人說一段話', 'free'],
            ['dare', '把手機螢幕截圖分享給大家看', 'free'],
            ['dare', '跟左邊的人握手 30 秒不能鬆開', 'free'],
            ['dare', '用腳比出一個數字讓大家猜', 'free'],

            // ── Dare — Premium ──
            ['dare', '給另一半一個 10 秒的法式熱吻', 'premium'],
            ['dare', '用最撩人的語氣念出一段指定的話', 'premium'],
            ['dare', '幫另一半按摩肩膀 1 分鐘', 'premium'],
            ['dare', '對另一半耳語一句最甜蜜的情話', 'premium'],
            ['dare', '和另一半十指交扣凝視對方 30 秒', 'premium'],

            // ── Couple (情侶題) — Free ──
            ['couple', '說出你們第一次約會的細節', 'free'],
            ['couple', '模仿對方說話的樣子', 'free'],
            ['couple', '說出三件你感謝對方的事', 'free'],
            ['couple', '回憶你們最開心的一次旅行', 'free'],
            ['couple', '說出你最喜歡和對方一起做的事', 'free'],
            ['couple', '猜對方現在最想吃什麼', 'free'],
            ['couple', '用一個詞形容對方給你的感覺', 'free'],
            ['couple', '說出你們在一起後你改變最大的一個習慣', 'free'],
            ['couple', '猜對方手機裡最常用的 App', 'free'],
            ['couple', '說出你第一次見到對方的印象', 'free'],

            // ── Couple — Premium ──
            ['couple', '互相按摩對方最緊張的部位 2 分鐘', 'premium'],
            ['couple', '現在立刻擁抱對方 30 秒不說話', 'premium'],
            ['couple', '說出你最想和對方嘗試的約會行程', 'premium'],
            ['couple', '親吻對方的手背，說一句甜蜜的話', 'premium'],
            ['couple', '描述你理想中和對方的未來生活', 'premium'],

            // ── Party (派對題) — Free ──
            ['party', '所有人一起乾杯！', 'free'],
            ['party', '讓右邊的人決定你要做什麼', 'free'],
            ['party', '模仿一個名人，大家猜是誰', 'free'],
            ['party', '用一分鐘說服大家你是外星人', 'free'],
            ['party', '所有人比醜臉，最好看的那個喝一口', 'free'],
            ['party', '站起來跳 15 秒的舞', 'free'],
            ['party', '用唱歌的方式說出你接下來想做的事', 'free'],
            ['party', '和在場最遠的人交換位置', 'free'],
            ['party', '所有人石頭剪刀布，輸的人做一件事', 'free'],
            ['party', '用一句話惹怒右邊的人（不能真的罵）', 'free'],

            // ── Party — Premium ──
            ['party', '被指定的人要把飲料一口喝完', 'premium'],
            ['party', '用 20 秒講完一個冷笑話，沒人笑就自罰', 'premium'],
            ['party', '在場所有人輪流說你的優點，你只能回答謝謝', 'premium'],
            ['party', '模仿在場指定一個人走路的方式', 'premium'],
            ['party', '閉眼讓大家在你臉上畫一筆（用手指）', 'premium'],
        ];

        foreach ($cards as [$category, $content, $tier]) {
            TruthDareCard::firstOrCreate(
                ['category' => $category, 'content' => $content],
                ['tier' => $tier]
            );
        }
    }
}
