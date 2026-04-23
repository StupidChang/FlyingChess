# SEO Content Architect — Project Memory

## 站內結構規律

### Layout 支援的 SEO slots（已確認）
- `@yield('title', '情侶飛行棋 — 成人趣味桌遊')` — 頁面標題
- `@yield('meta_description', ...)` — description meta
- `@yield('robots', 'index,follow')` — robots meta（可被子頁 override）
- `@yield('canonical', url()->current())` — canonical（預設為當前 URL）
- `@yield('og_title', config('app.name'))` — OG title
- `@yield('og_description', ...)` — OG description
- `@yield('styles')` — 頁面專屬 CSS
- `@yield('scripts')` — 頁面專屬 JS（首頁等用此）
- `@stack('scripts')` — push 式腳本注入（layout 底部，已新增）

### 各頁面現況（截至 2026-04-20 M2 完成後）

| 頁面 | 路由 | title | meta_desc | canonical | robots | OG |
|------|------|-------|-----------|-----------|--------|-----|
| home.blade.php | / | 已設 | 已設 | 已設 | 預設 index | 已設 |
| games/lobby.blade.php | /games | 已修正 | 已設 | 已設 | 預設 index | 已設 |
| games/show.blade.php | /games/{code} | 已設（動態） | 已設（動態） | 預設 current | 預設 index | 未設 |
| play/show.blade.php | /play/{board} | 已修正 | 已設 | current | 預設 index | 已設 |
| legal/privacy.blade.php | /privacy | 已設 | 已設 | 已設 | 預設 index | 未設 |
| auth/login.blade.php | /login | 已設 | 無（無需） | 已設 | noindex,nofollow | 無 |
| auth/register.blade.php | /register | 已設 | 無（無需） | 已設 | noindex,nofollow | 無 |

### 重要發現
- `games/lobby.blade.php` 原本用 `@section('description',...)` 而非 `@section('meta_description',...)` — 已修正
- 首頁 `@section('scripts')` 而非 `@push('scripts')`，兩種方式都可用，但不可混用
- `/boards/*` 全為 auth+verified 路由，已在 robots.txt Disallow
- `/email/` 為 email verification 路由，已在 robots.txt Disallow
- `games/show.blade.php`（遊戲房間頁）每局 URL 不同，屬於動態 session 頁面，SEO 價值低，但目前未加 noindex — 未來可考慮加

### FAQ 設計原則
- 使用原生 HTML `<details><summary>` 結構，無需 JS
- 第一個 FAQ 預設 open
- FAQ section 用 `<h2>常見問題</h2>` 作標題
- 每個問答有 2-3 句實質答案（非空洞句子）

### robots.txt 封鎖清單
已封鎖：`/boards/`, `/email/`, `/admin/`, `/forgot-password`, `/reset-password/`

### Sitemap 設計
- 路由：`/sitemap.xml`，Controller：`SitemapController`
- 動態包含所有 `share_code != null` 的棋盤分享頁
- 靜態頁：`/`、`/play`、`/games`、`/privacy`、`/terms`
- **部署後務必替換 `robots.txt` 中的 `https://yourdomain.com/sitemap.xml`**

### JSON-LD（首頁）
- type: WebSite，包含 potentialAction SearchAction 指向 /play
- 放在 `@section('scripts')` 內（非 @push）

## 命名風格
- title 格式：`功能名稱 — 情侶飛行棋`
- meta_description：包含關鍵動作詞（免費、線上、自訂、雙人）
- 繁體中文，全程無 emoji

## 待辦（部署後處理）
- 替換 robots.txt Sitemap 行的實際網域
- 提交 sitemap.xml 至 Google Search Console
- 為 `games/show.blade.php` 評估是否加 noindex（每局動態 URL）
- 為 `legal/terms.blade.php` 補齊 OG meta
