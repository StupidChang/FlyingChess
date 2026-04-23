# Laravel Debugger Memory

## Project: FlyingChessOnline (情侶飛行棋)

### Key Architecture
- Laravel 12, PHP 8.2, SQLite, Blade SSR (no SPA)
- Auth: session-based, no Breeze/Jetstream scaffolding — custom AuthController
- Games: session-based (no auth), GameController with {code} param
- Boards: auth + verified middleware required

### Route Naming Conventions
- `games.*` — Flying Chess routes (lobby, show, create, join, start, roll, move, state)
- `boards.*` — Board CRUD (auth + verified)
- `play`, `play.board`, `play.code` — Custom board play (public)
- `legal.privacy`, `legal.terms` — Legal pages (LegalController)
- `verification.notice`, `verification.verify`, `verification.send` — Email verification
- `password.request`, `password.email`, `password.reset`, `password.update` — Password reset (guest only, PasswordResetController)

### Auth Patterns
- RateLimiter key format: `login:{ip}` (5/min), `register:{ip}` (3/min)
- Register flow: create user -> sendEmailVerificationNotification() -> redirect to verification.notice (NOT auto-login)
- User model implements MustVerifyEmail
- boards/* protected by ['auth', 'verified'] middleware

### Email Verification Routes
- Defined inline in web.php (not via Auth::routes())
- verification.verify uses `signed` + `auth` middleware
- EmailVerificationRequest handles the fulfillment automatically

### SEO / Layout Conventions
- Layout: resources/views/layouts/app.blade.php
- @yield('meta_description'), @yield('canonical'), @yield('og_title'), @yield('og_description')
- canonical defaults to url()->current()
- No emoji anywhere in views (M2-A requirement — all replaced with Heroicons SVG inline)

### CSS Architecture
- Global styles: public/css/app.css (hand-written, NOT a Vite build output)
- resources/css/app.css is Tailwind v4 entry (only used for Tailwind utility classes if any)
- Game styles: public/css/game.css, public/css/board.css
- SVG icons: Heroicons solid, class="w-5 h-5 inline-block", always inline (no sprite/img)

### Age Gate
- resources/views/partials/age-gate.blade.php
- Uses localStorage('age_verified') to persist consent
- Inline JS only, no framework dependency
- Included via @include in app.blade.php before </body>

### Legal Pages
- Controller: app/Http/Controllers/LegalController.php
- Views: resources/views/legal/privacy.blade.php, legal/terms.blade.php
- Routes: /privacy, /terms (no auth required, public)

### Common Pitfalls Observed
- Original web.php had NO games routes — GameController existed but was unreachable
- User model had MustVerifyEmail commented out — email_verified_at existed in DB but was unused
- Register() was auto-logging in user without verification
- `Illuminate\Support\Str` imported in AuthController but not used (RateLimiter doesn't need it — safe to keep or remove)
