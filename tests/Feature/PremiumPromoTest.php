<?php

namespace Tests\Feature;

use App\Support\PremiumAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 推廣期間全部開放(config premium.promo)。
 *
 * 要守住的兩件事:付費內容所有人都看得到,而且**廣告照樣出現** —— 這個開關
 * 不能被做成「順便免廣告」。
 */
class PremiumPromoTest extends TestCase
{
    use RefreshDatabase;

    public function test_off_by_default(): void
    {
        $this->assertFalse(PremiumAccess::promoActive());
        $this->assertFalse(PremiumAccess::content(null));
    }

    public function test_when_on_a_guest_gets_the_paid_content(): void
    {
        config(['premium.promo.enabled' => true]);

        $this->assertTrue(PremiumAccess::content(null));
        // 沒有金流時,留得住的東西也跟著開放
        $this->assertTrue(PremiumAccess::keepsakes(null));
    }

    public function test_it_switches_itself_off_after_the_last_day(): void
    {
        config(['premium.promo.enabled' => true, 'premium.promo.until' => '2026-10-31']);

        $this->travelTo('2026-10-31 23:59:00');
        $this->assertTrue(PremiumAccess::promoActive(), '最後一天整天都算');

        $this->travelTo('2026-11-01 00:00:01');
        $this->assertFalse(PremiumAccess::promoActive());
    }

    public function test_the_result_page_shows_the_deep_reading_and_no_unlock_prompt(): void
    {
        config(['premium.promo.enabled' => true]);
        $slug = __('traits.items.dom.slug');

        $this->asAgeVerified()->get("/tw/trait-test/{$slug}")
            ->assertOk()
            ->assertDontSee(__('traits.result.deep_locked'))
            ->assertDontSee('id="rw-bar"', false);
    }

    public function test_ads_still_show_during_the_promo(): void
    {
        config(['premium.promo.enabled' => true, 'ads.adapter' => 'exoclick', 'ads.exoclick.zone_home_banner' => '123']);

        $this->asAgeVerified()->get('/tw')->assertOk()->assertSee('data-zone="home_banner"', false);
    }
}
