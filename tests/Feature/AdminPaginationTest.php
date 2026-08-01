<?php

namespace Tests\Feature;

use App\Http\Middleware\AgeVerification;
use App\Models\TruthDareCard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPaginationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_show_all_rows_and_keep_filters(): void
    {
        $this->withoutMiddleware(AgeVerification::class);
        $admin = User::factory()->create(['is_admin' => true]);

        foreach (range(1, 55) as $number) {
            TruthDareCard::create([
                'category' => 'truth',
                'content' => "測試題目 {$number}",
                'tier' => 'premium',
            ]);
        }

        $response = $this->actingAs($admin)
            ->withCookie('age_verified', '1')
            ->get('/tw/admin/cards?tier=premium&per_page=all');

        $response->assertOk()
            ->assertViewHas('cards', fn ($cards) => $cards->count() === 55)
            ->assertSee('每頁顯示')
            ->assertSee('value="all" selected', false)
            ->assertSee('name="tier" value="premium"', false);
    }

    public function test_invalid_page_size_falls_back_to_twenty(): void
    {
        $this->withoutMiddleware(AgeVerification::class);
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)
            ->withCookie('age_verified', '1')
            ->get('/tw/admin/cards?per_page=999999');

        $response->assertOk()
            ->assertViewHas('cards', fn ($cards) => $cards->perPage() === 20);
    }

    public function test_numeric_page_size_stays_selected_after_submission(): void
    {
        $this->withoutMiddleware(AgeVerification::class);
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)
            ->withCookie('age_verified', '1')
            ->get('/tw/admin/cards?per_page=50');

        $response->assertOk()
            ->assertViewHas('cards', fn ($cards) => $cards->perPage() === 50)
            ->assertSee('value="50" selected', false);
    }
}
