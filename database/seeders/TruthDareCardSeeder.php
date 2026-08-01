<?php

namespace Database\Seeders;

use App\Models\TruthDareCard;
use Illuminate\Database\Seeder;

class TruthDareCardSeeder extends Seeder
{
    public function run(): void
    {
        /* 每一列是 [類型, 適用人數, 內容, 尺度]。
           類型是真心話／大冒險,人數是情侶／多人／通用 —— 兩個軸分開,
           不然多人場會抽到指名「另一半」的題目。尺度是輕度／中度／重度,
           整站都是成人向,分的是輕重而不是「一般／18禁」。 */
        $cards = [
            // ── 真心話 — 輕度──
            ['truth', 'couple', '你第一次對另一半動心，是被哪個身體部位吸引？', 'mild'],
            ['truth', 'both', '你最近一次想入非非，是在想誰？', 'mild'],
            ['truth', 'both', '你被撩到過最有感覺的一句話是什麼？', 'mild'],
            ['truth', 'both', '你身上最希望被親吻的部位是哪裡？', 'mild'],
            ['truth', 'both', '你談過最刺激的一段感情發生過什麼？', 'mild'],
            ['truth', 'both', '你最容易在什麼情境下被挑起慾望？', 'mild'],
            ['truth', 'both', '你談戀愛時最主動的一次做了什麼？', 'mild'],
            ['truth', 'couple', '你偷偷幻想過在哪個地方和另一半親熱？', 'mild'],
            ['truth', 'both', '你覺得自己身上哪裡最性感？', 'mild'],
            ['truth', 'couple', '你喜歡主導，還是被對方主導？', 'mild'],

            // ── 真心話 — 中度／重度 ──
            ['truth', 'couple', '你對另一半最私密的幻想是什麼？', 'intense'],
            ['truth', 'both', '你最敏感的身體部位在哪裡？', 'medium'],
            ['truth', 'couple', '你曾經在什麼意想不到的地方和另一半親熱過？', 'medium'],
            ['truth', 'both', '你覺得你們之間最火辣的一次經驗是什麼？', 'medium'],
            ['truth', 'both', '你最想嘗試但還沒開口的情趣玩法是什麼？', 'intense'],
            ['truth', 'both', '你有什麼穿著打扮特別容易被撩到？', 'medium'],
            ['truth', 'both', '你對角色扮演有興趣嗎？最想扮演什麼？', 'medium'],
            ['truth', 'couple', '你覺得另一半做什麼動作最性感？', 'medium'],
            ['truth', 'both', '你最喜歡哪一種體位？為什麼？', 'intense'],
            ['truth', 'both', '你更喜歡口交、手玩還是插入？', 'intense'],
            ['truth', 'both', '你最想嘗試哪一種情趣玩具？', 'intense'],
            ['truth', 'both', '你敢不敢試肛交或後庭玩具？', 'intense'],
            ['truth', 'both', '你喜歡多快、多用力、多深？', 'medium'],
            ['truth', 'both', '你最想成真的性愛幻想是什麼？', 'intense'],

            // ── 大冒險・情侶 — 輕度──
            ['dare', 'couple', '在另一半耳邊吹一口氣，再說一句最撩的話', 'mild'],
            ['dare', 'couple', '用最性感的眼神盯著另一半 15 秒不能笑', 'mild'],
            ['dare', 'couple', '親另一半的手背，一路往上親到手肘', 'mild'],
            ['dare', 'couple', '從背後環抱另一半，下巴靠在他肩上 15 秒', 'mild'],
            ['dare', 'couple', '用指尖在另一半的手心慢慢畫圈 20 秒', 'mild'],
            ['dare', 'couple', '貼著另一半的耳朵，低聲說你今晚想做什麼', 'mild'],
            ['dare', 'couple', '含住另一半的一根手指 3 秒', 'mild'],
            ['dare', 'couple', '把另一半輕輕壓向牆或沙發，對視 10 秒', 'mild'],
            ['dare', 'couple', '對另一半跳 15 秒撩人的舞', 'mild'],
            ['dare', 'couple', '用嘴唇輕輕蹭過另一半的下巴到耳側', 'mild'],

            // ── 大冒險・情侶 — 中度／重度 ──
            ['dare', 'couple', '給另一半一個持續 30 秒的深吻', 'medium'],
            ['dare', 'couple', '用嘴唇從對方的脖子慢慢親到耳後', 'medium'],
            ['dare', 'couple', '幫另一半按摩大腿內側 1 分鐘', 'medium'],
            ['dare', 'couple', '用最撩人的語氣在對方耳邊說出你想對他做的事', 'medium'],
            ['dare', 'couple', '蒙住眼睛，讓另一半用手指在你身上畫字，猜出內容', 'medium'],
            ['dare', 'couple', '用冰塊沿著對方的鎖骨慢慢滑動', 'medium'],
            ['dare', 'couple', '選一首歌，對另一半跳一段性感的舞', 'medium'],
            ['dare', 'couple', '幫另一半脫掉一件衣物（外套、襪子等皆可）', 'intense'],
            ['dare', 'couple', '幫另一半口交 1 分鐘', 'intense'],
            ['dare', 'couple', '用手刺激另一半的私密處 1 分鐘', 'intense'],
            ['dare', 'couple', '挑一個你們都想玩的體位，直接試 2 分鐘', 'intense'],
            ['dare', 'couple', '用手指幫另一半，進去後快慢都聽對方的', 'medium'],
            ['dare', 'couple', '挑一樣情趣玩具，陪另一半玩 1 分鐘', 'intense'],
            ['dare', 'couple', '從後面進入另一半，照對方喜歡的節奏來', 'medium'],

            // ── 真心話・情侶 — 輕度──
            ['truth', 'couple', '說出你們第一次親熱時最難忘的細節', 'mild'],
            ['truth', 'couple', '互相指出對方身上最讓你心癢的部位', 'mild'],
            ['truth', 'couple', '說出你最想在對方身上多花時間的地方', 'mild'],
            ['truth', 'couple', '回憶你們最激情的一次是在哪裡', 'mild'],
            ['truth', 'couple', '說出你最想和對方一起嘗試的親密玩法', 'mild'],
            ['truth', 'couple', '告訴對方，他做哪個動作最挑起你', 'mild'],
            ['truth', 'couple', '用一句最露骨的話形容此刻對對方的渴望', 'mild'],
            ['truth', 'couple', '說出你最想被對方怎麼撩', 'mild'],
            ['truth', 'couple', '互相說出對方最性感的一個習慣', 'mild'],
            ['truth', 'couple', '說出你第一次對對方產生慾望的瞬間', 'mild'],

            // ── 真心話・情侶 — 中度／重度 ──
            ['truth', 'couple', '互相按摩對方身上最敏感的部位 2 分鐘', 'medium'],
            ['truth', 'couple', '從背後環抱對方，在耳邊低語你最想做的事', 'medium'],
            ['truth', 'couple', '和對方玩「主人與僕人」遊戲 3 分鐘', 'medium'],
            ['truth', 'couple', '用嘴巴從對方的手指尖親到手腕', 'medium'],
            ['truth', 'couple', '替對方塗上護唇膏——但不能用手', 'medium'],
            ['truth', 'couple', '和對方面對面坐在腿上，凝視 1 分鐘不能笑', 'medium'],
            ['truth', 'couple', '說出你最喜歡對方在親密時的一個小動作', 'medium'],
            ['truth', 'couple', '用身體語言向對方表達你現在想做什麼，不能說話', 'medium'],
            ['truth', 'couple', '互相口交或輪流服務對方各 1 分鐘', 'intense'],
            ['truth', 'couple', '挑一個最想玩的體位，直接試 2 分鐘', 'intense'],
            ['truth', 'couple', '一個人選姿勢，另一個人說要多快、多深', 'medium'],
            ['truth', 'couple', '挑一樣情趣玩具，輪流陪對方玩', 'intense'],
            ['truth', 'couple', '各講一個不行、一個想玩，再挑共同的直接做', 'medium'],

            // ── 大冒險・多人 — 輕度──
            ['dare', 'party', '讓右邊的人在你耳邊說一句最撩的話', 'mild'],
            ['dare', 'party', '對在場你覺得最性感的人放電 10 秒', 'mild'],
            ['dare', 'party', '用最色氣的方式吃掉一口食物給大家看', 'mild'],
            ['dare', 'party', '和左邊的人玩 15 秒 Pocky Game', 'mild'],
            ['dare', 'party', '用身體擺一個你自認最性感的姿勢 10 秒', 'mild'],
            ['dare', 'party', '對指定的人跳一段撩人的舞', 'mild'],
            ['dare', 'party', '說出在場你最想壁咚的人', 'mild'],
            ['dare', 'party', '和右邊的人十指交扣、對視 20 秒', 'mild'],
            ['dare', 'party', '用最誘惑的語氣念出下一題', 'mild'],
            ['dare', 'party', '讓大家票選你最性感的部位，展示 10 秒', 'mild'],

            // ── 大冒險・多人 — 中度／重度 ──
            ['dare', 'party', '被指定的人要把飲料一口喝完，喝不完就脫一件', 'intense'],
            ['dare', 'party', '和指定的人玩 30 秒 Pocky Game', 'medium'],
            ['dare', 'party', '由大家票選你最性感的身體部位，你要展示 10 秒', 'medium'],
            ['dare', 'party', '用最色氣的方式吃掉一根香蕉', 'medium'],
            ['dare', 'party', '讓指定的人在你身上任選一個部位親一下', 'medium'],
            ['dare', 'party', '和左邊的人身體貼緊維持 30 秒', 'medium'],
            ['dare', 'party', '模仿一段浮誇的撒嬌，讓全場投票過不過關', 'medium'],
            ['dare', 'party', '輸的人要做 5 下性感深蹲，其他人打分數', 'medium'],
            ['dare', 'party', '說出最喜歡的體位並用動作示範姿勢', 'intense'],
            ['dare', 'party', '抽一人回答最想嘗試的成人玩法', 'medium'],
            ['dare', 'party', '讓指定的人隔著衣物撫摸私密處 20 秒', 'intense'],
            ['dare', 'party', '展示最喜歡的情趣道具，沒有就描述用途', 'intense'],
            // ── 真心話・多人 — 輕度──
            ['truth', 'party', '在場的人裡，你最想跟誰交換一天的身體？', 'mild'],
            ['truth', 'party', '你被搭訕過最誇張的一次是什麼情況？', 'mild'],
            ['truth', 'party', '你最容易被哪一種人吸引？指出在場最接近的一位', 'mild'],
            ['truth', 'party', '你談過最短的一段感情維持多久，為什麼結束？', 'mild'],
            ['truth', 'party', '你手機裡有沒有不敢給在場任何人看的照片？', 'mild'],
            ['truth', 'party', '你最想收到哪一種告白方式？', 'mild'],
            ['truth', 'party', '你曾經對朋友喜歡的人動過心嗎？', 'mild'],
            ['truth', 'party', '你最引以為傲的身體部位是哪裡？', 'mild'],
            ['truth', 'party', '你在公共場合做過最大膽的事是什麼？', 'mild'],
            ['truth', 'party', '你的理想型跟你實際喜歡過的人差多少？', 'mild'],
            ['truth', 'party', '在場誰最有可能劈腿？說出名字並解釋', 'mild'],
            ['truth', 'party', '你曾經半夜偷看過誰的社群帳號到幾點？', 'mild'],

            // ── 真心話・多人 — 中度／重度 ──
            ['truth', 'party', '你最近一次自己解決是什麼時候？', 'intense'],
            ['truth', 'party', '你有過幾個對象？先讓大家猜再公布', 'medium'],
            ['truth', 'party', '你最刺激的一次是在什麼地方發生的？', 'medium'],
            ['truth', 'party', '你有沒有被別人聽到過聲音？當下怎麼收場', 'medium'],
            ['truth', 'party', '你最想嘗試的地點是哪裡？', 'medium'],
            ['truth', 'party', '你偏好主導還是被主導？說出原因', 'medium'],
            ['truth', 'party', '你最敏感的部位在哪裡？只說不示範', 'medium'],
            ['truth', 'party', '你玩過情趣道具嗎？哪一種', 'intense'],
            ['truth', 'party', '你一個晚上最多幾次？', 'intense'],
            ['truth', 'party', '你會不會在鏡子前面看？', 'medium'],
            ['truth', 'party', '你最想試的角色扮演是什麼？', 'medium'],
            ['truth', 'party', '你傳過裸露的照片給別人嗎？後來呢', 'intense'],
            ['truth', 'party', '在場的人裡，你覺得誰的床上表現最讓你好奇？', 'medium'],
            ['truth', 'party', '你有沒有跟朋友聊過彼此的性事？聊到多細', 'medium'],

            // ── 大冒險・多人 — 補充 ──
            ['dare', 'party', '讓右邊的人指定一個部位，你用冰塊在那裡停 10 秒', 'medium'],
            ['dare', 'party', '選一個人，隔空模仿你最喜歡的接吻方式 15 秒', 'medium'],
            ['dare', 'party', '用最色氣的語氣念出在場某人的名字五次', 'medium'],
            ['dare', 'party', '讓大家指定，你和其中一人維持對視到有人先笑', 'medium'],
            ['dare', 'party', '脫掉一件不影響見人的衣物，撐到這輪結束', 'intense'],
            ['dare', 'party', '讓左邊的人在你手臂上寫字，你要猜出寫了什麼', 'medium'],
        ];

        foreach ($cards as [$category, $audience, $content, $level]) {
            TruthDareCard::firstOrCreate(
                ['category' => $category, 'content' => $content],
                ['level' => $level, 'audience' => $audience]
            );
        }
    }
}
