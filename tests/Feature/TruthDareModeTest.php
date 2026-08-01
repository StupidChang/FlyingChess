<?php

namespace Tests\Feature;

use App\Http\Middleware\AgeVerification;
use App\Models\Game;
use App\Models\TruthDareCard;
use App\Services\TruthDareService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 真心話大冒險的「類型」與「人數」是兩個軸。
 *
 * 這一組測試存在的原因是原本兩個軸被壓成一個 category(truth/dare/couple/party):
 * dare 的 24 張裡有 23 張寫著「另一半」,所以三五好友按下「大冒險」會抽到
 * 「在另一半耳邊吹一口氣」,情侶按「派對」會抽到「對在場的人放電」。
 * 真正要守住的不是「有 mode 這個欄位」,而是**抽不到對不上場合的題目**。
 */
class TruthDareModeTest extends TestCase
{
    use RefreshDatabase;

    private function card(string $category, string $audience, string $content): TruthDareCard
    {
        return TruthDareCard::create([
            'category' => $category,
            'audience' => $audience,
            'content' => $content,
            'tier' => 'premium',
        ]);
    }

    private function room(string $mode): Game
    {
        $result = app(TruthDareService::class)
            ->createGame('主揪', 'sess-1', false, null, true, $mode);

        return $result['game'];
    }

    public function test_a_couple_room_never_draws_a_group_prompt(): void
    {
        $this->card('dare', 'couple', '在另一半耳邊吹一口氣');
        $this->card('dare', 'party', '對在場你覺得最性感的人放電');

        $game = $this->room('couple');
        $service = app(TruthDareService::class);

        // 抽到牌庫見底,確認一路上都沒有摸到多人場的題目。
        for ($i = 0; $i < 5; $i++) {
            $result = $service->drawCard($game->fresh(), 'dare', false, true);
            if (! $result['success']) {
                break;
            }
            $this->assertStringNotContainsString('在場', $result['card']['content']);
        }
    }

    public function test_a_group_room_never_draws_a_partner_prompt(): void
    {
        $this->card('dare', 'couple', '在另一半耳邊吹一口氣');
        $this->card('dare', 'party', '對在場你覺得最性感的人放電');

        $game = $this->room('party');
        $service = app(TruthDareService::class);

        for ($i = 0; $i < 5; $i++) {
            $result = $service->drawCard($game->fresh(), 'dare', false, true);
            if (! $result['success']) {
                break;
            }
            $this->assertStringNotContainsString('另一半', $result['card']['content']);
        }
    }

    public function test_neutral_prompts_come_up_in_both_modes(): void
    {
        $this->card('truth', 'both', '你最近一次心動是什麼時候');

        foreach (['couple', 'party'] as $mode) {
            $result = app(TruthDareService::class)
                ->drawCard($this->room($mode), 'truth', false, true);

            $this->assertTrue($result['success'], "{$mode} 抽不到通用題目");
        }
    }

    public function test_the_room_mode_follows_the_number_of_players(): void
    {
        $this->withoutMiddleware(AgeVerification::class);

        // 兩個人 → 情侶場
        $this->post('/tw/truth-dare', ['players' => ['小明', '小美']]);
        $this->assertSame('couple', Game::latest('id')->first()->game_state['mode']);

        // 三個人以上 → 多人場
        $this->post('/tw/truth-dare', ['players' => ['小明', '小美', '阿豪']]);
        $this->assertSame('party', Game::latest('id')->first()->game_state['mode']);
    }

    public function test_the_host_can_override_the_mode(): void
    {
        $this->withoutMiddleware(AgeVerification::class);

        // 兩個朋友(不是情侶)一起玩,要的是多人場的題目。
        $this->post('/tw/truth-dare', ['players' => ['小明', '小美'], 'mode' => 'party']);

        $this->assertSame('party', Game::latest('id')->first()->game_state['mode']);
    }

    public function test_the_old_audience_values_are_no_longer_accepted_as_a_category(): void
    {
        $this->withoutMiddleware(AgeVerification::class);
        $game = $this->room('couple');

        /* couple / party 以前是 category 的合法值。留著的話,前端只要有一顆沒清乾淨的
           舊按鈕就會送上來,而它現在永遠抽不到東西 —— 寧可直接擋掉。 */
        $this->postJson("/tw/truth-dare/{$game->code}/draw", ['category' => 'party'])
            ->assertStatus(422);
    }
}
