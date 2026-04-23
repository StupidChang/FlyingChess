# Project Context

## Tech stack & constraints
- Language/runtime: PHP 8.2, Node.js 24.14.0
- Frontend: Blade templates, Tailwind CSS v4 (via @tailwindcss/vite), vanilla JS, Axios
- Framework: Laravel 12
- DB: SQLite (default), Database queue/cache driver (local), File driver (Docker)
- Build: Vite 7 with laravel-vite-plugin
- Testing: PHPUnit 11, Mockery, Laravel Pint (code style)
- Deployment: Single-container Docker (PHP-FPM + Nginx + Supervisor on Alpine)

## Naming & style rules
- API responses: JSON (game state polling, board editor AJAX endpoints)
- Class/file naming: Laravel PSR-4 conventions (PascalCase models, controllers; snake_case DB columns)
- DB columns: snake_case (e.g. share_code, path_data, grid_row, grid_col, fly_to, is_default, canvas_rows, canvas_cols)
- Routes: kebab-case URLs, dot-notation route names
- Views: Blade templates in resources/views/, organized by feature (games/, boards/, play/, auth/, legal/, layouts/, partials/, sitemap/)

## Minimal-change rule
Only touch lines directly related to the task. This is a pure Laravel + Blade + SSR project — do not introduce SPA frameworks, React, Vue, or frontend routing unless explicitly requested.

## Custom risk keywords
Supplements the universal patterns in /generate scan.

HIGH keywords (file content):
- game_state|game_players: Game state manipulation affects multiplayer integrity
- share_code: Public sharing mechanism, URL exposure risk
- is_default: Default board flag affects all users' fallback experience
- path_data: JSON path definition, incorrect data corrupts game logic

HIGH filename patterns:
- GameService: Core game logic (52-square track, collision detection, bot AI)
- AuthController|PasswordResetController: Authentication and password reset flows

MEDIUM keywords (optional):
- canvas_rows|canvas_cols|grid_row|grid_col: Board editor grid system
- fly_to: Teleport mechanic, can create infinite loops if misconfigured
- relToAbs|chooseBotMove: Core game algorithms

## High-risk files/directories
- app/Services/GameService.php: Core Flying Chess logic (track system, relative→absolute position, safe squares, bot AI, capture mechanics)
- app/Http/Controllers/AuthController.php: Session auth (register/login/logout)
- app/Http/Controllers/PasswordResetController.php: Password reset flow with tokens
- app/Http/Controllers/GameController.php: Game CRUD + JSON API, bot turn execution
- app/Http/Controllers/BoardController.php: Full CRUD + JSON API for canvas editor (squares CRUD, bulk grid, path saving, canvas resize, preset apply)
- database/migrations/: Schema changes affect all existing data
- database/seeders/BoardSeeder.php: Seeds default board — incorrect seed breaks /play fallback
- config/auth.php: Authentication guards and providers
- config/session.php: Session driver configuration
- docker/: Deployment configuration (entrypoint, nginx, supervisord)
- routes/web.php: All routing definitions — changes affect URL structure, SEO, and access control

## Accepted trade-offs
- SQLite as default DB: Acceptable for single-server deployment; not for horizontal scaling
- Session-based game identity (no auth required for Flying Chess): Intentional design for frictionless play
- Inline JS in Blade views (games/show, boards/edit): Accepted — keeps game/editor logic co-located with templates
- No real-time WebSocket: Game uses polling via /state endpoint — acceptable latency for turn-based game
- Adult-oriented content site: All user-input fields require moderation consideration (nicknames, room names, board square text)

## Patterns to flag as P1
- SQL injection in any controller accepting user input (especially BoardController with position/grid params)
- Missing auth middleware on board CRUD routes (boards/* must require auth+verified)
- Game state race conditions (concurrent roll/move requests)
- XSS in board square text content (rendered in play view)
- Broken default board fallback (is_default flag or BoardSeeder issues)
- Path traversal or share_code collision risks
