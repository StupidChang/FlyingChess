<?php

namespace Tests\Feature;

use App\Http\Middleware\AgeVerification;
use App\Models\Board;
use App\Models\BoardSquare;
use App\Models\User;
use Database\Seeders\BoardTemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 棋盤的推薦人數與大廳排序。
 *
 * 情侶版的題目寫「對方」,多人版寫「在場的異性／左邊的人」,兩人玩多人版會卡在
 * 「找兩位異性」這種格子。所以每張卡片都要標人數,播放頁也要預設開那個人數。
 */
class BoardRecommendedPlayersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(AgeVerification::class);
    }

    private function template(string $name, bool $premium, int $players = 2, ?string $createdAt = null): Board
    {
        $board = Board::create([
            'name' => $name,
            'is_template' => true,
            'is_premium_template' => $premium,
            'recommended_players' => $players,
        ]);
        if ($createdAt) {
            $board->forceFill(['created_at' => $createdAt])->save();
        }
        BoardSquare::create(['board_id' => $board->id, 'position' => 0, 'text' => '起點', 'color' => 'start', 'grid_row' => 1, 'grid_col' => 1]);

        return $board;
    }

    public function test_lobby_lists_every_free_board_before_any_premium_one(): void
    {
        // 付費的建得比較新 —— 原本照 created_at 排,它會排到免費的前面
        $this->template('舊的免費版', false, 2, '2026-01-01');
        $this->template('新的付費版', true, 2, '2026-09-01');
        $this->template('另一張免費版', false, 4, '2026-02-01');

        $html = $this->get('/tw/games')->assertOk()->getContent();

        $premium = strpos($html, '新的付費版');
        $this->assertLessThan($premium, strpos($html, '舊的免費版'));
        $this->assertLessThan($premium, strpos($html, '另一張免費版'));
    }

    public function test_lobby_toggles_between_site_and_community_boards(): void
    {
        $this->template('官方版', false, 2);
        $user = User::factory()->create(['name' => '作者甲']);
        $community = Board::create(['name' => '會員做的棋盤', 'user_id' => $user->id,
            'publish_status' => Board::PUBLISH_APPROVED, 'published_at' => now()]);
        foreach ([0, 1] as $p) {
            BoardSquare::create(['board_id' => $community->id, 'position' => $p, 'text' => '格'.$p, 'color' => 'action', 'grid_row' => 1, 'grid_col' => $p + 1]);
        }

        $site = $this->get('/tw/games')->assertOk();
        $site->assertSee('官方版')->assertDontSee('會員做的棋盤');

        $tab = $this->get('/tw/games?tab=community')->assertOk();
        $tab->assertSee('會員做的棋盤')->assertSee(__('games.lobby_by', ['name' => '作者甲']))
            ->assertDontSee('官方版')
            ->assertSee('noindex,follow', false);
    }

    public function test_the_community_tab_has_an_empty_state(): void
    {
        $this->get('/tw/games?tab=community')->assertOk()
            ->assertSee(__('games.lobby_community_empty_title'));
    }

    public function test_cards_say_who_the_board_is_written_for(): void
    {
        $this->template('兩人版', false, 2);
        $this->template('派對版', false, 4);

        $html = $this->get('/tw/games')->assertOk()->getContent();

        $this->assertStringContainsString('badge-players badge-players-couple">1男1女<', $html);
        $this->assertStringContainsString('badge-players badge-players-group">多男多女<', $html);
    }

    public function test_play_page_opens_with_the_recommended_player_count(): void
    {
        $group = $this->template('派對版', false, 4);
        $couple = $this->template('兩人版', false, 2);

        $this->get($group->canonicalPlayUrl())->assertOk()->assertViewHas('playerCount', 4);
        $this->get($couple->canonicalPlayUrl())->assertOk()->assertViewHas('playerCount', 2);

        // 玩家自己選的人數優先
        $this->get($group->canonicalPlayUrl().'?players=2')->assertOk()->assertViewHas('playerCount', 2);
    }

    public function test_multiplayer_boards_do_not_split_players_into_teams(): void
    {
        /* 使用者要求拿掉 V8.0 的兩人一組:3 人時會多出一個只有一人的「第二組」。
           現在每個人各自一顆棋子,開局視窗是一份平的名單,上方玩家列只是左右兩欄。 */
        $group = $this->template('派對版', false, 4);

        foreach ([3, 4] as $n) {
            $html = $this->get($group->canonicalPlayUrl().'?players='.$n)->assertOk()->getContent();

            $this->assertStringNotContainsString('team-group', $html);
            $this->assertStringNotContainsString('setup-team-block', $html);
            $this->assertStringNotContainsString(__('play.team_n', ['n' => 2]), $html);
            $this->assertSame($n, substr_count($html, 'class="form-group setup-player"'));

            // 1、2 號在左欄,其餘在右欄
            $left = substr($html, strpos($html, 'player-side side-left'), strpos($html, 'turn-center') - strpos($html, 'player-side side-left'));
            $this->assertStringContainsString('id="p2-panel"', $left);
            $this->assertStringNotContainsString('id="p3-panel"', $left);
        }

        // 開局前可以選要不要「追上別人時對方回起點」,預設照棋盤設定
        $html = $this->get($group->canonicalPlayUrl())->getContent();
        $this->assertStringContainsString('name="capture-rule" value="on" checked', $html);

        $couple = $this->template('兩人版', false, 2);
        $html = $this->get($couple->canonicalPlayUrl())->assertOk()->getContent();
        $this->assertStringNotContainsString('player-side', $html);
        $this->assertStringContainsString('id="p2-panel"', $html);
    }

    public function test_the_setup_modal_offers_name_dice_and_piece_styles(): void
    {
        $group = $this->template('派對版', false, 4);
        $html = $this->get($group->canonicalPlayUrl())->assertOk()->getContent();

        // 每位玩家的名字旁都有一顆骰子
        $this->assertSame(4, substr_count($html, 'class="setup-name-dice"'));
        foreach (['disc', 'pawn', 'heart'] as $style) {
            $this->assertStringContainsString('name="piece-style" value="'.$style.'"', $html);
        }
        // 骰名字的字庫有送到前端
        $this->assertStringContainsString('"nameAdj"', $html);
        // 切換的是整張棋盤的大小,不是「格子」
        $this->assertStringContainsString(__('play.js_board_smaller'), $html);
        $this->assertSame('縮小棋盤', __('play.js_board_smaller'));
    }

    public function test_cloning_a_template_keeps_its_player_count(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $group = $this->template('派對版', false, 4);

        $this->actingAs($user)->post(route('boards.template.clone', ['locale' => 'tw', 'board' => $group]));

        $this->assertSame(4, Board::where('user_id', $user->id)->firstOrFail()->recommended_players);
    }

    public function test_every_seeded_template_is_labelled_consistently_with_its_wording(): void
    {
        /* 多人的格子寫「在場的異性」「左邊的人」;情侶的寫「對方」。標錯的話,
           兩人一開局就會抽到「找兩位異性」。 */
        $this->seed(BoardTemplateSeeder::class);

        $group = Board::where('is_template', true)->where('recommended_players', 4)->pluck('name')->all();
        sort($group);
        $expected = BoardTemplateSeeder::GROUP_BOARDS;
        sort($expected);
        $this->assertSame($expected, $group);

        foreach (Board::where('is_template', true)->with('squares')->get() as $board) {
            $text = $board->squares->pluck('text')->implode("\n");
            $groupWords = preg_match_all('/異性|在場|全場|大家|左邊|右邊|其他人|每個人/u', $text);
            if ($board->isGroupPlay()) {
                $this->assertGreaterThan(5, $groupWords, "{$board->name} 標成多人,內容卻不像多人寫法");
            } else {
                $this->assertSame(0, $groupWords, "{$board->name} 標成情侶,卻有多人寫法的格子");
            }
        }
    }
}
