<?php

namespace Tests\Feature;

use App\Support\LocaleHelper;
use Database\Seeders\BoardSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Guards the GEO (generative-engine) surface.
 *
 * The failure this exists to catch is silent and total: AgeVerification runs in
 * the `web` group and answers anything it does not recognise with the age gate
 * at status 200. Before the AI user agents were whitelisted, every generative
 * engine — ChatGPT, Perplexity, Claude — received a ~5KB "年齡確認" page with no
 * game content on it, while Googlebot received the real 25KB page. Nothing about
 * that looks broken from the outside: the status is 200 and the HTML is valid.
 *
 * So the assertions below deliberately check for *content*, never just the code.
 */
class GenerativeEngineAccessTest extends TestCase
{
    use RefreshDatabase;

    /** Bots that fetch because a user asked something, and cite the source back. */
    public static function retrievalBotProvider(): array
    {
        return array_map(fn ($ua) => [$ua], [
            'Mozilla/5.0 (compatible; OAI-SearchBot/1.0; +https://openai.com/searchbot)',
            'Mozilla/5.0 (compatible; ChatGPT-User/1.0; +https://openai.com/bot)',
            'Mozilla/5.0 (compatible; PerplexityBot/1.0; +https://perplexity.ai/perplexitybot)',
            'Mozilla/5.0 (compatible; Perplexity-User/1.0)',
            'Mozilla/5.0 (compatible; Claude-User/1.0)',
            'Mozilla/5.0 (compatible; Claude-SearchBot/1.0)',
        ]);
    }

    /** Bots that take content into a model and send nothing back. */
    public static function trainingBotProvider(): array
    {
        return array_map(fn ($ua) => [$ua], [
            'Mozilla/5.0 (compatible; GPTBot/1.2; +https://openai.com/gptbot)',
            'Mozilla/5.0 (compatible; ClaudeBot/1.0; +claudebot@anthropic.com)',
            'Mozilla/5.0 (compatible; CCBot/2.0; +https://commoncrawl.org/faq/)',
            'Mozilla/5.0 (compatible; Bytespider)',
            'Mozilla/5.0 (compatible; meta-externalagent/1.1)',
        ]);
    }

    #[DataProvider('retrievalBotProvider')]
    public function test_retrieval_ai_crawlers_reach_the_real_page(string $ua): void
    {
        $response = $this->withHeader('User-Agent', $ua)->get('/tw/game-hall');

        $response->assertOk();
        // The game hall's own H1 — present only if the age gate was bypassed.
        $response->assertSee(__('seo.lobby_title'), false);
        $response->assertDontSee('age-gate', false);
    }

    #[DataProvider('trainingBotProvider')]
    public function test_training_ai_crawlers_still_get_the_age_gate(string $ua): void
    {
        $response = $this->withHeader('User-Agent', $ua)->get('/tw/game-hall');

        // 200 on purpose — the age gate is a page, not an error.
        $response->assertOk();
        $response->assertDontSee(__('seo.game_hall_seo_description'), false);
    }

    public function test_robots_txt_is_served_by_the_app_with_a_live_sitemap_url(): void
    {
        $response = $this->get('/robots.txt');

        $response->assertOk();
        // The whole point of generating this: the Sitemap line tracks APP_URL
        // instead of the hard-coded placeholder the old static file carried.
        // (Asserting the absence of "example.com" would be wrong here — the test
        // environment's own APP_URL is flying.example.com.)
        $response->assertDontSee('yourdomain.com');
        $response->assertSee('Sitemap: '.url('/sitemap.xml'));
        // Private areas are locale-prefixed in reality, so a bare /admin/ rule
        // would match nothing.
        $response->assertSee('Disallow: /tw/admin');
        $response->assertSee('User-agent: GPTBot');
    }

    public function test_llms_txt_lists_the_games_with_absolute_urls(): void
    {
        $response = $this->get('/llms.txt');

        $response->assertOk();
        // Links always point at the default locale, whatever language the
        // requester's Accept-Language happens to ask for.
        $default = LocaleHelper::defaultLocale();
        $response->assertSee(LocaleHelper::localizedUrl($default, 'who-most-likely'));
        $response->assertSee(LocaleHelper::localizedUrl($default, 'truth-dare'));
        $response->assertDontSee(':board'); // unreplaced placeholder
    }

    public function test_llms_txt_is_not_redirected_into_a_locale_prefix(): void
    {
        // RedirectUnprefixedUrl 301s anything it does not recognise to /tw/…,
        // which would hand crawlers a redirect instead of the file.
        $this->get('/llms.txt')->assertOk();
    }

    /**
     * @return array<int, array{0: string, 1: int, 2: int|null}>
     */
    public static function gamePageProvider(): array
    {
        return [
            ['/tw/games', 2, 4],
            ['/tw/truth-dare', 1, 6],
            ['/tw/card-game', 2, 6],
            ['/tw/king-game', 3, 6],
            ['/tw/dice-game', 2, 6],
            ['/tw/wheel-game', 2, 6],
            ['/tw/wheel', 2, null],
            ['/tw/who-most-likely', 2, 8],
        ];
    }

    #[DataProvider('gamePageProvider')]
    public function test_game_pages_emit_parsable_schema_with_the_right_player_counts(string $url, int $min, ?int $max): void
    {
        $this->seed(BoardSeeder::class);

        $html = $this->withHeader('User-Agent', 'Googlebot')->get($url)->assertOk()->getContent();

        preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $html, $matches);
        $this->assertNotEmpty($matches[1], "No JSON-LD found on {$url}");

        $byType = [];
        foreach ($matches[1] as $block) {
            $decoded = json_decode($block, true);
            // A trailing comma or an unescaped quote makes the block invisible to
            // every consumer while the page still renders perfectly.
            $this->assertNotNull($decoded, "Invalid JSON-LD on {$url}: ".json_last_error_msg());
            $byType[$decoded['@type']] = $decoded;
        }

        $this->assertArrayHasKey('Organization', $byType, "{$url} is missing the site Organization");
        $this->assertArrayHasKey('VideoGame', $byType, "{$url} is missing its VideoGame node");
        $this->assertArrayHasKey('FAQPage', $byType, "{$url} is missing its FAQPage node");
        $this->assertArrayHasKey('BreadcrumbList', $byType, "{$url} is missing its breadcrumb");

        $players = $byType['VideoGame']['numberOfPlayers'];
        $this->assertSame($min, $players['minValue'], "Wrong minimum player count on {$url}");
        if ($max === null) {
            $this->assertArrayNotHasKey('maxValue', $players, "{$url} should declare no player ceiling");
        } else {
            $this->assertSame($max, $players['maxValue'], "Wrong maximum player count on {$url}");
        }
    }

    #[DataProvider('gamePageProvider')]
    public function test_faq_answers_are_visible_on_the_page_not_only_in_schema(string $url): void
    {
        $html = $this->withHeader('User-Agent', 'Googlebot')->get($url)->assertOk()->getContent();

        preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $html, $matches);
        $faq = null;
        foreach ($matches[1] as $block) {
            $decoded = json_decode($block, true);
            if (($decoded['@type'] ?? null) === 'FAQPage') {
                $faq = $decoded;
            }
        }
        $this->assertNotNull($faq, "No FAQPage on {$url}");

        // Structured data that claims content the page does not show is a
        // policy violation, and the two halves drifting apart is the likely way
        // it happens here, since both read the same lang file today.
        foreach ($faq['mainEntity'] as $question) {
            $this->assertStringContainsString(
                e($question['name']),
                $html,
                "FAQ question is in the schema but not on {$url}"
            );
        }
    }
}
