# Project PM Memory — 情侶飛行棋 (FlyingChessOnline)

## Project Tech Stack
- Laravel 12, PHP 8.2, SQLite, Blade SSR, Tailwind CSS v4, Vite 7
- No SPA/React — pure Blade + vanilla JS inline in views

## Key File Paths
- Routes: `routes/web.php`
- Layout: `resources/views/layouts/app.blade.php`
- Home: `resources/views/home.blade.php`
- Auth: `app/Http/Controllers/AuthController.php`
- Models: `app/Models/User.php`, `Board.php`, `BoardSquare.php`, `Game.php`, `GamePlayer.php`
- GameService: `app/Services/GameService.php`
- Ad partial: `resources/views/partials/ad-unit.blade.php`

## Current Auth State (as of 2026-04-20)
- Custom AuthController (NOT Laravel Breeze/Jetstream)
- User model has `email_verified_at` column but `MustVerifyEmail` is commented out
- No password reset flow
- No email verification enforcement
- No rate limiting on login/register
- Registration success message uses emoji (♥) — needs cleanup

## Current Monetization State
- Ad system already scaffolded: supports TrafficJunky + Google AdSense via config
- `partials/ad-unit.blade.php` handles both networks
- No subscription/payment system exists yet
- No `is_premium` flag on User model

## Emoji Usage
- Project rule: NO emojis, use Heroicons or Lucide instead
- Current codebase has many emojis in Blade views — all need replacement

## Games Route Gap
- `/games/*` routes are NOT in `routes/web.php` — GameController lobby/show/join/start/roll/move/state exist but routes are missing from web.php
- Need to verify if they are in a separate file or truly missing

## SEO Baseline
- Layout has title, description, robots meta — basic coverage
- No canonical tags
- No Open Graph tags
- No schema.org markup
- No sitemap.xml

## Database Schema Notes
- `users` table has `email_verified_at` (nullable) — migration exists
- No `is_premium`, `subscribed_at`, `subscription_plan` columns yet
- `boards` table: `user_id` nullable (null = preset/system board)

## Links to Detail Files
- See `roadmap.md` for full milestone plan
