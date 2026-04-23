# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**情侶飛行棋 (Couple Flying Chess Online)** — A Laravel 12 web application for playing a customizable board game online. It has two distinct game systems:

1. **Flying Chess** (`/games/*`) — A classic Ludo/Parcheesi-style game with 4 colored pieces per player, dice rolling, capture mechanics, and bot AI. Session-based multiplayer (no auth required).
2. **Custom Board Play** (`/play/*`) — A turn-based board game using custom boards with editable squares (text, color categories, fly-to shortcuts). Supports 1–2 players via browser.

Users can register/login to create and edit custom boards in a canvas editor (`/boards/*`).

## Commands

### Local Development
```bash
composer run dev        # Starts all services concurrently: Laravel server, queue, log watcher, Vite
```
Or individually:
```bash
php artisan serve
npm run dev
php artisan queue:listen --tries=1 --timeout=0
```

### Setup (first time)
```bash
composer run setup      # install, .env, key:generate, migrate, npm install, build
```

### Tests
```bash
composer run test       # Clears config cache then runs PHPUnit
php artisan test --filter=TestClassName   # Run a single test class
php artisan test --filter="test method name"  # Run a single test method
```

### Linting / Formatting
```bash
./vendor/bin/pint       # Laravel Pint (PHP code style fixer)
```

### Database
```bash
php artisan migrate
php artisan db:seed     # Seeds default board via BoardSeeder
```

### Docker (Production)
```bash
# Copy and configure .env.docker.example → .env, then:
docker compose up -d
# Generate APP_KEY for fresh deploy:
docker run --rm flying-chess-online php artisan key:generate --show
```

## Architecture

### Tech Stack
- **Backend**: Laravel 12, PHP 8.2, SQLite (default DB)
- **Frontend**: Blade templates, Tailwind CSS v4 (via `@tailwindcss/vite`), vanilla JS
- **Build**: Vite 7 with `laravel-vite-plugin`
- **Queue/Cache**: Database driver (local), File driver (Docker)

### Key Models & Relationships
- `User` → `hasMany` `Board`
- `Board` → `hasMany` `BoardSquare` (ordered by `position`)
- `Game` → `hasMany` `GamePlayer`

**Board fields of note:**
- `path_data` (JSON): `{ all: [pos...], male: [...], female: [...] }` — defines play order. `resolvedPath(gender)` returns the effective path.
- `share_code`: Auto-generated 8-char uppercase code for public URL sharing.
- `canvas_rows`/`canvas_cols`: Grid dimensions for the canvas editor.
- `is_default`: One board is flagged as the default for `/play` and as the clone source when creating new boards.

**BoardSquare fields:**
- `position`: Logical sequence index (used in `path_data`)
- `grid_row`/`grid_col`: Visual position in the canvas grid
- `color`: Category (`action`, `drink`, `dare`, `truth`, `strip`, `move`, `normal`, `start`, `end`, `male`, `female`)
- `fly_to`: Optional — landing here teleports player to this position

### GameService (Flying Chess Logic)
`app/Services/GameService.php` is the core of the Flying Chess game. It encodes:
- A **52-square main track** (clockwise, absolute indices 0–51 stored as `[row, col]`)
- Per-color **relative progress** (0 = home, 1–52 = main track, 53–57 = safe lane, 58 = finished)
- `relToAbs(color, relPos)` converts relative → absolute track index for collision detection
- **Safe squares** (stars) at indices `[0,8,13,21,26,34,39,47]` — cannot be captured
- **Bot AI** (`chooseBotMove`): greedy, priority: capture > enter safe lane > farthest piece
- Game state is stored as JSON in `games.game_state`

### Controllers
- `GameController` — Flying Chess CRUD + JSON API for roll/move/state polling. After human actions, automatically runs `executePendingBotTurns()`.
- `BoardController` — Full CRUD for boards + JSON API endpoints for the canvas editor (squares CRUD, bulk grid positions, path saving, canvas resize, preset apply).
- `PlayController` — Renders the custom board play view. Resolves board by ID, share code, or falls back to default.
- `AuthController` — Simple session auth (register/login/logout).
- `HomeController` — Landing page.

### Routing Structure
- `/` — Home
- `/login`, `/register` — Guest-only auth
- `/games/*` — Flying Chess (no auth required; session-based player identity)
- `/play`, `/play/{board}`, `/play/share/{code}` — Custom board play (public)
- `/boards/*` — Board CRUD (auth required)

### Frontend JS
- `resources/js/app.js` — Entry point (bootstraps Axios, sets CSRF header)
- Game and board editor logic is inline in the respective Blade views (`resources/views/games/show.blade.php`, `resources/views/boards/edit.blade.php`)

### Docker Setup
Single-container image: PHP-FPM + Nginx + Supervisor on Alpine. The entrypoint runs migrations and seeds on start. SQLite database and storage are persisted via named Docker volumes.

## Core principles（Claude 工作守則）
- 這個專案以 **純 Laravel + Blade + SSR** 為主，優先維持搜尋引擎可讀性與穩定索引。
- 除非使用者明確要求，**不要主動把頁面改成 SPA、Nuxt、React 或前後端完全分離**。
- 內容頁、規則頁、教學頁、FAQ、分類頁、標籤頁，都要優先考慮 SEO 結構。
- 先做最小可行改動，避免大範圍重構。
- 若改動會影響 routing、canonical、meta、schema、indexability，先說明風險。

## SEO rules
- 所有重要內容頁應檢查：title、meta description、H1、canonical、Open Graph、robots。
- 新增 landing page 時，優先補：unique title、unique meta description、單一主 H1、FAQ 區塊、合理 internal links。
- 若是同主題多頁，先考慮是否會 keyword cannibalization。
- 分頁、過濾頁、搜尋頁若有索引風險，要主動提醒。

## Safety and moderation rules
- 本專案屬成人向內容站，任何使用者可輸入欄位都需要考慮審核與風險控管。
- 設計暱稱、房名、留言、站內文案、SEO 文字時，優先考慮違規字詞、侮辱、仇恨、未成年暗示、非法交易。
- 對外公開頁面內容，偏向保守且可控。

## Working style
- 先簡述計畫，再修改。
- 完成後回報：改了哪些檔案、為什麼這樣改、可能影響、建議如何驗證。
- 如果資訊不足，先列出需要檢查的檔案，不要瞎猜。

## Available Skills & Agents
本專案已設定以下工具，可直接在 Claude Code 中使用：

### Skills（輸入 `/技能名稱` 呼叫）
- `/bug-triage` — 分析 Laravel 錯誤、找 root cause
- `/laravel-seo-implement` — 實作 SEO 內容頁或補強 meta
- `/seo-landing-page` — 規劃 SEO landing page 結構
- `/keyword-cluster-plan` — 關鍵字分群與內容樹規劃
- `/content-moderation` — 設計內容審核規則
- `/conversion-funnel-review` — 分析轉換漏斗流失點

### Agents（輸入 `@agent-名稱 任務描述` 呼叫）
- `@laravel-debugger` — Laravel 除錯專家
- `@seo-content-architect` — SEO 架構與內容樹專家
- `@compliance-reviewer` — 成人向站點的內容風險審查
