<?php

namespace Tests\Feature;

use App\Http\Middleware\AgeVerification;
use App\Models\Feedback;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 後台的回報管理。
 *
 * 回報沒有人通知,所以這一頁的重點不是好看,而是「不會有東西積在那裡沒人發現」:
 * 未處理的排最前面、每個後台頁面的導覽都看得到未處理筆數。
 */
class AdminFeedbackTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(AgeVerification::class);
    }

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    private function make(array $attrs = []): Feedback
    {
        return Feedback::create(array_merge([
            'type' => Feedback::TYPE_BUG,
            'message' => '測試回報內容',
            'locale' => 'zh_TW',
            'status' => Feedback::STATUS_NEW,
        ], $attrs));
    }

    public function test_only_an_admin_gets_in(): void
    {
        $this->make();

        $this->get('/tw/admin/feedback')->assertRedirect();
        $this->actingAs(User::factory()->create())->get('/tw/admin/feedback')->assertForbidden();
        $this->actingAs($this->admin())->get('/tw/admin/feedback')->assertOk();
    }

    public function test_it_lists_reports_with_their_details(): void
    {
        $this->make([
            'message' => '按了沒反應',
            'contact' => 'me@example.com',
            'page_path' => '/tw/wheel-game',
        ]);

        $this->actingAs($this->admin())->get('/tw/admin/feedback')
            ->assertOk()
            ->assertSee('按了沒反應')
            ->assertSee('me@example.com')
            ->assertSee('/tw/wheel-game');
    }

    public function test_unhandled_reports_come_first_by_default(): void
    {
        // 已處理但很新 vs 未處理但比較舊 —— 未處理的要在上面
        $done = $this->make(['message' => '已經處理過的', 'status' => Feedback::STATUS_DONE]);
        $done->forceFill(['created_at' => now()])->save();

        $new = $this->make(['message' => '還沒處理的']);
        $new->forceFill(['created_at' => now()->subDay()])->save();

        $html = $this->actingAs($this->admin())->get('/tw/admin/feedback')->assertOk()->getContent();

        $this->assertLessThan(
            strpos($html, '已經處理過的'),
            strpos($html, '還沒處理的'),
            '未處理的回報應該排在已處理的前面'
        );
    }

    public function test_the_status_can_be_changed_and_only_to_a_known_one(): void
    {
        $item = $this->make();
        $admin = $this->admin();

        $this->actingAs($admin)
            ->patch("/tw/admin/feedback/{$item->id}", ['status' => Feedback::STATUS_DONE])
            ->assertRedirect();
        $this->assertSame(Feedback::STATUS_DONE, $item->fresh()->status);

        $this->actingAs($admin)
            ->patch("/tw/admin/feedback/{$item->id}", ['status' => 'whatever'])
            ->assertSessionHasErrors('status');
        $this->assertSame(Feedback::STATUS_DONE, $item->fresh()->status);
    }

    public function test_it_can_be_deleted(): void
    {
        $item = $this->make();

        $this->actingAs($this->admin())->delete("/tw/admin/feedback/{$item->id}")->assertRedirect();

        $this->assertDatabaseCount('feedback', 0);
    }

    public function test_filters_and_search_narrow_the_list(): void
    {
        $this->make(['message' => '這是一個錯誤', 'type' => Feedback::TYPE_BUG]);
        $this->make(['message' => '這是一個題目建議', 'type' => Feedback::TYPE_PROMPT]);

        $admin = $this->admin();

        $this->actingAs($admin)->get('/tw/admin/feedback?type[]='.Feedback::TYPE_PROMPT)
            ->assertOk()
            ->assertSee('這是一個題目建議')
            ->assertDontSee('這是一個錯誤');

        $this->actingAs($admin)->get('/tw/admin/feedback?q=題目')
            ->assertOk()
            ->assertSee('這是一個題目建議')
            ->assertDontSee('這是一個錯誤');
    }

    public function test_the_pending_count_shows_up_across_the_admin_area(): void
    {
        $this->make();
        $this->make();
        $this->make(['status' => Feedback::STATUS_DONE]);

        $admin = $this->admin();

        // 導覽列在每一個後台頁面都有,所以隨便挑一頁都該看到未處理筆數
        $this->actingAs($admin)->get('/tw/admin/users')
            ->assertOk()
            ->assertSee('admin-nav-count', false)
            ->assertSee('>2</span>', false);

        $this->actingAs($admin)->get('/tw/admin')->assertOk()->assertSee('未處理回報');
    }

    public function test_a_deleted_user_does_not_take_their_report_with_them(): void
    {
        $user = User::factory()->create();
        $item = $this->make(['user_id' => $user->id, 'message' => '這則要留下來']);

        $user->delete();

        // nullOnDelete:回報內容本身還有價值,不該跟著帳號一起消失
        $this->assertDatabaseCount('feedback', 1);
        $this->assertNull($item->fresh()->user_id);
    }
}
