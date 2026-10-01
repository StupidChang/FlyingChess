<?php

namespace Tests\Feature;

use App\Http\Middleware\AgeVerification;
use App\Services\HornyTestService;
use App\Services\TraitTestService;
use App\Support\QuizSteps;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * 測驗分頁作答:一頁一段,作答存 session,最後一頁交卷。
 *
 * 跨頁靠的是 session,所以這裡照 RoomIdentityAcrossRedirectTest 的做法把明文
 * session id 當 cookie 帶上 —— 不帶的話每個請求都是新的 session,前一頁答的
 * 題目在下一頁就不見了,而測試會因為兩邊都是空的而「剛好」通過。
 */
class QuizStepsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(AgeVerification::class);
    }

    /** 兩份測驗:網址、config、session key、答案範圍、結果頁的 session key */
    public static function quizzes(): array
    {
        return [
            'trait' => ['/tw/trait-test', 'traits', 'trait_answers', TraitTestService::MAX, 'trait_result'],
            'horny' => ['/tw/dual-control', 'horny', 'horny_answers', HornyTestService::MAX, 'horny_result'],
        ];
    }

    private function steps(string $cfg, string $key): QuizSteps
    {
        $svc = $cfg === 'traits' ? TraitTestService::class : HornyTestService::class;

        return new QuizSteps($key, (array) config($cfg.'.questions'), $svc::MIN, $svc::MAX);
    }

    private function keepSession(): void
    {
        $this->withCookie(config('session.cookie'), session()->getId());
    }

    #[DataProvider('quizzes')]
    public function test_every_question_is_on_exactly_one_page(string $url, string $cfg, string $key): void
    {
        $steps = $this->steps($cfg, $key);
        $seen = [];
        foreach (range(1, $steps->count()) as $p) {
            $on = $steps->questionsOn($p);
            $this->assertNotEmpty($on);
            foreach ($on as $i) {
                $this->assertArrayNotHasKey($i, $seen, "第 {$i} 題出現在兩頁");
                $seen[$i] = true;
            }
            // 每一頁都從一段的開頭起算,段落標題不會被切在頁中間
            $this->assertArrayHasKey('section', config($cfg.'.questions')[$on[0]]);
        }
        $this->assertCount(count(config($cfg.'.questions')), $seen);
        $this->assertGreaterThan(1, $steps->count());
    }

    #[DataProvider('quizzes')]
    public function test_each_page_shows_its_own_questions_and_only_page_one_is_indexed(string $url, string $cfg, string $key, int $max): void
    {
        $steps = $this->steps($cfg, $key);
        $texts = (array) __($cfg.'.questions');
        $full = array_fill(0, count($texts), $max);

        foreach (range(1, $steps->count()) as $p) {
            $html = $this->withSession([$key => $full])->get($url.($p > 1 ? '?p='.$p : ''))->assertOk()->getContent();
            foreach ($steps->questionsOn($p) as $i) {
                $this->assertStringContainsString(e($texts[$i]), $html);
            }
            $this->assertStringContainsString('name="step" value="'.$p.'"', $html);
            $p === 1
                ? $this->assertStringContainsString('index,follow', $html)
                : $this->assertStringContainsString('noindex,follow', $html);
        }
    }

    #[DataProvider('quizzes')]
    public function test_skipping_ahead_without_answers_goes_back_to_the_first_page(string $url, string $cfg, string $key): void
    {
        $this->get($url.'?p=3')->assertRedirect($url);
        // 亂填的頁碼當第一頁
        $this->get($url.'?p=999')->assertOk();
    }

    #[DataProvider('quizzes')]
    public function test_walking_through_every_page_lands_on_the_same_result_as_one_shot(string $url, string $cfg, string $key, int $max, string $resultKey): void
    {
        $steps = $this->steps($cfg, $key);
        $all = array_fill(0, $steps->total(), $max);

        $oneShot = $this->post($url, ['a' => $all])->headers->get('Location');

        foreach (range(1, $steps->count()) as $p) {
            $page = array_intersect_key($all, array_flip($steps->questionsOn($p)));
            $res = $this->post($url, ['step' => $p, 'nav' => 'next', 'a' => $page]);
            $this->keepSession();
            $res->assertSessionHasNoErrors();
            if ($p < $steps->count()) {
                $res->assertRedirect($url.'?p='.($p + 1));
            }
        }

        $this->assertSame($oneShot, $res->headers->get('Location'));
        $this->assertNotNull(session($resultKey));
        // 交卷之後作答清掉,重做從空白開始
        $this->assertNull(session($key));
    }

    #[DataProvider('quizzes')]
    public function test_an_unfinished_page_is_rejected_but_keeps_what_was_answered(string $url, string $cfg, string $key, int $max): void
    {
        $first = $this->steps($cfg, $key)->questionsOn(1);
        $partial = [$first[0] => $max];

        $this->from($url)->post($url, ['step' => 1, 'nav' => 'next', 'a' => $partial])
            ->assertRedirect($url)
            ->assertSessionHasErrors('a');
        $this->assertSame($partial, session($key));
    }

    #[DataProvider('quizzes')]
    public function test_going_back_saves_a_half_answered_page_without_complaining(string $url, string $cfg, string $key, int $max): void
    {
        $steps = $this->steps($cfg, $key);
        $before = array_fill_keys($steps->questionsOn(1), $max);
        $second = $steps->questionsOn(2);

        $this->withSession([$key => $before])
            ->post($url, ['step' => 2, 'nav' => 'prev', 'a' => [$second[0] => $max]])
            ->assertRedirect($url)
            ->assertSessionHasNoErrors();
        $this->assertSame($before + [$second[0] => $max], session($key));
    }

    #[DataProvider('quizzes')]
    public function test_an_out_of_range_answer_on_a_page_is_rejected(string $url, string $cfg, string $key, int $max): void
    {
        $first = $this->steps($cfg, $key)->questionsOn(1);

        $this->post($url, ['step' => 1, 'nav' => 'next', 'a' => [$first[0] => $max + 5]])
            ->assertSessionHasErrors('a.'.$first[0]);
    }
}
