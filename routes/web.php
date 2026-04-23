<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\BoardController;
use App\Http\Controllers\PlayController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\PremiumController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\TruthDareController;
use App\Http\Controllers\CardGameController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

// Age verification POST endpoint
Route::post('/age-verify', function () {
    $cookie = cookie('age_verified', '1', 30 * 24 * 60);
    return redirect()->back()->withCookie($cookie);
})->name('age.verify');

// Sitemap
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');

// Legal pages
Route::get('/privacy', [LegalController::class, 'privacy'])->name('legal.privacy');
Route::get('/terms',   [LegalController::class, 'terms'])->name('legal.terms');

// Auth routes (guest only)
Route::middleware('guest')->group(function () {
    Route::get('/login',    [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login',   [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register',[AuthController::class, 'register']);

    // Password reset (guest only)
    Route::get('/forgot-password',        [PasswordResetController::class, 'showForgotForm'])->name('password.request');
    Route::post('/forgot-password',       [PasswordResetController::class, 'sendResetLink'])->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password',        [PasswordResetController::class, 'resetPassword'])->name('password.update');
});
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// Email verification routes
Route::get('/email/verify', function () {
    return view('auth.verify-email');
})->middleware('auth')->name('verification.notice');

Route::get('/email/verify/{id}/{hash}', function (\Illuminate\Foundation\Auth\EmailVerificationRequest $request) {
    $request->fulfill();
    return redirect()->route('home')->with('success', '電子信箱驗證成功！歡迎加入。');
})->middleware(['auth', 'signed'])->name('verification.verify');

Route::post('/email/verification-notification', function (\Illuminate\Http\Request $request) {
    $request->user()->sendEmailVerificationNotification();
    return back()->with('success', '驗證信已重新寄出，請查收信箱。');
})->middleware(['auth', 'throttle:6,1'])->name('verification.send');

// Flying Chess (公開，session-based)
Route::prefix('games')->name('games.')->group(function () {
    Route::get('/',                          [GameController::class, 'lobby'])->name('lobby');
    Route::post('/',                         [GameController::class, 'create'])->name('create');
    Route::get('/{code}',                    [GameController::class, 'show'])->name('show');
    Route::post('/{code}/join',              [GameController::class, 'join'])->name('join');
    Route::post('/{code}/start',             [GameController::class, 'start'])->name('start');
    Route::post('/{code}/roll',              [GameController::class, 'roll'])->name('roll');
    Route::post('/{code}/move',              [GameController::class, 'move'])->name('move');
    Route::get('/{code}/state',              [GameController::class, 'state'])->name('state');
});

// Truth or Dare (公開，session-based)
Route::prefix('truth-dare')->name('truth-dare.')->group(function () {
    Route::get('/',                          [TruthDareController::class, 'lobby'])->name('lobby');
    Route::post('/',                         [TruthDareController::class, 'create'])->name('create');
    Route::get('/{code}',                    [TruthDareController::class, 'show'])->name('show');
    Route::post('/{code}/join',              [TruthDareController::class, 'join'])->name('join');
    Route::post('/{code}/start',             [TruthDareController::class, 'start'])->name('start');
    Route::post('/{code}/draw',              [TruthDareController::class, 'draw'])->name('draw');
    Route::post('/{code}/next',              [TruthDareController::class, 'nextPlayer'])->name('next');
    Route::get('/{code}/state',              [TruthDareController::class, 'state'])->name('state');
    Route::post('/{code}/leave',             [TruthDareController::class, 'leave'])->name('leave');
});

// Card Game (單機版，所有邏輯在前端 JS)
Route::get('/card-game', [CardGameController::class, 'show'])->name('card-game.show');

// Play (公開)
Route::get('/play',                [PlayController::class, 'show'])->name('play');
Route::get('/play/share/{code}',   [PlayController::class, 'showByCode'])->name('play.code');
Route::get('/play/{board}',        [PlayController::class, 'show'])->name('play.board');

// Board CRUD (auth required)
Route::prefix('boards')->name('boards.')->middleware(['auth', 'verified'])->group(function () {
    Route::get('/',                              [BoardController::class, 'index'])->name('index');
    Route::get('/create',                        [BoardController::class, 'create'])->name('create');
    Route::post('/',                             [BoardController::class, 'store'])->name('store');
    Route::get('/{board}/edit',                  [BoardController::class, 'edit'])->name('edit');
    Route::patch('/{board}',                     [BoardController::class, 'update'])->name('update');
    Route::delete('/{board}',                    [BoardController::class, 'destroy'])->name('destroy');

    // Square content
    Route::patch('/{board}/squares/{position}',  [BoardController::class, 'updateSquare'])->name('squares.update');
    // Square create/delete (canvas editor)
    Route::post('/{board}/squares',              [BoardController::class, 'storeSquare'])->name('squares.store');
    Route::delete('/{board}/squares/{position}', [BoardController::class, 'destroySquare'])->name('squares.destroy');
    // Bulk grid positions
    Route::patch('/{board}/squares',             [BoardController::class, 'bulkUpdateSquares'])->name('squares.bulk');
    // Canvas size
    Route::patch('/{board}/canvas',              [BoardController::class, 'updateCanvas'])->name('canvas.update');
    // Path data
    Route::patch('/{board}/path',                [BoardController::class, 'updatePath'])->name('path.update');
    // Apply preset
    Route::post('/{board}/preset',               [BoardController::class, 'applyPreset'])->name('preset');
});

// Board templates (公開預覽)
Route::get('/templates',            [BoardController::class, 'templates'])->name('boards.templates');
Route::get('/templates/{board}',    [BoardController::class, 'templatePreview'])->name('boards.template.preview');
Route::post('/templates/{board}/clone', [BoardController::class, 'cloneTemplate'])
    ->name('boards.template.clone')
    ->middleware(['auth', 'verified']);

// Premium membership
Route::prefix('premium')->name('premium.')->group(function () {
    Route::get('/',         [PremiumController::class, 'index'])->name('index');
    Route::post('/checkout',[PremiumController::class, 'checkout'])->name('checkout')->middleware('auth');
    Route::post('/callback',[PremiumController::class, 'callback'])->name('callback')->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
    Route::match(['get', 'post'], '/result', [PremiumController::class, 'result'])
        ->name('result')
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
});
