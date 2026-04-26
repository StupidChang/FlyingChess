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

            // ── Truth — Premium (18+) ──
            ['truth', '你對另一半最私密的幻想是什麼？', 'premium'],
            ['truth', '你最敏感的身體部位在哪裡？', 'premium'],
            ['truth', '你曾經在什麼意想不到的地方和另一半親熱過？', 'premium'],
            ['truth', '你覺得你們之間最火辣的一次經驗是什麼？', 'premium'],
            ['truth', '你最想嘗試但還沒開口的情趣玩法是什麼？', 'premium'],
            ['truth', '你有什麼穿著打扮特別容易被撩到？', 'premium'],
            ['truth', '你對角色扮演有興趣嗎？最想扮演什麼？', 'premium'],
            ['truth', '你覺得另一半做什麼動作最性感？', 'premium'],

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

            // ── Dare — Premium (18+) ──
            ['dare', '給另一半一個持續 30 秒的深吻', 'premium'],
            ['dare', '用嘴唇從對方的脖子慢慢親到耳後', 'premium'],
            ['dare', '幫另一半按摩大腿內側 1 分鐘', 'premium'],
            ['dare', '用最撩人的語氣在對方耳邊說出你想對他做的事', 'premium'],
            ['dare', '蒙住眼睛，讓另一半用手指在你身上畫字，猜出內容', 'premium'],
            ['dare', '用冰塊沿著對方的鎖骨慢慢滑動', 'premium'],
            ['dare', '選一首歌，對另一半跳一段性感的舞', 'premium'],
            ['dare', '幫另一半脫掉一件衣物（外套、襪子等皆可）', 'premium'],

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

            // ── Couple — Premium (18+) ──
            ['couple', '互相按摩對方身上最敏感的部位 2 分鐘', 'premium'],
            ['couple', '從背後環抱對方，在耳邊低語你最想做的事', 'premium'],
            ['couple', '和對方玩「主人與僕人」遊戲 3 分鐘', 'premium'],
            ['couple', '用嘴巴從對方的手指尖親到手腕', 'premium'],
            ['couple', '替對方塗上護唇膏——但不能用手', 'premium'],
            ['couple', '和對方面對面坐在腿上，凝視 1 分鐘不能笑', 'premium'],
            ['couple', '說出你最喜歡對方在親密時的一個小動作', 'premium'],
            ['couple', '用身體語言向對方表達你現在想做什麼，不能說話', 'premium'],

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
