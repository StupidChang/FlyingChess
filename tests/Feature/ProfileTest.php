<?php

namespace Tests\Feature;

use App\Http\Middleware\AgeVerification;
use App\Models\Board;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // 年齡閘是全域 middleware,會在路由 middleware 之前用 200 回一頁年齡確認,
        // 蓋掉我們要測的東西 —— 測試時關掉它(見 CLAUDE.md 的說明)。
        $this->withoutMiddleware(AgeVerification::class);
        // 正式站的 .env 現在把上傳指到 R2(dev/prod 共用 .env)。測試一律釘在本機
        // 的 fake public disk,免得測試真的打到 R2 —— Storage::fake('public') 才擋得住。
        config(['profile.upload_disk' => 'public']);
    }

    public function test_owner_can_save_personalization_fields(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->patch('/tw/profile', [
            'name' => 'Aki',
            'city' => '台北',
            'looking_for' => '想找一起玩桌遊的人',
            'bio' => '哈囉',
            'profile_theme' => 'teal',
            'profile_public' => '1',
            'banner_pos_y' => 20,
            'avatar_pos_x' => 70,
        ])->assertRedirect();

        $user->refresh();
        $this->assertSame('台北', $user->city);
        $this->assertSame('teal', $user->profile_theme);
        $this->assertTrue($user->profile_public);
        $this->assertSame(20, $user->banner_pos_y);
        $this->assertSame(70, $user->avatar_pos_x);
        $this->assertSame('70% 50%', $user->avatarPosition());
    }

    public function test_an_out_of_range_image_position_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->patch('/tw/profile', [
            'name' => 'Aki',
            'banner_pos_y' => 250,   // 只允許 0–100
        ])->assertSessionHasErrors('banner_pos_y');
    }

    public function test_an_invalid_theme_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->patch('/tw/profile', [
            'name' => 'Aki',
            'profile_theme' => 'rainbow-injection',
        ])->assertSessionHasErrors('profile_theme');
    }

    public function test_a_private_profile_is_not_publicly_visible(): void
    {
        $user = User::factory()->create(['profile_public' => false]);

        $this->get("/tw/u/{$user->id}")
            ->assertNotFound();
    }

    public function test_a_public_profile_shows_its_fields_to_anyone(): void
    {
        $user = User::factory()->create([
            'profile_public' => true,
            'city' => '高雄',
            'looking_for' => '找聊得來的',
        ]);

        $this->get("/tw/u/{$user->id}")
            ->assertOk()
            ->assertSee('高雄')
            ->assertSee('找聊得來的')
            ->assertDontSee($user->email);
    }

    public function test_avatar_upload_stores_an_image_and_rejects_a_non_image(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        // 頭像和其他資料走同一個 PATCH 表單(上傳時就一起儲存)
        $this->actingAs($user)->patch('/tw/profile', [
            'name' => 'Aki',
            'avatar' => UploadedFile::fake()->image('me.jpg', 200, 200),
        ])->assertRedirect();

        $user->refresh();
        $this->assertNotNull($user->avatar_path);
        Storage::disk('public')->assertExists($user->avatar_path);

        // 非圖片(偽裝成 .jpg 的文字檔)要被擋下
        $this->actingAs($user)->patch('/tw/profile', [
            'name' => 'Aki',
            'avatar' => UploadedFile::fake()->create('evil.jpg', 10, 'text/plain'),
        ])->assertSessionHasErrors('avatar');
    }

    public function test_banner_upload_and_removal(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $this->actingAs($user)->patch('/tw/profile', [
            'name' => 'Aki',
            'banner' => UploadedFile::fake()->image('cover.jpg', 1200, 400),
            'banner_pos_y' => 30,
        ])->assertRedirect();

        $user->refresh();
        $this->assertNotNull($user->banner_path);
        $this->assertSame(30, $user->banner_pos_y);
        Storage::disk('public')->assertExists($user->banner_path);

        // 勾「移除橫幅」→ 檔案與路徑都清掉
        $oldPath = $user->banner_path;
        $this->actingAs($user)->patch('/tw/profile', [
            'name' => 'Aki',
            'remove_banner' => '1',
        ])->assertRedirect();

        $user->refresh();
        $this->assertNull($user->banner_path);
        Storage::disk('public')->assertMissing($oldPath);
    }

    public function test_discover_lists_public_profiles_and_filters_by_city(): void
    {
        User::factory()->create(['profile_public' => true, 'name' => '阿明', 'city' => '台北']);
        User::factory()->create(['profile_public' => true, 'name' => '小華', 'city' => '高雄']);
        User::factory()->create(['profile_public' => false, 'name' => '隱藏的人', 'city' => '台北']);

        // 全部公開的
        $this->get('/tw/discover')
            ->assertOk()
            ->assertSee('阿明')
            ->assertSee('小華')
            ->assertDontSee('隱藏的人');

        // 依城市篩選
        $this->get('/tw/discover?city='.urlencode('台北'))
            ->assertOk()
            ->assertSee('阿明')
            ->assertDontSee('小華');
    }

    public function test_public_profile_lists_approved_boards_only_when_enabled(): void
    {
        $user = User::factory()->create(['profile_public' => true, 'show_boards' => true]);
        Board::create([
            'name' => '我的情侶棋盤', 'user_id' => $user->id,
            'canvas_rows' => 11, 'canvas_cols' => 13,
            'publish_status' => Board::PUBLISH_APPROVED,
        ]);

        $this->get("/tw/u/{$user->id}")->assertOk()->assertSee('我的情侶棋盤');

        // 關掉「顯示棋盤」開關 → 不再出現
        $user->update(['show_boards' => false]);
        $this->get("/tw/u/{$user->id}")->assertOk()->assertDontSee('我的情侶棋盤');
    }

    public function test_section_toggles_save(): void
    {
        $user = User::factory()->create(['show_traits' => true, 'show_boards' => true]);

        // 不送 show_boards → 視為關閉(checkbox 沒勾就是關)
        $this->actingAs($user)->patch('/tw/profile', [
            'name' => 'Aki',
            'show_traits' => '1',
        ])->assertRedirect();

        $user->refresh();
        $this->assertTrue($user->show_traits);
        $this->assertFalse($user->show_boards);
    }

    public function test_guests_cannot_edit_a_profile(): void
    {
        $this->patch('/tw/profile', ['name' => 'x'])->assertRedirect();   // → login
        $this->assertGuest();
    }
}
