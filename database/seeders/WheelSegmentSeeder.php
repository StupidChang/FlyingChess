<?php

namespace Database\Seeders;

use App\Models\WheelSegment;
use App\Services\WheelGameService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WheelSegmentSeeder extends Seeder
{
    public function run(): void
    {
        /* 內容只有一份:WheelGameService::defaultPools()(資料表空的時候轉盤也退回它)。
           以前這裡另外抄了一份,兩邊慢慢改成不一樣 —— 2026-09-27 時 72 題裡有 25 題對不上。 */
        DB::transaction(function (): void {
            // This table is the system-managed wheel library. Replace it as a
            // whole so retired formal wording cannot continue to be drawn.
            WheelSegment::query()->delete();

            foreach (WheelGameService::defaultPools() as $tier => $items) {
                foreach ($items as $content) {
                    WheelSegment::create([
                        'content' => $content,
                        'tier' => $tier,
                        'is_paid' => WheelSegment::defaultIsPaid($tier),
                    ]);
                }
            }
        });
    }
}
