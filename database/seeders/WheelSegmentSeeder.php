<?php

namespace Database\Seeders;

use App\Models\WheelSegment;
use Illuminate\Database\Seeder;

class WheelSegmentSeeder extends Seeder
{
    public function run(): void
    {
        $segments = [
            // ── Mild（輕鬆互動）── 16 個
            ['互相凝視 10 秒', 'mild'],
            ['說出對方的優點', 'mild'],
            ['模仿對方動作', 'mild'],
            ['牽手轉一圈', 'mild'],
            ['互相比心', 'mild'],
            ['用一個詞形容對方', 'mild'],
            ['對方做鬼臉', 'mild'],
            ['幫對方按手', 'mild'],
            ['用搞笑語氣介紹對方', 'mild'],
            ['互相說一個小秘密', 'mild'],
            ['幫對方整理頭髮', 'mild'],
            ['用眼神傳達一句話讓對方猜', 'mild'],
            ['面對面做出相同表情', 'mild'],
            ['說出你們最好笑的回憶', 'mild'],
            ['互相幫對方拍一張好看的照片', 'mild'],
            ['用手指在對方手心寫字猜猜看', 'mild'],

            // ── Medium（親密互動）── 16 個
            ['親對方臉頰', 'medium'],
            ['擁抱 10 秒', 'medium'],
            ['按摩肩膀 30 秒', 'medium'],
            ['耳邊說悄悄話', 'medium'],
            ['摸對方的頭', 'medium'],
            ['從背後擁抱', 'medium'],
            ['互餵吃東西', 'medium'],
            ['面對面跳舞', 'medium'],
            ['牽手散步一圈回來', 'medium'],
            ['額頭碰額頭 10 秒', 'medium'],
            ['幫對方捏臉 10 秒', 'medium'],
            ['鼻碰鼻凝視 5 秒', 'medium'],
            ['把頭靠在對方肩上 15 秒', 'medium'],
            ['公主抱對方（或嘗試）', 'medium'],
            ['用嘴接住對方餵的食物', 'medium'],
            ['幫對方吹頭髮或梳頭', 'medium'],

            // ── Intense（大膽挑戰）── 14 個
            ['親嘴 5 秒', 'intense'],
            ['坐在對方腿上', 'intense'],
            ['讓對方選部位親', 'intense'],
            ['脫一件外層衣物', 'intense'],
            ['從脖子親到鎖骨', 'intense'],
            ['耳邊說想做的事', 'intense'],
            ['獨處 1 分鐘', 'intense'],
            ['對方可以要求一件事', 'intense'],
            ['咬住對方耳垂 3 秒', 'intense'],
            ['替對方塗護唇膏但不能用手', 'intense'],
            ['蒙眼讓對方帶你走一段路', 'intense'],
            ['用最撩的語氣念一段歌詞', 'intense'],
            ['讓對方在你身上畫一個圖案', 'intense'],
            ['互相說出最想和對方嘗試的事', 'intense'],
        ];

        foreach ($segments as [$content, $tier]) {
            WheelSegment::firstOrCreate(
                ['content' => $content, 'tier' => $tier]
            );
        }
    }
}
