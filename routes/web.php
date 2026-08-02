<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BoardController;
use App\Http\Controllers\BucketListController;
use App\Http\Controllers\CardGameController;
use App\Http\Controllers\CustomWheelController;
use App\Http\Controllers\DiceController;
use App\Http\Controllers\DiceGameController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\GameHallController;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\KingGameController;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\PlayController;
use App\Http\Controllers\PremiumController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RewardedUnlockController;
use App\Http\Controllers\SesFeedbackController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\TimeCapsuleController;
use App\Http\Controllers\TraitTestController;
use App\Http\Controllers\TruthDareController;
use App\Http\Controllers\WheelGameController;
use App\Http\Controllers\WhoMostLikelyController;
use App\Support\LocaleHelper;
use App\Support\Pricing;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Locale-exempt routes
|--------------------------------------------------------------------------
| These endpoints MUST NOT carry a /tw|cn|jp|en prefix. They are either
| infrastructural (sitemap, robots, health) or contracts with external
| systems (age-verify cookie write, payment gateway callback / redirect).
| RedirectUnprefixedUrl::NEVER_PREFIX keeps them out of the 301 sweep.
*/

Route::post('/age-verify', function () {
    $cookie = cookie('age_verified', '1', 30 * 24 * 60);

    return redirect()->back()->withCookie($cookie);
})->name('age.verify');

// llms.txt — the convention generative engines read for a plain-text map of the
// site. Generated rather than static for the same reason as robots.txt: every
// link has to carry the live domain. Kept deliberately short; the point is that
// an engine can tell what each page is without executing the page's JS.
Route::get('/llms.txt', function () {
    // 這條路由沒有語系前綴,所以 set.locale 會依 cookie / Accept-Language 決定語言 ——
    // 但連結一律指向預設語系。不把語言釘死的話,會產生「英文說明 + /tw 連結」這種
    // 前後不一致的檔案,而且同一個網址每次抓到的語言還可能不一樣。
    $locale = LocaleHelper::defaultLocale();
    app()->setLocale($locale);
    $link = fn (string $path, string $key) => '- ['.__($key).']('.LocaleHelper::localizedUrl($locale, $path).')';

    return response(implode("\n", [
        '# '.__('ui.site_name'),
        '',
        '> '.__('home.meta_description'),
        '',
        '## '.__('games.lobby'),
        $link('game-hall', 'seo.lobby_title').': '.__('seo.game_hall_seo_description'),
        '',
        '## '.__('games.more_games'),
        $link('games', 'games.flying_chess').': '.__('games.desc_flying_chess'),
        $link('truth-dare', 'games.truth_dare').': '.__('games.desc_truth_dare'),
        $link('card-game', 'minigame.card_title').': '.__('games.desc_card'),
        $link('king-game', 'minigame.king_title').': '.__('games.desc_king'),
        $link('dice-game', 'minigame.dice_title').': '.__('games.desc_dice'),
        $link('wheel-game', 'minigame.wheel_title').': '.__('games.desc_wheel'),
        $link('wheel', 'games.pure_wheel').': '.__('games.desc_pure_wheel'),
        $link('who-most-likely', 'minigame.wml_title').': '.__('games.desc_wml'),
        $link('trait-test', 'traits.title').': '.__('traits.seo.description'),
        // Short labels on purpose: seo.play_title carries a :board placeholder and
        // the community/templates SEO titles are full sentences. A link label in
        // llms.txt should read as a page name, so use the plain UI strings here.
        $link('play', 'ui.play').': '.__('games.my_boards_desc'),
        '',
        '## '.__('ui.community_boards'),
        $link('community', 'ui.community_boards').': '.__('seo.community_description'),
        $link('templates', 'games.templates_short').': '.__('seo.templates_description'),
        '',
        '## Optional',
        $link('premium', 'seo.premium_title').': '.__('seo.premium_description', ['price' => Pricing::entryPrice()]),
        $link('privacy', 'legal.privacy_title').'',
        $link('terms', 'legal.terms_title').'',
        '',
    ])."\n", 200, ['Content-Type' => 'text/plain; charset=utf-8']);
})->name('llms');

Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');

// Per-locale sitemap (referenced by /sitemap.xml index)
Route::get('/sitemap-{prefix}.xml', [SitemapController::class, 'locale'])
    ->where('prefix', 'tw|cn|jp|en')
    ->name('sitemap.locale');

// ads.txt — required by ad networks (ExoClick / TrafficJunky / AdSense) to
// verify the site is authorized to sell its inventory. Content is env-driven:
// ADS_TXT_LINES="exoclick.com, 123456, DIRECT|..." ( | = newline).
Route::get('/ads.txt', function () {
    $lines = trim(str_replace('|', "\n", (string) config('ads.txt_lines')));

    abort_if($lines === '', 404);

    return response($lines."\n", 200, ['Content-Type' => 'text/plain']);
})->name('ads.txt');

/*
 * robots.txt is generated here rather than shipped as public/robots.txt for one
 * reason: the Sitemap line has to carry the live domain. The static file that
 * used to sit in public/ hard-coded https://yourdomain.com, and because nginx
 * `try_files $uri` serves a real file before it ever reaches Laravel, that
 * placeholder is what crawlers actually got — this route existed but was dead.
 *
 * Two things worth knowing before editing:
 *
 * - Every app route lives under a /{locale} prefix, so `Disallow: /admin/` does
 *   not match anything; the real path is /tw/admin/. The private paths below are
 *   therefore emitted once per locale prefix, plus a wildcard form, instead of
 *   once at the root.
 * - The AI section mirrors AgeVerification::CRAWLER_PATTERNS. Retrieval bots are
 *   allowed there and here; training bots are gated there and disallowed here.
 *   Change one and change the other, or the two disagree about the same bot.
 */
Route::get('/robots.txt', function () {
    $privatePaths = ['admin', 'boards', 'email', 'forgot-password', 'reset-password', 'profile', 'my-wheels', 'my-dice'];
    $prefixes = array_column(LocaleHelper::supported(), 'prefix');

    $lines = ['User-agent: *'];
    foreach ($privatePaths as $path) {
        $lines[] = 'Disallow: /*/'.$path;
        foreach ($prefixes as $prefix) {
            $lines[] = 'Disallow: /'.$prefix.'/'.$path;
        }
    }

    // Training-only crawlers: they take content into a model and send no traffic
    // back. The age gate already stops them; this states the policy explicitly
    // for the ones that honour robots.txt. Google-Extended and Applebot-Extended
    // are robots.txt tokens rather than real user agents — they opt this site out
    // of Gemini / Apple Intelligence training without touching Googlebot or
    // Applebot, which stay allowed above.
    $trainingBots = [
        'GPTBot', 'ClaudeBot', 'CCBot', 'Bytespider', 'meta-externalagent',
        'Amazonbot', 'Google-Extended', 'Applebot-Extended', 'anthropic-ai',
        'cohere-ai', 'Diffbot', 'Omgilibot', 'ImagesiftBot',
    ];
    foreach ($trainingBots as $bot) {
        $lines[] = '';
        $lines[] = 'User-agent: '.$bot;
        $lines[] = 'Disallow: /';
    }

    $lines[] = '';
    $lines[] = 'Sitemap: '.url('/sitemap.xml');

    return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain']);
})->name('robots');

// Premium gateway callbacks: URLs are pinned by the payment provider — adding
// a locale prefix would break the contract. The view handler picks language
// from cookie / Accept-Language internally.
/*
 * SES 的退信／客訴回饋(SNS webhook)。
 *
 * 不在語系前綴底下:通知是 AWS 送來的,沒有語系可言,而且網址填進 SNS 之後
 * 不該因為前綴調整而失效。CSRF 也要排除(bootstrap/app.php),AWS 不會帶 token。
 *
 * 端點是公開的,安全性全靠 SNS 的簽章驗證,見 App\Support\SnsMessage。
 */
Route::post('/ses/feedback', SesFeedbackController::class)
    ->name('ses.feedback')
    ->middleware('throttle:120,1');

Route::post('/premium/callback', [PremiumController::class, 'callback'])
    ->name('premium.callback')
    ->withoutMiddleware([VerifyCsrfToken::class]);

// Payment gateway redirect URL. ECPay's OrderResultURL is a client-side POST
// callback (browser form-posts here after payment), so this route must accept
// both GET (ClientBackURL fallback) and POST (OrderResultURL) and be CSRF-exempt.
// Layout shared with localized routes calls route('home') etc., which require
// the {locale} URL default. set.locale falls back to cookie/Accept-Language when
// no route parameter exists, so the shared layout renders correctly here.
// Google 登入(Socialite)。redirect URI 必須是固定 URL,不能帶語系前綴 ——
// 否則使用者切語言後 Google Console 註冊的 URI 就對不上。與金流 callback 同理。
// set.locale 讓共用 layout 內的 route('home') 等呼叫仍能解析。
Route::middleware('set.locale')->group(function () {
    Route::get('/auth/google', [GoogleAuthController::class, 'redirect'])->name('google.redirect');
    Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('google.callback');
});

Route::match(['get', 'post'], '/premium/result', [PremiumController::class, 'result'])
    ->name('premium.result')
    ->middleware('set.locale')
    ->withoutMiddleware([VerifyCsrfToken::class]);

/*
|--------------------------------------------------------------------------
| Localized routes (everything else)
|--------------------------------------------------------------------------
| All public + authed pages go through this group. {locale} is constrained
| to the four supported URL prefixes. SetLocale middleware reads the prefix,
| sets app()->setLocale(zh_TW|zh_CN|ja|en), and pins URL::defaults so that
| existing route('foo') / url() calls automatically inherit the prefix.
*/

Route::prefix('{locale}')
    ->where(['locale' => 'tw|cn|jp|en'])
    ->middleware(['set.locale', 'not.banned'])
    ->group(function () {
        Route::get('/', [HomeController::class, 'index'])->name('home');
        Route::get('/game-hall', [GameHallController::class, 'index'])->name('game-hall.index');

        // Legal pages
        Route::get('/privacy', [LegalController::class, 'privacy'])->name('legal.privacy');
        Route::get('/terms', [LegalController::class, 'terms'])->name('legal.terms');

        // Auth (guest only)
        Route::middleware('guest')->group(function () {
            Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
            Route::post('/login', [AuthController::class, 'login']);
            Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
            Route::post('/register', [AuthController::class, 'register']);

            Route::get('/forgot-password', [PasswordResetController::class, 'showForgotForm'])->name('password.request');
            Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])->name('password.email');
            Route::get('/reset-password/{token}', [PasswordResetController::class, 'showResetForm'])->name('password.reset');
            Route::post('/reset-password', [PasswordResetController::class, 'resetPassword'])->name('password.update');
        });
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

        // Email verification
        Route::get('/email/verify', function () {
            return view('auth.verify-email');
        })->middleware('auth')->name('verification.notice');

        Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
            $request->fulfill();

            return redirect()->route('home')->with('success', __('auth.verify_email_success'));
        })->middleware(['auth', 'signed'])->name('verification.verify');

        Route::post('/email/verification-notification', function (Request $request) {
            $request->user()->sendEmailVerificationNotification();

            return back()->with('success', __('auth.verify_email_resent'));
        })->middleware(['auth', 'throttle:6,1'])->name('verification.send');

        // Flying Chess
        Route::prefix('games')->name('games.')->group(function () {
            Route::get('/', [GameController::class, 'lobby'])->name('lobby');
            Route::post('/', [GameController::class, 'create'])->name('create');
            Route::get('/{code}', [GameController::class, 'show'])->name('show');
            Route::post('/{code}/join', [GameController::class, 'join'])->name('join');
            Route::post('/{code}/start', [GameController::class, 'start'])->name('start');
            Route::post('/{code}/roll', [GameController::class, 'roll'])->name('roll');
            Route::post('/{code}/move', [GameController::class, 'move'])->name('move');
            Route::get('/{code}/state', [GameController::class, 'state'])->name('state');
        });

        // Truth or Dare
        Route::prefix('truth-dare')->name('truth-dare.')->group(function () {
            Route::get('/', [TruthDareController::class, 'lobby'])->name('lobby');
            Route::post('/', [TruthDareController::class, 'create'])->name('create');
            Route::get('/{code}', [TruthDareController::class, 'show'])->name('show');
            Route::post('/{code}/join', [TruthDareController::class, 'join'])->name('join');
            Route::post('/{code}/start', [TruthDareController::class, 'start'])->name('start');
            Route::post('/{code}/draw', [TruthDareController::class, 'draw'])->name('draw');
            Route::post('/{code}/next', [TruthDareController::class, 'nextPlayer'])->name('next');
            Route::get('/{code}/state', [TruthDareController::class, 'state'])->name('state');
            Route::post('/{code}/leave', [TruthDareController::class, 'leave'])->name('leave');
        });

        // 看廣告換一段時間的付費內容。throttle 是必要的:兌換端點決定了誰能玩到
        // 付費題庫,不擋的話等於開放無限重試。
        Route::post('/ad-unlock/start', [RewardedUnlockController::class, 'start'])
            ->name('rewarded.start')->middleware('throttle:20,1');
        Route::post('/ad-unlock/claim', [RewardedUnlockController::class, 'claim'])
            ->name('rewarded.claim')->middleware('throttle:20,1');

        /* Single-player mini games.
         *
         * 這幾頁會把題庫的一部分送進 HTML(見 App\Support\ContentExposure),所以
         * 蒐集整份題庫的方法就是「一直重載、每次撈走不同的一小把」。throttle 是
         * 讓那件事變慢的那一半 —— 只裁切不限速的話,重載五次就湊回來了。
         *
         * 40/分鐘 對真人非常寬鬆(玩一場只會載一次),對想枚舉的人則是硬牆。
         * 爬蟲一分鐘也不會抓四十次同一批頁面。
         */
        Route::middleware('throttle:40,1')->group(function () {
            Route::get('/card-game', [CardGameController::class, 'show'])->name('card-game.show');
            Route::get('/dice-game', [DiceGameController::class, 'show'])->name('dice-game.show');
            Route::get('/king-game', [KingGameController::class, 'show'])->name('king-game.show');
            Route::get('/wheel-game', [WheelGameController::class, 'show'])->name('wheel-game.show');
            Route::get('/who-most-likely', [WhoMostLikelyController::class, 'show'])->name('who-most-likely.show');
        });

        // 純轉盤:只有轉盤與指針,沒有題庫,不用限速
        Route::get('/wheel', [WheelGameController::class, 'pure'])->name('wheel.pure');

        // 自訂轉盤。頁面公開,存檔才要登入(下面的 my-wheels 那組)
        Route::get('/custom-wheel', [CustomWheelController::class, 'page'])->name('custom-wheel.page');

        /* 枕邊屬性測驗。結果頁是獨立網址,20 種屬性就是 20 個可以被搜尋到、
           可以被分享的頁面 —— 做成交卷後的一次性畫面的話,SEO 貢獻是零。
           交卷限速:算分不重,但這是一個匿名可寫入的端點。 */
        Route::get('/trait-test', [TraitTestController::class, 'show'])->name('trait-test.show');
        Route::post('/trait-test', [TraitTestController::class, 'submit'])
            ->name('trait-test.submit')->middleware('throttle:20,1');
        Route::get('/trait-test/{slug}', [TraitTestController::class, 'result'])->name('trait-test.result');

        // 自訂轉盤的儲存 / 讀取 / 刪除(登入 + 已驗證)。純 JSON API,
        // 由 partials/custom-wheel 的編輯器以 fetch 呼叫。
        Route::prefix('my-wheels')->name('custom-wheel.')->middleware(['auth', 'verified'])->group(function () {
            Route::get('/', [CustomWheelController::class, 'index'])->name('index');
            Route::post('/', [CustomWheelController::class, 'store'])->name('store');
            Route::delete('/{customWheel}', [CustomWheelController::class, 'destroy'])->name('destroy');
        });

        // Custom dice management (logged-in + verified)
        Route::prefix('my-dice')->name('dice.')->middleware(['auth', 'verified'])->group(function () {
            Route::get('/', [DiceController::class, 'index'])->name('index');
            Route::post('/', [DiceController::class, 'store'])->name('store');
            Route::patch('/{dice}', [DiceController::class, 'update'])->name('update');
            Route::delete('/{dice}', [DiceController::class, 'destroy'])->name('destroy');
        });

        // Bucket List
        Route::prefix('bucket-list')->name('bucket-list.')->group(function () {
            Route::get('/', [BucketListController::class, 'lobby'])->name('lobby');
            Route::post('/', [BucketListController::class, 'create'])->name('create');
            Route::get('/{shareCode}', [BucketListController::class, 'show'])->name('show')
                ->middleware('throttle:60,1');
            Route::post('/{shareCode}/items', [BucketListController::class, 'addItem'])->name('items.add');
            Route::post('/{shareCode}/items/{itemId}/vote', [BucketListController::class, 'voteItem'])->name('items.vote');
            Route::delete('/{shareCode}/items/{itemId}', [BucketListController::class, 'deleteItem'])->name('items.delete');
        });

        // Time Capsule
        Route::prefix('time-capsule')->name('time-capsule.')->group(function () {
            Route::get('/', [TimeCapsuleController::class, 'lobby'])->name('lobby');
            /* 膠囊會在指定日期寄一封信到使用者自己填的地址 —— 不需要登入、
               地址不需要驗證,等於一個延遲發信的管道。限制建立頻率。 */
            Route::post('/', [TimeCapsuleController::class, 'create'])->name('create')
                ->middleware('throttle:6,60');
            Route::get('/{shareCode}', [TimeCapsuleController::class, 'show'])->name('show')
                ->middleware('throttle:60,1');
            Route::post('/{shareCode}/answers', [TimeCapsuleController::class, 'saveAnswers'])->name('answers');
            Route::post('/{shareCode}/seal', [TimeCapsuleController::class, 'seal'])->name('seal');
        });

        // Custom board play
        Route::get('/play', [PlayController::class, 'show'])->name('play');
        Route::get('/play/share/{code}', [PlayController::class, 'showByCode'])->name('play.code')->middleware('throttle:60,1');
        Route::get('/play/{board}', [PlayController::class, 'show'])->name('play.board');

        // Profile
        Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index')->middleware(['auth', 'verified']);

        // Board CRUD
        Route::prefix('boards')->name('boards.')->middleware(['auth', 'verified'])->group(function () {
            Route::get('/', [BoardController::class, 'index'])->name('index');
            Route::get('/create', [BoardController::class, 'create'])->name('create');
            Route::post('/', [BoardController::class, 'store'])->name('store');
            Route::get('/{board}/edit', [BoardController::class, 'edit'])->name('edit');
            Route::patch('/{board}', [BoardController::class, 'update'])->name('update');
            Route::delete('/{board}', [BoardController::class, 'destroy'])->name('destroy');

            Route::patch('/{board}/squares/{position}', [BoardController::class, 'updateSquare'])->name('squares.update');
            Route::post('/{board}/squares', [BoardController::class, 'storeSquare'])->name('squares.store');
            Route::delete('/{board}/squares/{position}', [BoardController::class, 'destroySquare'])->name('squares.destroy');
            Route::patch('/{board}/squares', [BoardController::class, 'bulkUpdateSquares'])->name('squares.bulk');
            Route::patch('/{board}/canvas', [BoardController::class, 'updateCanvas'])->name('canvas.update');
            Route::patch('/{board}/path', [BoardController::class, 'updatePath'])->name('path.update');
            Route::patch('/{board}/rules', [BoardController::class, 'updateRules'])->name('rules.update');
            Route::post('/{board}/preset', [BoardController::class, 'applyPreset'])->name('preset');

            Route::post('/{board}/publish', [BoardController::class, 'publish'])->name('publish');
            Route::post('/{board}/unpublish', [BoardController::class, 'unpublish'])->name('unpublish');
        });

        // Community boards (public discovery of user-published boards)
        Route::get('/community', [BoardController::class, 'community'])->name('boards.community');

        // Templates
        Route::get('/templates', [BoardController::class, 'templates'])->name('boards.templates');
        Route::get('/templates/{board}', [BoardController::class, 'templatePreview'])->name('boards.template.preview');
        Route::post('/templates/{board}/clone', [BoardController::class, 'cloneTemplate'])
            ->name('boards.template.clone')
            ->middleware(['auth', 'verified']);

        // Premium (index + checkout only — callback/result are non-localized above)
        Route::prefix('premium')->name('premium.')->group(function () {
            Route::get('/', [PremiumController::class, 'index'])->name('index');
            Route::post('/checkout', [PremiumController::class, 'checkout'])->name('checkout')->middleware('auth');
        });

        // Admin
        Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {
            Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');
            Route::get('/traffic', [AdminController::class, 'traffic'])->name('traffic');
            Route::get('/boards', [AdminController::class, 'boards'])->name('boards');
            Route::get('/board-reviews', [AdminController::class, 'boardReviews'])->name('boards.reviews');
            Route::post('/boards/{board}/approve', [AdminController::class, 'approveBoard'])->name('boards.approve');
            Route::post('/boards/{board}/reject', [AdminController::class, 'rejectBoard'])->name('boards.reject');
            Route::get('/boards/{board}/edit', [AdminController::class, 'editBoard'])->name('boards.edit');
            Route::patch('/boards/{board}', [AdminController::class, 'updateBoard'])->name('boards.update');
            Route::get('/cards', [AdminController::class, 'cards'])->name('cards');
            Route::get('/cards/create', [AdminController::class, 'createCard'])->name('cards.create');
            Route::post('/cards', [AdminController::class, 'storeCard'])->name('cards.store');
            Route::get('/cards/{card}/edit', [AdminController::class, 'editCard'])->name('cards.edit');
            Route::patch('/cards/{card}', [AdminController::class, 'updateCard'])->name('cards.update');
            Route::post('/cards/{card}/duplicate', [AdminController::class, 'duplicateCard'])->name('cards.duplicate');
            Route::delete('/cards/{card}', [AdminController::class, 'destroyCard'])->name('cards.destroy');
            Route::get('/users', [AdminController::class, 'users'])->name('users');
            Route::get('/users/{user}/edit', [AdminController::class, 'editUser'])->name('users.edit');
            Route::patch('/users/{user}', [AdminController::class, 'updateUser'])->name('users.update');
            Route::post('/users/{user}/ban', [AdminController::class, 'banUser'])->name('users.ban');
            Route::post('/users/{user}/unban', [AdminController::class, 'unbanUser'])->name('users.unban');
            Route::delete('/users/{user}', [AdminController::class, 'destroyUser'])->name('users.destroy');
            Route::get('/games', [AdminController::class, 'games'])->name('games');
            Route::post('/games/cleanup', [AdminController::class, 'cleanupGames'])->name('games.cleanup');
            Route::delete('/games/{game}', [AdminController::class, 'destroyGame'])->name('games.destroy');
            // 四個小遊戲的題庫(誰最有可能 / 撲克牌 / 國王 / 骰子)
            Route::get('/prompts', [AdminController::class, 'prompts'])->name('prompts');
            Route::get('/prompts/create', [AdminController::class, 'createPrompt'])->name('prompts.create');
            Route::post('/prompts', [AdminController::class, 'storePrompt'])->name('prompts.store');
            Route::post('/prompts/import', [AdminController::class, 'importPrompts'])->name('prompts.import');
            Route::get('/prompts/{prompt}/edit', [AdminController::class, 'editPrompt'])->name('prompts.edit');
            Route::patch('/prompts/{prompt}', [AdminController::class, 'updatePrompt'])->name('prompts.update');
            Route::post('/prompts/{prompt}/duplicate', [AdminController::class, 'duplicatePrompt'])->name('prompts.duplicate');
            Route::delete('/prompts/{prompt}', [AdminController::class, 'destroyPrompt'])->name('prompts.destroy');

            Route::get('/wheel-segments', [AdminController::class, 'wheelSegments'])->name('wheel-segments');
            Route::get('/wheel-segments/create', [AdminController::class, 'createWheelSegment'])->name('wheel-segments.create');
            Route::post('/wheel-segments', [AdminController::class, 'storeWheelSegment'])->name('wheel-segments.store');
            Route::get('/wheel-segments/{wheelSegment}/edit', [AdminController::class, 'editWheelSegment'])->name('wheel-segments.edit');
            Route::patch('/wheel-segments/{wheelSegment}', [AdminController::class, 'updateWheelSegment'])->name('wheel-segments.update');
            Route::post('/wheel-segments/{wheelSegment}/duplicate', [AdminController::class, 'duplicateWheelSegment'])->name('wheel-segments.duplicate');
            Route::delete('/wheel-segments/{wheelSegment}', [AdminController::class, 'destroyWheelSegment'])->name('wheel-segments.destroy');
        });
    });
