<?php

namespace Tests\Feature;

use Database\Seeders\BoardSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Renders every parameter-free route so that a controller pointing at a view
 * that no longer exists fails here instead of in production. Cheap to run and
 * the only guard against silent breakage when views get merged or deleted.
 */
class PublicRoutesSmokeTest extends TestCase
{
    use RefreshDatabase;

    /**
     * AgeVerification sits in the `web` middleware group, so it runs *before*
     * route middleware and answers an unverified visitor with the age gate at
     * status 200. Asserting only the status code would therefore pass while
     * never reaching the real view. Feeding a whitelisted crawler UA is how the
     * middleware itself allows bots through, and it keeps these requests
     * cookie-independent — see AgeVerification::CRAWLER_PATTERNS.
     */
    private function visit(string $url)
    {
        return $this->withHeader('User-Agent', 'Googlebot')->get($url);
    }

    public static function htmlPageProvider(): array
    {
        return array_map(fn ($u) => [$u], [
            '/tw',
            '/tw/game-hall',
            '/tw/games',
            '/tw/card-game',
            '/tw/king-game',
            '/tw/dice-game',
            '/tw/wheel-game',
            '/tw/wheel',
            '/tw/who-most-likely',
            '/tw/truth-dare',
            '/tw/bucket-list',
            '/tw/time-capsule',
            '/tw/templates',
            '/tw/community',
            '/tw/premium',
            '/tw/login',
            '/tw/register',
            '/tw/privacy',
            '/tw/terms',
        ]);
    }

    #[DataProvider('htmlPageProvider')]
    public function test_html_page_renders_past_the_age_gate(string $url): void
    {
        $this->visit($url)
            ->assertOk()
            ->assertDontSee('class="age-gate"', false);
    }

    public static function textEndpointProvider(): array
    {
        return [['/robots.txt'], ['/sitemap.xml']];
    }

    #[DataProvider('textEndpointProvider')]
    public function test_text_endpoint_responds(string $url): void
    {
        $this->visit($url)->assertOk();
    }

    /**
     * Locale prefixes are not the locale codes: zh_TW => tw, zh_CN => cn,
     * ja => jp. Requesting /ja instead of /jp gets a 301, which is easy to
     * mistake for a broken locale — see config('app.available_locales').
     */
    public static function localePrefixProvider(): array
    {
        return [['tw'], ['en'], ['cn'], ['jp']];
    }

    #[DataProvider('localePrefixProvider')]
    public function test_home_renders_for_every_locale_prefix(string $prefix): void
    {
        $this->visit("/$prefix")->assertOk();
    }

    public static function authOnlyPageProvider(): array
    {
        return array_map(fn ($u) => [$u], [
            '/tw/boards',
            '/tw/boards/create',
            '/tw/my-dice',
            '/tw/my-wheels',
            '/tw/profile',
            '/tw/admin',
        ]);
    }

    #[DataProvider('authOnlyPageProvider')]
    public function test_auth_only_page_sends_guest_to_login(string $url): void
    {
        $this->visit($url)->assertRedirect(route('login', ['locale' => 'tw']));
    }

    /**
     * /play is the one public page that cannot render on an empty database: it
     * falls back to the board flagged is_default, and aborts 404 when no board
     * exists. It therefore needs the seeder rather than a place in the provider
     * above — a bare 404 here means the default board is missing, not that the
     * route is broken.
     */
    public function test_play_renders_with_the_default_board_seeded(): void
    {
        $this->seed(BoardSeeder::class);

        $this->visit('/tw/play')
            ->assertOk()
            ->assertDontSee('class="age-gate"', false);
    }

    /**
     * The card game and king game were merged onto a single view; both routes
     * were kept so their titles and canonicals stay distinct. This pins that
     * arrangement down, because the old per-game views have been deleted and a
     * regression would only surface as a missing-view error at runtime.
     */
    public function test_card_and_king_routes_both_render_the_shared_view(): void
    {
        foreach (['/tw/card-game', '/tw/king-game'] as $url) {
            $this->visit($url)
                ->assertOk()
                ->assertSee('mg-card-scene', false);
        }
    }
}
