<?php

namespace Tests\Feature;

use App\Http\Middleware\AgeVerification;
use App\Models\GamePrompt;
use App\Models\TruthDareCard;
use App\Models\User;
use App\Models\WheelSegment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 後台可以一鍵複製一題。
 *
 * 用途是「照這一題再做一個變體」—— 同樣的內容換個限定對象或尺度。所以複製要
 * 連同各種分類欄位一起帶走,只複製內容的話等於還要重設一次。
 */
class AdminDuplicateTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $this->withoutMiddleware(AgeVerification::class);

        return User::factory()->create(['is_admin' => true]);
    }

    public function test_duplicating_a_card_copies_every_field(): void
    {
        $card = TruthDareCard::create([
            'category' => 'dare', 'audience' => 'party', 'gender' => 'female',
            'level' => 'medium_plus', 'is_paid' => true, 'content' => '原本這一題',
        ]);

        $this->actingAs($this->admin())
            ->post("/tw/admin/cards/{$card->id}/duplicate")
            ->assertRedirect();

        $this->assertSame(2, TruthDareCard::count());

        $copy = TruthDareCard::latest('id')->first();
        $this->assertNotSame($card->id, $copy->id);

        foreach (['category', 'audience', 'gender', 'level', 'is_paid', 'content'] as $field) {
            $this->assertSame($card->$field, $copy->$field, "{$field} 沒有跟著複製");
        }
    }

    public function test_it_opens_the_copy_for_editing_not_the_original(): void
    {
        $card = TruthDareCard::create([
            'category' => 'truth', 'audience' => 'both', 'gender' => 'any',
            'level' => 'mild', 'content' => '原本這一題',
        ]);

        /* 複製完留在列表的話,副本多半會排到別頁去(預設依建立時間排序),
           還要自己找。直接進副本的編輯頁才接得上下一步。 */
        $copyId = $card->id + 1;

        $this->actingAs($this->admin())
            ->post("/tw/admin/cards/{$card->id}/duplicate")
            ->assertRedirectContains("/admin/cards/{$copyId}/edit");
    }

    public function test_the_copy_keeps_where_you_were_in_the_list(): void
    {
        $card = TruthDareCard::create([
            'category' => 'truth', 'audience' => 'both', 'gender' => 'any',
            'level' => 'mild', 'content' => '一題',
        ]);

        $this->actingAs($this->admin())
            ->post("/tw/admin/cards/{$card->id}/duplicate", ['return' => 'level%5B0%5D=mild&page=2'])
            ->assertRedirectContains('return=');
    }

    public function test_prompts_and_wheel_segments_can_be_duplicated_too(): void
    {
        $prompt = GamePrompt::create([
            'game' => 'king_game', 'pool' => 'medium', 'content' => '一題', 'is_paid' => true, 'sort_order' => 7,
        ]);
        $segment = WheelSegment::create(['tier' => 'intense', 'content' => '一個任務', 'is_paid' => true]);

        $admin = $this->admin();

        $this->actingAs($admin)->post("/tw/admin/prompts/{$prompt->id}/duplicate")->assertRedirect();
        $this->actingAs($admin)->post("/tw/admin/wheel-segments/{$segment->id}/duplicate")->assertRedirect();

        $promptCopy = GamePrompt::latest('id')->first();
        $this->assertSame(['medium', true, 7], [$promptCopy->pool, $promptCopy->is_paid, $promptCopy->sort_order]);

        $segmentCopy = WheelSegment::latest('id')->first();
        $this->assertSame(['intense', true], [$segmentCopy->tier, $segmentCopy->is_paid]);
    }

    public function test_duplicating_is_admin_only(): void
    {
        // 沒關年齡閘的話,擋下來的是年齡確認頁(HTTP 200),不是 403。
        $this->withoutMiddleware(AgeVerification::class);

        $card = TruthDareCard::create([
            'category' => 'truth', 'audience' => 'both', 'gender' => 'any',
            'level' => 'mild', 'content' => '一題',
        ]);

        $this->actingAs(User::factory()->create(['is_admin' => false]))
            ->post("/tw/admin/cards/{$card->id}/duplicate")
            ->assertForbidden();

        $this->assertSame(1, TruthDareCard::count());
    }
}
