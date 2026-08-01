<?php

namespace Tests\Feature;

use App\Http\Middleware\AgeVerification;
use App\Models\Board;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminBoardAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_and_update_someone_elses_board(): void
    {
        $this->withoutMiddleware(AgeVerification::class);
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $admin = User::factory()->create([
            'email_verified_at' => now(),
            'is_admin' => true,
        ]);
        $board = Board::create([
            'name' => '別人的棋盤',
            'user_id' => $owner->id,
            'canvas_rows' => 11,
            'canvas_cols' => 13,
        ]);

        $this->actingAs($admin)->get("/tw/boards/{$board->id}/edit")
            ->assertOk()
            ->assertSee('＋ 新增進場轉盤')
            ->assertSee('addStartWheel()', false);
        $this->actingAs($admin)->patchJson("/tw/boards/{$board->id}", [
            'name' => '管理員已更新',
            'description' => '後台管理內容',
        ])->assertOk();

        $this->assertSame('管理員已更新', $board->fresh()->name);
    }

    public function test_regular_user_still_cannot_edit_someone_elses_board(): void
    {
        $this->withoutMiddleware(AgeVerification::class);
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $other = User::factory()->create(['email_verified_at' => now()]);
        $board = Board::create([
            'name' => '私人棋盤',
            'user_id' => $owner->id,
            'canvas_rows' => 11,
            'canvas_cols' => 13,
        ]);

        $this->actingAs($other)->get("/tw/boards/{$board->id}/edit")->assertForbidden();
    }
}
