<?php

namespace Tests\Feature;

use App\Http\Middleware\AgeVerification;
use App\Models\TruthDareCard;
use App\Models\User;
use App\Models\WheelSegment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 編輯完要回到原本停的地方。
 *
 * 列表頁的網址帶著一堆狀態(第 3 頁、只看重度、依尺度排序…),點進去改一筆
 * 再回來如果全部歸零,等於每改一題就要重找一次。
 */
class AdminReturnToListTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $this->withoutMiddleware(AgeVerification::class);

        return User::factory()->create(['is_admin' => true]);
    }

    public function test_the_edit_link_carries_where_you_were(): void
    {
        TruthDareCard::create(['category' => 'truth', 'audience' => 'both', 'content' => '一題', 'level' => 'mild']);

        $this->actingAs($this->admin())
            ->get('/tw/admin/cards?level=mild&sort=content&dir=asc&page=1')
            ->assertOk()
            ->assertSee('return=', false)
            ->assertSee('level%3Dmild', false);
    }

    public function test_saving_goes_back_to_the_same_page_and_filter(): void
    {
        $card = TruthDareCard::create([
            'category' => 'truth', 'audience' => 'both', 'content' => '一題', 'level' => 'mild',
        ]);

        $this->actingAs($this->admin())
            ->patch("/tw/admin/cards/{$card->id}", [
                'category' => 'truth',
                'audience' => 'both',
                'level' => 'mild',
                'content' => '改過了',
                'return' => 'level=mild&sort=content&dir=asc&page=3',
            ])
            ->assertRedirectContains('level=mild')
            ->assertRedirectContains('page=3')
            ->assertRedirectContains('sort=content');

        $this->assertSame('改過了', $card->fresh()->content);
    }

    public function test_deleting_stays_on_the_same_page(): void
    {
        $segment = WheelSegment::create(['tier' => 'mild', 'content' => '任務']);

        // 刪一筆之後被丟回第 1 頁,跟改一筆之後被丟回第 1 頁一樣煩。
        $this->actingAs($this->admin())
            ->delete("/tw/admin/wheel-segments/{$segment->id}", ['return' => 'tier=mild&page=2'])
            ->assertRedirectContains('tier=mild')
            ->assertRedirectContains('page=2');
    }

    public function test_the_cancel_button_goes_back_too(): void
    {
        $segment = WheelSegment::create(['tier' => 'mild', 'content' => '任務']);

        $this->actingAs($this->admin())
            ->get("/tw/admin/wheel-segments/{$segment->id}/edit?return=".urlencode('tier=mild&page=2'))
            ->assertOk()
            ->assertSee('tier=mild', false)
            ->assertSee('page=2', false);
    }

    public function test_a_return_value_cannot_send_the_admin_off_site(): void
    {
        $segment = WheelSegment::create(['tier' => 'mild', 'content' => '任務']);

        /* return 是使用者給的字串。如果直接拿它當轉址目標,那就是一個開放轉址 ——
           它只能是 query string,而且要交回 route() 重組。 */
        $response = $this->actingAs($this->admin())
            ->delete("/tw/admin/wheel-segments/{$segment->id}", [
                'return' => 'https://evil.example.com',
            ]);

        $this->assertStringStartsWith(config('app.url'), $response->headers->get('Location'));
    }

    public function test_no_return_value_still_works(): void
    {
        $segment = WheelSegment::create(['tier' => 'mild', 'content' => '任務']);

        // 直接打網址進來編輯的情況,沒有 return 也不能壞掉。
        $this->actingAs($this->admin())
            ->get("/tw/admin/wheel-segments/{$segment->id}/edit")
            ->assertOk();

        $this->actingAs($this->admin())
            ->delete("/tw/admin/wheel-segments/{$segment->id}")
            ->assertRedirect();
    }
}
