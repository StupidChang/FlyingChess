<?php

namespace Tests\Feature;

use App\Http\Middleware\AgeVerification;
use App\Models\User;
use App\Models\WheelSegment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 後台各處的「強度」都用同一組顏色。
 *
 * 這裡驗的是有樣式的 class,不是畫面上有沒有出現「輕鬆／親密／大膽」——
 * 轉盤後台原本輸出的是 .badge-mild / .badge-medium / .badge-intense,
 * 那三個 class 從來沒有對應的 CSS,所以「有出現文字」的斷言會過,
 * 但強度欄實際上是一片沒有顏色的純文字。
 */
class AdminTierBadgeTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_wheel_admin_colour_codes_each_tier(): void
    {
        $this->withoutMiddleware(AgeVerification::class);
        $admin = User::factory()->create(['is_admin' => true]);

        foreach (['mild', 'medium', 'intense'] as $tier) {
            WheelSegment::create(['tier' => $tier, 'content' => "強度 {$tier} 的任務"]);
        }

        $response = $this->actingAs($admin)->get('/tw/admin/wheel-segments');

        $response->assertOk()
            ->assertSee('badge-tier--mild')
            ->assertSee('badge-tier--medium')
            ->assertSee('badge-tier--intense');
    }

    public function test_every_tier_colour_is_actually_defined_in_the_stylesheet(): void
    {
        // 標籤輸出的 class 與樣式表對不上,正是原本那個 bug。
        $css = file_get_contents(public_path('css/app.css'));

        foreach (['mild', 'medium', 'intense', 'neutral'] as $level) {
            $this->assertStringContainsString(".badge-tier--{$level}{", $css);
        }
    }
}
