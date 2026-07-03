<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BoardController;
use App\Http\Controllers\BucketListController;
use App\Http\Controllers\CardGameController;
use App\Http\Controllers\DiceGameController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\GameHallController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\KingGameController;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\PlayController;
use App\Http\Controllers\PremiumController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\TimeCapsuleController;
use App\Http\Controllers\TruthDareController;
use App\Http\Controllers\WheelGameController;
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

Route::get('/robots.txt', function () {
    return response(
        "User-agent: *\nDisallow: /admin/\nSitemap: ".url('/sitemap.xml')."\n",
        200,
        ['Content-Type' => 'text/plain']
    );
})->name('robots');

// Premium gateway callbacks: URLs are pinned by the payment provider — adding
// a locale prefix would break the contract. The view handler picks language
// from cookie / Accept-Language internally.
Route::post('/premium/callback', [PremiumController::class, 'callback'])
    ->name('premium.callback')
    ->withoutMiddleware([VerifyCsrfToken::class]);

// Payment gateway redirect URL. ECPay's OrderResultURL is a client-side POST
// callback (browser form-posts here after payment), so this route must accept
// both GET (ClientBackURL fallback) and POST (OrderResultURL) and be CSRF-exempt.
// Layout shared with localized routes calls route('home') etc., which require
// the {locale} URL default. set.locale falls back to cookie/Accept-Language when
// no route parameter exists, so the shared layout renders correctly here.
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

            return redirect()->route('home')->with('success', __('auth.verify_email_success', [], 'zh_TW'));
        })->middleware(['auth', 'signed'])->name('verification.verify');

        Route::post('/email/verification-notification', function (Request $request) {
            $request->user()->sendEmailVerificationNotification();

            return back()->with('success', __('auth.verify_email_resent', [], 'zh_TW'));
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

        // Single-player mini games
        Route::get('/card-game', [CardGameController::class, 'show'])->name('card-game.show');
        Route::get('/dice-game', [DiceGameController::class, 'show'])->name('dice-game.show');
        Route::get('/king-game', [KingGameController::class, 'show'])->name('king-game.show');
        Route::get('/wheel-game', [WheelGameController::class, 'show'])->name('wheel-game.show');

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
            Route::post('/', [TimeCapsuleController::class, 'create'])->name('create');
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
            Route::get('/wheel-segments', [AdminController::class, 'wheelSegments'])->name('wheel-segments');
            Route::get('/wheel-segments/create', [AdminController::class, 'createWheelSegment'])->name('wheel-segments.create');
            Route::post('/wheel-segments', [AdminController::class, 'storeWheelSegment'])->name('wheel-segments.store');
            Route::get('/wheel-segments/{wheelSegment}/edit', [AdminController::class, 'editWheelSegment'])->name('wheel-segments.edit');
            Route::patch('/wheel-segments/{wheelSegment}', [AdminController::class, 'updateWheelSegment'])->name('wheel-segments.update');
            Route::delete('/wheel-segments/{wheelSegment}', [AdminController::class, 'destroyWheelSegment'])->name('wheel-segments.destroy');
        });
    });
