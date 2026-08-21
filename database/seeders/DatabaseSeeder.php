<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            BoardSeeder::class,
            TruthDareCardSeeder::class,
            WheelSegmentSeeder::class,
            BoardTemplateSeeder::class,
            // 逐格復刻的原版飛行棋盤(格子照原圖轉錄,不是自己寫的內容)
            FlyingChessV8ReplicaSeeder::class,
            DevAccountSeeder::class,
        ]);
    }
}
