# User Journey Tester Memory

## Project: 情侶飛行棋 (FlyingChessOnline)

### Confirmed Architecture
- Laravel 12, Blade SSR, SQLite, Tailwind v4 via Vite
- Two game systems: Flying Chess (`/games/*`) and Custom Board Play (`/play/*`)
- Auth required only for board editing (`/boards/*`, also requires email verification)
- Session-based player identity for Flying Chess (no auth needed)

### Key Pages & Routes
- `/` — Home (HomeController, passes $presetBoards, $myBoards, $default to view)
- `/games` — Flying Chess lobby
- `/games/{code}` — Game room (show.blade.php)
- `/play` or `/play/{board}` — Custom board play (show.blade.php)
- `/play/share/{code}` — Board by share code
- `/register`, `/login`, `/forgot-password` — Auth pages
- `/email/verify` — Email verification notice (requires auth)
- `/privacy`, `/terms` — Legal pages
- `/robots.txt` — Static file in public/
- `/sitemap.xml` — SitemapController

### Known UX Issues (from 2026-04-20 audit)
See: `ux-audit-2026-04-20.md` for full report.

Critical issues:
1. `robots.txt` sitemap URL hardcoded as `https://yourdomain.com/sitemap.xml` — not updated for production
2. Privacy/Terms pages have placeholder `[請填入聯絡信箱]` not replaced
3. `home.blade.php` includes `@include('partials.ad-unit', ['zone' => 'home_top'])` TWICE (lines 41 and 119)
4. Flying Chess lobby default max_players is 4 — unintuitive for a "couple" game site
5. `play/show.blade.php` Setup Modal shows edit button but links to `boards.edit` which requires auth+verification — guest will get redirect
6. `show.blade.php` (games) uses `@section('description')` not `@section('meta_description')` — meta tag will use default fallback
7. Rate limit is IP-based; login allows 5 attempts per 60s (reasonable), register allows only 3

### Files Confirmed Stable
- `resources/views/layouts/app.blade.php` — layout with age-gate, nav, footer
- `resources/views/partials/age-gate.blade.php` — localStorage-based, shows on every page load if not verified
- `public/robots.txt` — exists but needs domain update before launch
