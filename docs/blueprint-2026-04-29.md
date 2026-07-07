# 情侶飛行棋 5-Theme 實作藍圖

**Generated**: 2026-04-29 by `/generate` (full mode)
**Source prompt**: `.claude/current-prompt.md`
**Target codebase**: Laravel 12 + Blade SSR + SQLite，commit `b640e9a` (branch `fix/docker-build`)

---

## 1. 執行摘要

### 1.1 主題優先順序與依賴關係圖

```
[A] 安全性審計 ──→ [B] Google OAuth
       │
       └────────→ [C] i18n
                    │
[D] 新遊戲評估 (平行，無實作依賴)

[E] 廣告策略 (依賴 A 的安全 headers + C 的 locale routing)
```

#### 章節獨立性說明（重要）
本藍圖各章節**並非完全獨立**，而是「**有依賴關係但子 prompt 種子已明文宣告**」：
- 每個子 prompt 種子內的「前置假設」段落已寫明它需要前置主題完成什麼
- 實作者只要按照階段交付計畫順序執行，就能保證依賴
- 例外：D（新遊戲評估）為純文件產出，無實作依賴，可任何時點執行

依賴細節（編號對齊 2.2 章節 PR 清單）：
- **B 依賴 A** 的 PR-A-03（session 加密）。PR-A-04 僅針對金流 `/premium/result`，**不適用**於 Google OAuth callback；OAuth callback 的 CSRF 防護依賴 Socialite `state` 參數與 session（既有機制，不需自製 token）
- **C 依賴 A** 的 middleware 棧已穩定（避免 locale middleware 與 PR-A-01～A-02 的 age-gate 修復衝突）
- **E 依賴 A** 的 PR-A-06（CSP headers，廣告 domain 需白名單）+ PR-A-07（HTTP security headers）+ **C** 的 prefix routing（廣告版位需依 locale 切換）

### 1.2 階段交付物 1 句話摘要

| 階段 | 期程 | 交付物 |
|---|---|---|
| 1 | W1–W2 | 安全審計報告（≥10 項風險 + P0/P1/P2 分級）+ 所有 P0 修復 PR 合併 |
| 2 | W3   | Google OAuth 上線：登入註冊頁多一個按鈕 + 帳號合併規則 |
| 3 | W4–W6 | i18n MVP：4 語系 + 既有公開頁 / 帳號頁 / 遊戲頁完成翻譯 + SEO meta |
| 4 | W7   | 新遊戲規格書（前 2 名 D-02 共同清單 + D-05 時間膠囊）+ 開發週期估算，**不含 MVP 程式碼** |
| 5 | W8+  | 廣告 A/B 測試（feature flag 切版位、CLS/LCP 監控） |

### 1.3 整體預估週期

**8 週** MVP 全主題完成；廣告階段視流量延後啟動。

---

## 2. 主題 A — 安全性審計

### 2.1 審計報告

> 審計範圍：`app/Http/Controllers/`、`app/Http/Middleware/`、`bootstrap/app.php`、`routes/web.php`、`config/session.php`、`config/auth.php`、`docker/`、所有 Blade `{!! !!}` 與前端 `innerHTML` 使用點

#### P0（立即修復，存在生產利用風險）

**A-P0-01　AgeVerification middleware 對所有非 GET/HEAD 請求放行**
- **線索**：`app/Http/Middleware/AgeVerification.php:81` — `if (!$request->isMethod('GET') && !$request->isMethod('HEAD')) { return $next($request); }`
- **影響面**：所有 POST/PUT/PATCH/DELETE 端點（包含註冊、登入、遊戲操作、boards CRUD）完全跳過年齡驗證；未滿 18 歲使用者可直接呼叫 API 互動
- **利用情境**：未過 age-gate 即可 POST `/register`、`/games/{code}/roll`；對成人站點合規性是直接違規
- **修復**：移除這條捷徑；對所有路由都檢查 cookie。如需保留註冊登入未過 age-gate 也能用，把 `login`、`register`、`forgot-password`、`reset-password` 加進 `WHITELISTED_PATHS`（注意：`POST /age-verify` 已由 `AgeVerification.php:75` 的特殊處理放行，不需加入白名單）
- **測試**：未帶 `age_verified` cookie 直接 `curl -X POST /register` 應回 age-gate 渲染（或 403）

> ❌ **已撤銷**：原列為 A-P0-01 的「Premium callback 簽章驗證」於程式碼確認已實作（`app/Http/Controllers/PremiumController.php:69-96` 已驗 `CheckMacValue` + `MerchantID` + `TradeAmt`，且 :103-139 有 `lockForUpdate` + transaction 防並發）。**此項不存在**。

#### P1（需排程修復）

**A-P1-02　AgeVerification User-Agent 信任偽造**
- **線索**：`app/Http/Middleware/AgeVerification.php:64` — `if (stripos($ua, $pattern) !== false) { return $next($request); }`
- **影響面**：任何人改 UA 為 `Googlebot` / `Bingbot` 等即繞過年齡牆
- **修復**：對爬蟲做反向 DNS 校驗（Googlebot 的 reverse DNS 應為 `*.googlebot.com` 或 `*.google.com`）；或改用 IP allowlist；或乾脆不放行爬蟲改用 prerender / SSR snapshot
- **測試**：`curl -A "Googlebot" /` 應回 age-gate（除非通過 reverse DNS 驗證）

**A-P1-03　Session 預設不加密**
- **線索**：`config/session.php` — `'encrypt' => env('SESSION_ENCRYPT', false)`
- **影響面**：成人站 + premium 訂閱資料 + admin 帳號，session payload 未加密；Docker volume / file driver 下可直接讀取 session 內容
- **修復**：`.env` 設 `SESSION_ENCRYPT=true`；同時設 `SESSION_SECURE_COOKIE=true`（需確認 HTTPS 已上）

**A-P1-04　Premium /result 端點移除 CSRF 且接 GET+POST 混用**
- **線索**：`routes/web.php:149-151` — `Route::match(['get','post'], '/result', ...)->withoutMiddleware([VerifyCsrfToken::class])`
- **影響面**：CSRF 解除狀態下，攻擊者可構造跨站連結讓使用者觸發 `result`；若 controller 內有副作用（記錄、重定向、發信）則風險升級
- **修復**：保留 CSRF 解除（金流商 redirect 必要），但 callback 完成後在 cache 寫入一次性 token，result 端驗證該 token 才放行寫入操作；或限制 method 為 GET only（金流商 redirect 通常是 GET）

**A-P1-05　share_code 生成在邊界條件下無 retry 上限**
- **線索**：`app/Models/Board.php:33-37` — `do { $code = strtoupper(Str::random(8)); } while (static::where('share_code', $code)->exists());`
- **實際空間**：`Str::random(8)` 為 8 字元（base62 → 大寫後仍 36^8 ≈ 2.8 × 10^12）
- **影響面**：在 8 字元 alphanumeric 空間下衝突機率極低，但理論上 DB 異常 / SQL 注入 / 高並發 race 可能造成無限迴圈與資源耗盡
- **修復**：加 retry 上限（例如 10 次），達標 throw + log alert
- **out-of-scope 邊界**：字元集 + 長度 + 隨機演算法本身**不可動**；僅補 retry 上限與告警

**A-P1-06　無 Content Security Policy headers**
- **線索**：`docker/nginx/conf.d/*.conf`、`bootstrap/app.php` — 全域均未注入 CSP
- **影響面**：未來若有 XSS 即可執行任意 JS；在成人站背景 + 廣告腳本注入下風險加倍
- **修復**：以 `spatie/laravel-csp` 或自寫 middleware 加上 strict CSP；先用 `Report-Only` 模式跑 1 週收違規回報，再切正式
- **測試**：response header 應出現 `Content-Security-Policy: default-src 'self'; script-src 'self' 'nonce-xxx' ...`

**A-P1-07　Missing HTTP security headers (HSTS / X-Frame-Options / X-Content-Type-Options / Referrer-Policy)**
- **線索**：`docker/nginx/conf.d/*.conf` — nginx 設定未注入這些通用安全 header
- **影響面**：缺 HSTS → 可被 SSL strip；缺 X-Frame-Options → clickjacking；缺 X-Content-Type-Options → MIME sniffing；缺 Referrer-Policy → 來源洩漏
- **修復**：在 nginx server block 加：
  ```
  add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
  add_header X-Frame-Options "DENY" always;
  add_header X-Content-Type-Options "nosniff" always;
  add_header Referrer-Policy "strict-origin-when-cross-origin" always;
  ```

> ❌ **已撤銷**：原列為 A-P1-06 的「dice-game innerHTML XSS」於程式碼確認皆已正確使用 `escHtml()`（`resources/views/dice-game/show.blade.php:189` 與 :261），且該頁不渲染 `wheel_segments.content`。**此項不存在**。

#### P2（建議改善）

**A-P2-08　密碼重設無 IP-level rate limit + email 列舉**
- **線索**：`app/Http/Controllers/PasswordResetController.php:22-44`（`sendResetLink` method）— 整段沒有任何 RateLimiter；對比 `app/Http/Controllers/AuthController.php:21-40` 登入流程有 RateLimiter
- **影響面**：可被列舉「哪些 email 已註冊」（透過 reset link 是否寄出 / 回應訊息差異）
- **修復**：在 `sendResetLink` 加 RateLimiter（IP+email composite key），最大 3 次/小時；無論 email 是否存在都回相同訊息「若該 email 存在，重設信已寄出」

**A-P2-09　路由 GET `/play/share/{code}` 無速率限制（share_code 暴力掃描）**
- **線索**：`routes/web.php:107` — `Route::get('/play/share/{code}', [PlayController::class, 'showByCode'])->name('play.code')`
- **影響面**：可被掃描列舉所有公開棋盤；雖然 8 字元空間 36^8 ≈ 2.8×10^12 暴力不可行，但無速率限制仍可造成 DB 負載
- **修復**：`->middleware('throttle:60,1')`（每 IP 每分鐘 60 次）
- **註**：另一條 `routes/web.php:108` `/play/{board}` 為 Route Model Binding，依 `boards.id`，不屬同類風險

**A-P2-10　Email 驗證 throttle:6,1 與註冊 RateLimiter 不一致**
- **線索**：`routes/web.php:66` 設 `throttle:6,1`；對比 `app/Http/Controllers/AuthController.php:55-63` 註冊用 `RateLimiter::hit($key, 60)`，每 IP 每分鐘 3 次
- **影響面**：註冊上限 3 次/分鐘但驗證信寄送 6 次/分鐘，使用者可透過 verification.send 重複觸發寄信
- **修復**：統一 throttle 政策，把 verification.send 改為 `throttle:3,1`，或改用 `RateLimiter` 按 email 限制

> ❌ **已撤銷**：原列為 A-P2-11 的「.env 暴露風險」，於 `.dockerignore` 第 5–7 行確認已正確排除 `.env`、`.env.local`、`.env.*.local`；`docker-compose.yml` 採 `env_file` 注入而非打包入 image。**此項不存在**。

#### 風險清單一覽表（最終版，10 項真實風險）

| ID | 級別 | 主題 | 線索 |
|---|---|---|---|
| A-P0-01 | P0 | AgeVerification 非 GET 旁路 | app/Http/Middleware/AgeVerification.php:81 |
| A-P1-02 | P1 | UA 偽造繞 age-gate | app/Http/Middleware/AgeVerification.php:64 |
| A-P1-03 | P1 | Session 不加密 | config/session.php SESSION_ENCRYPT |
| A-P1-04 | P1 | Premium /result CSRF | routes/web.php:149-151 |
| A-P1-05 | P1 | share_code retry 無上限 | app/Models/Board.php:33-37 |
| A-P1-06 | P1 | 無 CSP headers | docker/nginx/conf.d/* + bootstrap/app.php |
| A-P1-07 | P1 | Missing security headers (HSTS / X-Frame-Options 等) | docker/nginx/conf.d/* |
| A-P2-08 | P2 | 密碼重設無 rate limit + email 列舉 | app/Http/Controllers/PasswordResetController.php:22-44 |
| A-P2-09 | P2 | share_code 暴力掃描 | routes/web.php:107 |
| A-P2-10 | P2 | 註冊/驗證 throttle 不一致 | routes/web.php:66 vs app/Http/Controllers/AuthController.php:55-63 |

**已撤銷的誤判**（不在清單內，留作審計透明度紀錄）：
- ❌ ~~Premium callback 無簽章~~ — `PremiumController.php:69-96` 已驗 CheckMacValue + MerchantID + amount + lock
- ❌ ~~dice-game innerHTML XSS~~ — `resources/views/dice-game/show.blade.php:189, 261` 已用 `escHtml()`
- ❌ ~~.env 暴露~~ — `.dockerignore:5-7` 已排除，`docker-compose.yml` 用 `env_file` 注入

### 2.2 修復 PR 任務拆解

#### PR-A-01: 移除 AgeVerification 非 GET 旁路（A-P0-01）
- **檔案**：`app/Http/Middleware/AgeVerification.php`
- **變更摘要**：刪除 line 81 的捷徑；把 `login`、`register`、`forgot-password`、`reset-password` 加入 `WHITELISTED_PATHS`（POST `/age-verify` 已由 line 75 特殊處理）
- **測試**：未帶 cookie POST `/games/abc/roll` 應回 age-gate；POST `/login` 應正常通過
- **回滾條件**：發現某些遊戲 API 因 age-gate 中斷而失敗 → 個別端點加進白名單而非整批放行

#### PR-A-02: AgeVerification 爬蟲反向 DNS 校驗（A-P1-02）
- **檔案**：`app/Http/Middleware/AgeVerification.php`
- **變更摘要**：在 line 64 的 UA 比對之後，加 reverse DNS 校驗（`gethostbyaddr` + `gethostbyname` 雙向比對）；快取 1 小時降低查詢成本
- **測試**：偽造 UA + 非 Google IP 應被擋；真實 Googlebot IP + UA 應放行

#### PR-A-03: Session 加密 + Secure cookie（A-P1-03）
- **檔案**：`.env.docker.example`、`docker/entrypoint.sh`（若有 .env runtime 生成）、`docs/deploy.md`
- **變更摘要**：`SESSION_ENCRYPT=true`、`SESSION_SECURE_COOKIE=true`、`SESSION_SAME_SITE=lax`
- **測試**：登入後 dump session payload 應為密文

#### PR-A-04: 修補 Premium /result CSRF（A-P1-04）
- **檔案**：`routes/web.php:149-151`、`app/Http/Controllers/PremiumController.php`
- **變更摘要（鎖定行為，避免歧義）**：
  - 將 `Route::match(['get','post'], '/result', ...)` 改為 **GET only**（金流商導回幾乎都是 GET；藍新 ECPay 預設使用 ClientBackURL 走 GET redirect）
  - 若必須保留 POST 接口（部分金流商有 POST 回跳），則 POST 路徑不經渲染、僅做 callback 結果落地查詢，**且必須驗證 callback 寫入的一次性 token**（callback 完成時在 cache key `payment_result_token:{order_no}` 寫入 30 分鐘 TTL）
  - GET `/premium/result?order_no=X` 為純讀取頁，不寫 DB
- **測試**：
  - `curl -X GET /premium/result?order_no=valid` → 200 顯示結果（read-only）
  - `curl -X POST /premium/result` 不帶 token → 403
  - `curl -X POST /premium/result` 帶有效一次性 token → 200，token 用過即失效

#### PR-A-05: share_code retry 上限與告警（A-P1-05）
- **檔案**：`app/Models/Board.php:33-37`
- **變更摘要**：`do { ... } while (...)` 改為 `for ($i = 0; $i < 10; $i++) { ... }`，達上限 throw `RuntimeException` + `Log::alert`
- **out-of-scope 邊界**：字元集（base62 大寫）與長度（8）不可改
- **測試**：mock DB exists 永遠 true，呼叫 boot creating，應 throw

#### PR-A-06: CSP headers（A-P1-06）
- **檔案**：新增 `app/Http/Middleware/ContentSecurityPolicy.php`、編輯 `bootstrap/app.php`
- **變更摘要**：先以 Report-Only 模式上線，nonce 注入 layout；同步在 `config/csp.php` 列廣告網路 domain 白名單
- **測試**：response header 應出現 `Content-Security-Policy-Report-Only`；非白名單 script 觸發 violation report

#### PR-A-07: HTTP security headers（A-P1-07）
- **檔案**：`docker/nginx/conf.d/default.conf`（或專案實際 nginx 設定路徑）
- **變更摘要**：`add_header` 加 HSTS / X-Frame-Options / X-Content-Type-Options / Referrer-Policy
- **測試**：`curl -I https://yourdomain.com/` response 應出現四個 header

#### PR-A-08 ~ PR-A-10（P2，可分批）
- A-P2-08：PasswordResetController 加 RateLimiter
- A-P2-09：`/play/share/{code}` 加 throttle middleware
- A-P2-10：統一 verification.send 與 register 的節流策略

### → 子 prompt 種子（A — 安全性）

```
[前置假設] 已執行 /generate scan 並完成 2.1 風險清單；專案在 fix/docker-build 分支
[依賴成果] 無（這是基線）
[邊界宣告] 不可動 GameService / share_code 演算法 / 既有 admin 路由行為

任務：實作 PR-A-01 至 PR-A-07 共 7 個 P0/P1 修復，每個獨立分支與 PR；
PR 描述需附 2.1 表中對應 ID、影響面、驗證步驟、回滾計畫。
P2 項目開新 issue 但本批不做。
```

---

## 3. 主題 B — Google OAuth

### 3.1 套件選型

**`laravel/socialite ^5.16`**（支援 Laravel 12，目前 stable）
- 優點：Laravel 官方維護、provider 抽象（Google/GitHub/FB 之後可加）、CSRF state 自動處理
- 不選 Auth0 / Firebase Auth：依賴外部服務 + 月費

### 3.2 schema 變更

`database/migrations/YYYY_MM_DD_add_oauth_fields_to_users_table.php`：

```php
Schema::table('users', function (Blueprint $table) {
    $table->string('google_id')->nullable()->unique()->after('email');
    $table->string('avatar_url')->nullable()->after('google_id');
    $table->string('provider', 20)->default('local')->after('avatar_url');
    // 'local' | 'google' | 'facebook'(future)
    $table->string('locale', 10)->nullable()->after('provider');
});
```

down() 對應 `dropColumn`。

### 3.3 路由 + Controller

`routes/web.php` 新增：

```php
Route::middleware('guest')->group(function () {
    Route::get('/auth/google',          [OAuthController::class, 'redirectToGoogle'])->name('oauth.google');
    Route::get('/auth/google/callback', [OAuthController::class, 'handleGoogleCallback'])->name('oauth.google.callback');
});
```

`app/Http/Controllers/OAuthController.php` pseudo code：

```php
public function redirectToGoogle()
{
    return Socialite::driver('google')
        ->scopes(['email', 'profile'])
        ->redirect();
}

public function handleGoogleCallback(Request $request)
{
    try {
        $googleUser = Socialite::driver('google')->user();
    } catch (\Exception $e) {
        return redirect()->route('login')
            ->withErrors(['oauth' => 'Google 登入失敗，請稍後再試']);
    }

    // 帳號合併決策樹（見 3.4）
    $user = User::where('google_id', $googleUser->getId())->first();

    if (!$user) {
        $user = User::where('email', $googleUser->getEmail())->first();

        if ($user) {
            // 同 email — 必須先確認原帳號 email_verified_at 已驗證才能合併
            if (is_null($user->email_verified_at)) {
                // 拒絕直接合併（防 email pre-registration 攻擊）
                return redirect()->route('login')->withErrors([
                    'oauth' => '此 email 已有未驗證的本地帳號，請先登入該帳號完成 email 驗證後再綁定 Google',
                ]);
            }
            // 安全合併
            $user->update([
                'google_id'  => $googleUser->getId(),
                'avatar_url' => $googleUser->getAvatar(),
                'provider'   => 'google',  // 紀錄「最近一次登入來源」
                // ⚠ is_admin 不動（不繼承也不清除，保留既有值）
                // ⚠ password 不動（讓使用者仍可用密碼登入）
            ]);
        } else {
            // 全新帳號
            $user = User::create([
                'name'       => $googleUser->getName(),
                'email'      => $googleUser->getEmail(),
                'password'   => bcrypt(Str::random(32)),  // dummy；該帳號之後可走「忘記密碼」設定
                'google_id'  => $googleUser->getId(),
                'avatar_url' => $googleUser->getAvatar(),
                'provider'   => 'google',
                'email_verified_at' => now(),  // Google 已驗證 email
                'is_admin'   => false,         // ⚠ 強制 false（OAuth 來源永不為 admin）
            ]);
        }
    }

    Auth::login($user, true);

    // age-gate 檢查（OAuth 也要過）
    if ($request->cookie('age_verified') !== '1') {
        // 導向 age-gate；通過後再回到 intended URL
        return redirect()->route('home');  // home 會被 AgeVerification middleware 攔下渲染 age-gate
    }

    return redirect()->intended(route('home'));
}
```

> 完整語意說明見 3.4 章節下方「provider 欄位語意」。

### 3.4 帳號合併規則決策樹

```
google_id 已存在於 users？
├─ Yes → 直接登入該帳號，更新 avatar_url；provider 維持 'google'
└─ No  → 用 google email 查 users
         ├─ 找到 ──┐
         │        ├─ user.email_verified_at IS NOT NULL → 安全合併
         │        │   寫入 google_id、avatar_url、provider='google'
         │        │   is_admin 不動、password 不動
         │        └─ user.email_verified_at IS NULL（既有未驗證帳號）→ 拒絕直接合併
         │            導向「請先驗證原 email 或登入後再綁定」流程
         │            （避免攻擊者搶先用受害者 email 註冊未驗證帳號 → 等真正擁有 email 的人 OAuth 登入時被合併）
         └─ 沒找到 → 新建：is_admin = false、password = random(32)、
                          provider = 'google'、email_verified_at = now()（Google 已驗證 email）
```

**provider 欄位語意（與 3.3 pseudo 一致，避免歧義）**：
- 此欄位記錄「**最近一次登入來源**」，**不是**「帳號永久標記」
- 純密碼登入過 → `'local'`
- Google 登入過 → `'google'`（即使該帳號同時擁有本地密碼）
- 此欄位**不影響登入流程**；混合帳號（既有密碼又綁定 Google）仍可用任一方式登入
- 僅作 audit / 統計用途

### 3.5 UI 變更

#### `resources/views/auth/login.blade.php`
在 `<form>` 之後加：
```blade
<div class="oauth-divider">— 或 —</div>
<a href="{{ route('oauth.google') }}" class="btn btn-google">
  <svg>...</svg> 使用 Google 登入
</a>
```

#### `resources/views/auth/register.blade.php`
同上，按鈕文字改「使用 Google 註冊」。

### 3.6 風險清單

| 風險 | 緩解 |
|---|---|
| **redirect_uri 偽造** | Google Cloud Console 嚴格設定 redirect_uri 白名單，每個環境獨立 client_id |
| **CSRF 攻擊** | Socialite 自動處理 state；不要關閉 |
| **Token leak** | 不在 URL 帶 access_token；callback 後立即落地 user 資料、丟棄 token |
| **社交工程** | 使用者誤以為 Google 登入 = admin → 透過 3.4 規則嚴格隔離 |
| **帳號劫持（同 email 接管 / pre-registration 攻擊）** | 同 email 合併時，必須是已驗證 `email_verified_at`；若未驗證，導向「請先登入該本地帳號完成 email 驗證後再綁定 Google」流程（與 3.3 / 3.4 一致） |
| **Premium 訂閱跨帳號污染** | 合併 user 時保留原 user 的 premium_expires_at，不從 OAuth 新建覆寫 |

### 3.7 config/services.php 範例

```php
'google' => [
    'client_id'     => env('GOOGLE_CLIENT_ID'),
    'client_secret' => env('GOOGLE_CLIENT_SECRET'),
    'redirect'      => env('GOOGLE_REDIRECT_URI', '/auth/google/callback'),
],
```

`.env` 範例：
```
GOOGLE_CLIENT_ID=xxxxxxx.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=GOCSPX-xxxxxxx
GOOGLE_REDIRECT_URI=https://yourdomain.com/auth/google/callback
```

### → 子 prompt 種子（B — OAuth）

```
[前置假設] 主題 A 的 PR-A-03（session 加密）已合併；HTTPS 已配置（不然 secure cookie 會掛）
[依賴成果] users 表 schema 穩定；CSP 已上 Report-Only 不會擋 Google 登入彈窗
[邊界宣告] 不修改既有 AuthController 的密碼登入流程；OAuth 為平行路徑

任務：composer require laravel/socialite；新增 OAuthController + migration；
按 3.3 / 3.4 / 3.5 / 3.7 落地；對 3.4 決策樹寫 PHPUnit feature tests（同 email 合併、新建、is_admin 保護）。
```

---

## 4. 主題 C — i18n

### 4.1 套件選型比較表

| 套件 | 範圍 | 優點 | 缺點 | 推薦 |
|---|---|---|---|---|
| Laravel 內建 `Lang::get` | 靜態翻譯 | 零依賴；官方支援 | 無 URL prefix 自動處理；無 hreflang helper | 部分使用 |
| `mcamara/laravel-localization` | URL prefix + 靜態 | 完整 URL prefix / hreflang 解決方案；Laravel 12 兼容 | 需要 wrap 路由群組；部分 middleware 順序需調整 | ⭐ 主推 |
| `spatie/laravel-translatable` | DB 內容翻譯 | translatable trait 簡潔；JSON 欄位儲存 | 不處理 URL/SEO 層；需自行做 admin UI | ⭐ DB 翻譯用 |

**最終組合**：`mcamara/laravel-localization`（URL/SEO 層） + `spatie/laravel-translatable`（DB 內容層）。

### 4.2 URL 策略對照表

| URL prefix | App locale | hreflang | 中文標籤 |
|---|---|---|---|
| `/tw` | `zh_TW` | `zh-TW` | 繁體中文 |
| `/cn` | `zh_CN` | `zh-CN` | 簡體中文 |
| `/jp` | `ja`    | `ja`    | 日本語   |
| `/en` | `en`    | `en`    | English |

#### 舊 URL 過渡規則（鎖定）
- `/` → Accept-Language + cookie → **301** → `/{locale}`
- 既有非 prefix URL（`/login`、`/register`、`/privacy`、`/terms`、`/games/*`、`/play/*`、`/admin/*`、`/boards/*`、`/game-hall`、`/truth-dare/*`、`/dice-game`、`/card-game`、`/king-game`、`/wheel-game`、`/templates/*`、`/profile`、`/premium`） → **301 永久重導**至 `/{default_or_cookie_locale}/{path}`，**保留 query string**（fragment 屬瀏覽器 client-side 不送 server，無從保留也不需處理）
- admin 與 boards 仍掛在 prefix 下：`/tw/admin`、`/tw/boards`，prefix 不影響 `EnsureAdmin` / `auth` middleware 判定
- `<link rel="canonical">` 永遠指向自己 locale 版本
- `sitemap.xml` 切成 `sitemap-tw.xml` / `sitemap-cn.xml` / `sitemap-jp.xml` / `sitemap-en.xml`，主 sitemap.xml 為 sitemap index
- 舊 URL 不發 noindex，靠 301 自然交棒
- **例外（不參與 i18n 重導 / 不過 locale middleware）**：
  - `POST /age-verify`（cookie 寫入端點）
  - `/sitemap.xml`、`/robots.txt`
  - `/up`（health check）
  - `/premium/callback`（金流商 webhook，URL 必須穩定）
  - `/premium/result`（金流商 redirect 回跳的 URL，加 prefix 會破壞付款流程；若需多語系顯示結果，由 controller 內讀 cookie/Accept-Language 切換 view）

### 4.3 靜態翻譯檔結構

`lang/{locale}/` 下分檔，每檔 ≥5 keys：

#### `auth.php`
```php
return [
    'login_title'        => '登入',
    'register_title'     => '註冊',
    'forgot_password'    => '忘記密碼？',
    'remember_me'        => '記住我',
    'login_button'       => '登入',
    'register_button'    => '建立帳號',
    'logout'             => '登出',
    'google_login'       => '使用 Google 登入',
    'email_invalid'      => '電子信箱格式錯誤',
    'password_min'       => '密碼至少 8 個字元',
];
```

#### `ui.php`
```php
return [
    'home'           => '首頁',
    'play'           => '開始遊戲',
    'boards'         => '我的棋盤',
    'profile'        => '個人資料',
    'admin'          => '後台',
    'language'       => '語言',
    'menu'           => '選單',
    'close'          => '關閉',
    'confirm'        => '確認',
    'cancel'         => '取消',
];
```

#### `seo.php`
```php
return [
    'home_title'         => '情侶飛行棋 — 線上情侶遊戲平台',
    'home_description'   => '免費線上情侶飛行棋，自訂棋盤，真心話大冒險，輪盤遊戲，雙人對戰',
    'play_title'         => '開始遊戲 — :board',
    'play_description'   => ':board — 線上情侶飛行棋',
    'rules_title'        => '飛行棋玩法規則',
    'rules_description'  => '完整飛行棋規則：擲骰、起飛、安全格、回家、捕捉',
];
```

#### `legal.php`
```php
return [
    'privacy_title'   => '隱私權政策',
    'terms_title'     => '使用條款',
    'age_gate_title'  => '年齡確認',
    'age_gate_text'   => '本站含有成人內容，僅限 18 歲以上使用者瀏覽。',
    'enter_18'        => '我已滿 18 歲，進入',
    'leave'           => '離開',
];
```

#### `games.php`
```php
return [
    'flying_chess'      => '飛行棋',
    'truth_dare'        => '真心話大冒險',
    'wheel_game'        => '輪盤遊戲',
    'card_game'         => '紙牌遊戲',
    'dice_game'         => '骰子遊戲',
    'lobby'             => '遊戲大廳',
    'create_room'       => '建立房間',
    'join_room'         => '加入房間',
    'roll_dice'         => '擲骰子',
    'your_turn'         => '輪到你了',
];
```

#### `play.php`
```php
return [
    'select_board'    => '選擇棋盤',
    'default_board'   => '預設棋盤',
    'share_link'      => '分享連結',
    'copy_link'       => '複製連結',
    'square_text'     => '棋格內容',
    'fly_to'          => '飛向',
];
```

#### `errors.php`
```php
return [
    '403_title'   => '無權限',
    '403_message' => '您沒有權限存取此頁面',
    '404_title'   => '找不到頁面',
    '404_message' => '您要找的頁面不存在',
    '500_title'   => '伺服器錯誤',
    '500_message' => '系統暫時無法回應，請稍後再試',
];
```

實作者需為 `zh_TW`、`zh_CN`、`ja`、`en` 各一份。`zh_TW` 為 master，其餘以 AI 機翻 + 後台校對。

### 4.4 動態 DB 翻譯欄位設計

**選型**：採用 `spatie/laravel-translatable` 的 JSON 欄位方案（不另開 translation table，schema 改動最小）。

#### 對象與 migration 草案

`database/migrations/YYYY_MM_DD_translate_card_text_columns.php`：

```php
public function up(): void
{
    // truth_dare_cards.content
    Schema::table('truth_dare_cards', function (Blueprint $table) {
        $table->json('content_translations')->nullable()->after('content');
    });
    // 將既有 content 灌入 zh_TW 欄位
    DB::table('truth_dare_cards')->orderBy('id')->each(function ($row) {
        DB::table('truth_dare_cards')->where('id', $row->id)->update([
            'content_translations' => json_encode(['zh_TW' => $row->content]),
        ]);
    });

    // board_squares.text
    Schema::table('board_squares', function (Blueprint $table) {
        $table->json('text_translations')->nullable()->after('text');
    });
    DB::table('board_squares')->orderBy('id')->each(function ($row) {
        DB::table('board_squares')->where('id', $row->id)->update([
            'text_translations' => json_encode(['zh_TW' => $row->text]),
        ]);
    });

    // wheel_segments.content
    Schema::table('wheel_segments', function (Blueprint $table) {
        $table->json('content_translations')->nullable()->after('content');
    });
    DB::table('wheel_segments')->orderBy('id')->each(function ($row) {
        DB::table('wheel_segments')->where('id', $row->id)->update([
            'content_translations' => json_encode(['zh_TW' => $row->content]),
        ]);
    });

    // boards.name
    Schema::table('boards', function (Blueprint $table) {
        $table->json('name_translations')->nullable()->after('name');
    });
    DB::table('boards')->orderBy('id')->each(function ($row) {
        DB::table('boards')->where('id', $row->id)->update([
            'name_translations' => json_encode(['zh_TW' => $row->name]),
        ]);
    });

    // 翻譯時間戳（用於後台「待翻譯」標記）
    foreach (['truth_dare_cards', 'board_squares', 'wheel_segments', 'boards'] as $tbl) {
        Schema::table($tbl, function (Blueprint $table) {
            $table->timestamp('machine_translated_at')->nullable();
        });
    }
}

public function down(): void
{
    // dropColumn 對應四個欄位
}
```

#### Eloquent accessor 用法（Spatie translatable）

```php
use Spatie\Translatable\HasTranslations;

class TruthDareCard extends Model
{
    use HasTranslations;
    public $translatable = ['content_translations'];
}

// 使用
$card->setTranslation('content_translations', 'zh_TW', '繁體內容');
$card->setTranslation('content_translations', 'ja', '日本語');
$card->getTranslation('content_translations', app()->getLocale(), fallback: 'zh_TW');
```

**fallback 規則**：缺翻譯時顯示 `zh_TW`；若 `zh_TW` 也缺，顯示 raw key 並 log warning。

### 4.5 後台翻譯編輯介面

#### 新增路由
```php
Route::prefix('admin/translations')->name('admin.translations.')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/',                          [TranslationController::class, 'index'])->name('index');
    Route::get('/{model}/{id}/edit',         [TranslationController::class, 'edit'])->name('edit');
    Route::patch('/{model}/{id}',            [TranslationController::class, 'update'])->name('update');
    Route::post('/{model}/{id}/machine',     [TranslationController::class, 'machineTranslate'])->name('machine');
});
```

`{model}` 走 enum：`card`、`square`、`wheel-segment`、`board`。

#### UI（簡略 wireframe）
```
┌─ 翻譯管理 ──────────────────────────┐
│ [類型 ▼] [語言 ▼] [狀態 ▼] [搜尋] │
├──────────────────────────────────┤
│ ID │ zh-TW │ zh-CN │ ja │ en │     │
│ 1  │  ✅   │  ✅   │ ⚠️  │ ❌ │ 編輯 │
│ 2  │  ✅   │  ⚠️   │ ❌  │ ❌ │ 編輯 │
└──────────────────────────────────┘
✅=人工已校對  ⚠️=機翻未校對  ❌=未翻譯
```

點「編輯」進入 inline form：
```
┌─ 編輯翻譯 #1 ────────────────────┐
│ 繁體中文（master）               │
│ [真心話：今晚最想做什麼？     ] │
│ 簡體中文 [ 機翻 ]                │
│ [真心话：今晚最想做什么？     ] │
│ 日本語 [ 機翻 ]                   │
│ [今夜何をしたい？             ] │
│ English [ 機翻 ]                 │
│ [Truth: What do you want to...] │
│ [標記為已校對] [儲存]             │
└────────────────────────────────┘
```

### 4.6 SEO 影響

#### `resources/views/layouts/app.blade.php` 新增（在 `<head>` 內）：

採用 `mcamara/laravel-localization` 提供的 helper（會自動處理 prefix 替換、避免 `/twlogin` 這種錯誤組合）：

```blade
@foreach (LaravelLocalization::getSupportedLocales() as $localeCode => $properties)
    <link rel="alternate"
          hreflang="{{ $localeCode === 'zh_TW' ? 'zh-TW' : ($localeCode === 'zh_CN' ? 'zh-CN' : $localeCode) }}"
          href="{{ LaravelLocalization::getLocalizedURL($localeCode, null, [], true) }}">
@endforeach
<link rel="alternate" hreflang="x-default"
      href="{{ LaravelLocalization::getLocalizedURL('zh_TW', null, [], true) }}">
<link rel="canonical" href="{{ LaravelLocalization::getLocalizedURL(app()->getLocale(), null, [], true) }}">
```

> ⚠ 不可用 `url('/tw' . request()->path())` 之類自製拼接 — 會產生 `/twlogin` 這類錯誤 URL，且對已 prefix 的頁面會重複 prefix。**一律走 `LaravelLocalization::getLocalizedURL`**。

#### `app/Http/Controllers/SitemapController.php`
拆成 4 個語系子 sitemap，主 `sitemap.xml` 改為 sitemap index 指向四個 child sitemap。

#### `robots.txt`
```
User-agent: *
Disallow: /admin/
Sitemap: https://yourdomain.com/sitemap.xml
```
無需大改，sitemap.xml 已涵蓋 hreflang。

### 4.7 預設語系與 fallback 策略

#### Middleware 註冊（`bootstrap/app.php`）—— **不可全域 prepend，僅作為 alias 供路由群組使用**
```php
$middleware->alias([
    // 新增 i18n 套件提供的 middleware（只註冊 alias，不 prepend 全域）
    'localize'         => \Mcamara\LaravelLocalization\Middleware\LaravelLocalization::class,
    'localizeRedirect' => \Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter::class,
    'localeViewPath'   => \Mcamara\LaravelLocalization\Middleware\LaravelLocalizationViewPath::class,
    // 既有 alias 保留
    'age.verify' => \App\Http\Middleware\AgeVerification::class,
    'admin'      => \App\Http\Middleware\EnsureAdmin::class,
    'premium'    => \App\Http\Middleware\EnsurePremium::class,
]);

// 既有：AgeVerification 全域 append 維持不動
// $middleware->append(\App\Http\Middleware\AgeVerification::class);
// ⚠ 不要全域 prepend localize middleware — 否則 /up、/sitemap.xml、/premium/callback 等 i18n 例外端點也會被處理
```

**順序原則**：locale middleware 只掛在 web group 的 prefix 群組內（見下方 routes/web.php），**不全域註冊**。

**AgeVerification 與 301 過渡的交互（重要）**：

- AgeVerification 仍全域 append（既有行為）。`WHITELISTED_PATHS` 已覆蓋 i18n 例外端點：`privacy`、`terms`、`sitemap.xml`、`robots.txt`、`premium/callback`、`premium/result`、`up`，這些端點**不受 age-gate 阻擋**
- ⚠ **白名單比對必須處理 locale prefix**：
  - 既有 AgeVerification 直接比對 `$request->path()`（如 `'privacy'`），i18n 上線後 URL 會變成 `/tw/privacy`，原 literal match 將失效
  - **PR-A-01 / PR-A-02 修補同時必須加入 locale prefix 正規化邏輯**，例如：
    ```php
    // 取得 path 並去除 locale prefix（zh_TW 對應 tw、ja 對應 jp 等）
    $path = $request->path();
    $localePrefixes = ['tw', 'cn', 'jp', 'en'];
    foreach ($localePrefixes as $prefix) {
        if (str_starts_with($path, "$prefix/")) {
            $path = substr($path, strlen("$prefix/"));
            break;
        } elseif ($path === $prefix) {
            $path = '';
            break;
        }
    }
    // 然後再比對 WHITELISTED_PATHS
    ```
  - 或更簡潔：把 WHITELISTED_PATHS 改成 regex pattern 列表（例如 `'#^([a-z]{2}/)?privacy$#'`），由 middleware 內 preg_match 比對
- 對於非例外的舊 URL（如 `/login`、`/games/*`），首次訪問者流程是：
  1. GET 舊 URL（無 cookie）→ AgeVerification 渲染 age-gate
  2. POST `/age-verify` → 設 cookie → 301 redirect back
  3. GET 舊 URL（有 cookie）→ 通過 AgeVerification → **進入 301 規則** → 跳轉至 `/{locale}/{path}`
- 結論：**age-gate 在 301 之前執行**，首次訪問會有 1 次額外 redirect；穩態（已通過 age-gate）下直接走 301。爬蟲走 reverse DNS 校驗（PR-A-02 修復後）放行，可直接到 301 規則
- 完成 PR-A-01（移除非 GET 旁路）後，所有受保護的 POST 請求都需通過 age-gate（cookie 已設或路徑在白名單內）。Google OAuth callback 為 `GET /auth/google/callback`，正常情況下使用者已過 age-gate；若進站第一個動作就是直接點 OAuth 連結（罕見），callback 後若無 cookie 會導回 home，由 AgeVerification 攔截渲染 age-gate（與 3.3 pseudo 一致）

#### `routes/web.php` 結構調整（落地 4.2 prefix routing）

```php
// === i18n 例外端點：留在 locale group 之外，不過 localize middleware ===
Route::post('/age-verify', function () { /* cookie 寫入 */ })->name('age.verify');
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
Route::get('/robots.txt', function () {
    return response(
        "User-agent: *\nDisallow: /admin/\nSitemap: " . url('/sitemap.xml') . "\n",
        200,
        ['Content-Type' => 'text/plain']
    );
})->name('robots');
// /up（health check）由 bootstrap/app.php withRouting(health: '/up') 自動註冊，無需在這裡

Route::post('/premium/callback', [PremiumController::class, 'callback'])
    ->name('premium.callback')
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

// /premium/result 與 PR-A-04 契約一致：GET only
Route::get('/premium/result', [PremiumController::class, 'result'])
    ->name('premium.result');
// PremiumController@result 內部讀 cookie 'locale' 切換 view，不依 URL prefix
// 若有金流商實際必須使用 POST 回跳，請另開獨立 route，並驗證 callback 寫入的一次性 token（見 PR-A-04）

// === i18n 群組：所有需要多語系的路由 ===
Route::group([
    'prefix' => LaravelLocalization::setLocale(),
    'middleware' => ['localize', 'localizeRedirect', 'localeViewPath'],
], function () {
    Route::get('/', [HomeController::class, 'index'])->name('home');
    Route::get('/privacy', [LegalController::class, 'privacy'])->name('legal.privacy');
    Route::get('/terms', [LegalController::class, 'terms'])->name('legal.terms');
    Route::get('/game-hall', [GameHallController::class, 'index'])->name('game-hall.index');
    Route::get('/premium', [PremiumController::class, 'index'])->name('premium.index');

    Route::middleware('guest')->group(function () {
        Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
        // ... 其餘原本在 routes/web.php:39-50 的 guest 路由全搬進來
    });

    // games / truth-dare / play / boards / templates / profile / admin
    // 全部搬進這個 group，沿用既有路由定義
});

// === 舊 URL 301 過渡（catch-all） ===
// 對非 prefix 的舊 URL（不在 i18n 例外列表中）一律 301 到 /{default_locale}/{path}
// 實作方式：可用 LaravelLocalizationRedirectFilter（套件內建）
// 或自寫 fallback route，注意例外端點要先註冊以免被 catch-all 攔下
```

> ⚠ `LegalController` 目前無 `robots()` method（僅有 `privacy`、`terms`），上方 robots 路由用 inline closure 處理；若要拆出 controller method 需另行新增。

#### Cookie 設定
- 名稱：`locale`
- TTL：365 天
- SameSite：`Lax`
- Secure：true（HTTPS only）
- HttpOnly：false（前端切換需讀取）

#### 偵測順序
1. URL prefix（最高優先）
2. Cookie `locale`
3. `Accept-Language` header（取第一個受支援的）
4. 預設 `zh_TW`

### 4.8 第一階段覆蓋範圍

僅納入「既存」路由與 view：

| 類型 | URL | 翻譯來源 |
|---|---|---|
| 公開 | `/` (HomeController) | seo.php + ui.php |
| 公開 | `/privacy`、`/terms` | legal.php |
| 部分（age-gate view） | `partials/age-gate-full.blade.php` 由 AgeVerification middleware 渲染 | legal.php（`age_gate_*` keys） |
| 公開 | `/game-hall` (game-hall lobby) | games.php + ui.php |
| 帳號 | `/login`、`/register`、`/forgot-password`、`/reset-password` | auth.php |
| 帳號 | `/email/verify`、`/email/verify/{id}/{hash}` | auth.php |
| 飛行棋 | `/games/*`（lobby + room） | games.php + DB(card content_translations) |
| 真心話 | `/truth-dare/*` | games.php + DB(truth_dare_cards content_translations) |
| 單機 | `/dice-game`、`/card-game`、`/king-game`、`/wheel-game` | games.php |
| 自訂棋盤 | `/play`、`/play/{board}`、`/play/share/{code}` | play.php + DB(square text_translations、board name_translations) |
| 模板 | `/templates`、`/templates/{board}` | play.php |
| 帳號 | `/profile` | ui.php + auth.php |
| Premium | `/premium`、`/premium/result` | ui.php + 新增 premium.php |

**第一階段覆蓋的 SEO meta**：以上所有頁面的 title / description / OG / canonical / hreflang 都需翻譯。

**不在第一階段**：
- admin UI（`/admin/*`）— 留待第二階段
- boards 編輯器（`/boards/*` 的 create/edit/canvas 互動 UI）— 編輯介面複雜，待第二階段
- 後台翻譯管理介面（`/admin/translations`）本身的 UI label 為中文 master only

### 4.9 機翻流程建議

1. 新建 / 編輯卡片時，預設只填 `zh_TW`
2. admin 後台 cron 每天掃 `machine_translated_at IS NULL` 的列，呼叫 OpenAI / Google Translate 產出 `zh_CN` / `ja` / `en` 並寫入 `content_translations`、設 `machine_translated_at = now()`
3. UI 顯示 ⚠️ 圖示提示「機翻未校對」
4. admin 點「標記為已校對」→ 清空 `machine_translated_at`，UI 變 ✅
5. 工具：`php artisan translate:auto` artisan command

### → 子 prompt 種子（C — i18n）

```
[前置假設] 主題 A 的 P0 已合併；middleware 註冊順序確認無衝突；HTTPS 已上
[依賴成果] users 已新增 locale 欄位（主題 B 的 migration）— 用於記住使用者偏好
[邊界宣告] 第一階段只翻譯 4.8 列舉的頁面；admin UI 不翻譯；GameService 不可改

任務：composer require mcamara/laravel-localization spatie/laravel-translatable；
按 4.3 建立四語系 lang 檔；按 4.4 跑 migration 並 backfill；
按 4.5 新增 /admin/translations 子路由（不影響既有 admin）；
按 4.6 注入 hreflang + canonical；按 4.7 設定 middleware 順序與 cookie。
驗收：依 4.2 對照表所有 prefix 都可訪問且 SEO meta 正確；舊 URL 全部 301 到 prefix。
```

---

## 5. 主題 D — 新遊戲評估（不含 MVP 實作）

### 5.1 候選玩法評估（5 個）

#### D-01　雙人快問快答（情侶版）
- **玩法**：60 秒內輪流回答對方提出的問題（從 cards 抽取）；答錯/超時則接受懲罰（從 dare deck 抽）。回合制，5 回合定勝負
- **適合情侶的理由**：促進溝通、了解對方喜好；有點刺激但不激烈
- **技術可行性**：純 Blade SSR + 5 秒 polling /state；不需改 GameService
- **SEO 主題**：「情侶快問快答」「情侶問答遊戲」「double talk」（日韓有同類玩法搜尋熱度）
- **開發週期**：3–5 人天
- **整合複雜度**：低 — 用既有 `truth_dare_cards` 表、新增 `quiz_sessions` 表

#### D-02　共同清單（Bucket List Builder）
- **玩法**：雙方輪流出題（旅遊地、想嘗試的事），對方按「想做」「不想做」「可商量」；最後產出兩人都同意的清單
- **適合情侶的理由**：建立共同回憶、討論未來；非常溫馨
- **技術可行性**：純 Blade + 簡單 CRUD；無需 polling
- **SEO 主題**：「情侶共同清單」「bucket list 情侶」「兩人想做的事」
- **開發週期**：2–4 人天
- **整合複雜度**：極低 — 完全獨立，新表 `bucket_lists`、`bucket_items`

#### D-03　升溫塔 (Heat Tower)
- **玩法**：類似疊疊樂的數位版；每抽一張卡需執行對應指令；塔倒前完成最多挑戰勝
- **適合情侶的理由**：循序漸進、節奏可控；夠刺激
- **技術可行性**：需 polling、HTML5 動畫；接 truth_dare_cards 既有 `tier` 欄位（目前只有 `free`/`premium` 兩級）
- **SEO 主題**：「情侶疊疊樂」「heat tower game」「情侶刺激遊戲」
- **開發週期**：6–8 人天（動畫稍重）
- **整合複雜度**：中 — 與 GameService 並存但不互通
- **資料前置條件**：truth_dare_cards 目前 `tier` 僅 `free|premium`（見 `database/migrations/2026_04_23_000005_create_truth_dare_cards_table.php:13-18`）。本遊戲若需要「強度等級」需評估：(a) 直接複用既有 `tier` 但意義變模糊；(b) 新增 `intensity` 欄位（mild/medium/intense）作為 migration。**規格書階段需明確選定方案**

#### D-04　雙人填字（中文 / 日文）
- **玩法**：輪流填空，主題情侶相關（「我們第一次去」「最甜蜜時刻」）；填完用 AI 生成情書
- **適合情侶的理由**：輸出有紀念價值的成果（情書 / 紀念冊）
- **技術可行性**：純表單 + OpenAI API；非 polling
- **SEO 主題**：「情侶情書產生器」「情侶填字」「AI 情書」
- **開發週期**：4–6 人天（加 AI 整合）
- **整合複雜度**：低 — 獨立功能

#### D-05　時間膠囊問答 (Time Capsule)
- **玩法**：今天回答 10 個問題（一年後想成為什麼樣的情侶 / 想去哪裡），系統封存 1 年；明年同一天 email 提醒開封
- **適合情侶的理由**：長期承諾感、回顧成長
- **技術可行性**：表單 + cron + Mail；無即時互動需求
- **SEO 主題**：「情侶時間膠囊」「未來信給情侶」「one year letter」
- **開發週期**：3–5 人天（加 mail queue）
- **整合複雜度**：低 — 獨立 + Laravel Schedule

### 5.2 評分表

公式：**總分 = 情侶適合度 + (5 − 技術複雜度) + SEO 價值**（最高 15）

| 候選 | 情侶適合度 | 技術複雜度 | (5−複雜度) | SEO 價值 | 開發週期(人天) | 總分 |
|---|---|---|---|---|---|---|
| D-02 共同清單     | 5 | 1 | 4 | 4 | 2–4 | **13** |
| D-05 時間膠囊     | 5 | 2 | 3 | 5 | 3–5 | **13** |
| D-01 雙人快問快答 | 4 | 2 | 3 | 4 | 3–5 | **11** |
| D-03 升溫塔       | 5 | 4 | 1 | 5 | 6–8 | **11** |
| D-04 雙人填字     | 4 | 3 | 2 | 5 | 4–6 | **11** |

### 5.3 推薦優先順序

#### 🥇 並列第一：**D-02 共同清單** 與 **D-05 時間膠囊**（總分 13）

兩者開發成本都低（≤5 人天），SEO 價值高，且**完全與既有 GameService 解耦**，可平行開發。優先做這兩個能快速擴張內容廣度。

- **D-02 共同清單**：適合「想長期經營情感」的使用者，留存指標可期；與 spicy 主題形成對照，平衡品牌調性
- **D-05 時間膠囊**：病毒回流（一年後 email），是長期流量飛輪；單篇文章可掛 5+ 長尾關鍵字

#### 🥉 並列第三：D-01、D-03、D-04（總分 11）

- **D-01 雙人快問快答**：與 truth-dare 重疊度高，建議併入 truth-dare 而非獨立
- **D-03 升溫塔**：SEO 天花板雖高（適合度 5 + SEO 5）但複雜度最高（6–8 人天 + 動畫），建議**待 D-02/D-05 上線並驗證流量後再啟動**
- **D-04 雙人填字**：需接 OpenAI API（成本不可預測），建議延後

### 5.4 候選玩法可選範圍（未採用但 backlog）
真心話骰（已被 truth_dare 涵蓋）/ 二選一卡牌（與 D-01 重疊）/ 雙人迷宮 / 密室解謎 / 計時挑戰 / 表情猜題

### → 子 prompt 種子（D — 新遊戲）

```
[前置假設] 5.3 已選定 D-02 共同清單 + D-05 時間膠囊為第一優先（兩者並列 13 分）；
           本 prompt 只交付規格書，不寫程式碼；
           truth_dare_cards 的 tier 結構（free/premium）保持不變
[依賴成果] 無（D 與既有 GameService 完全解耦）
[邊界宣告] 不可改 GameService；不引入 WebSocket；保留 SSR；不新增 tier 值

任務：產出兩份規格書：

【D-02 共同清單規格書】
  1. 完整玩法規則（出題 / 同意 / 商量 / 拒絕，最終清單匯出）
  2. ER 圖（bucket_lists, bucket_items）
  3. 路由設計（/bucket-list/*）
  4. SEO 結構（landing + FAQ + 範例清單頁）
  5. 3 人天開發任務 breakdown

【D-05 時間膠囊規格書】
  1. 完整玩法規則（10 題 + 封存 1 年 + 一年後 mail）
  2. ER 圖（time_capsules, capsule_questions, capsule_answers）
  3. 路由設計（/time-capsule/*）+ Laravel Schedule cron
  4. Mail template 草案（multilingual 對應 i18n）
  5. SEO 結構（landing + FAQ + 「給未來的自己」單篇）
  6. 4 人天開發任務 breakdown

不寫 PHP / Blade 程式碼。D-03 升溫塔與 D-04 雙人填字暫列 backlog，待 D-02/D-05 上線並驗證流量再啟動。
```

---

## 6. 主題 E — 廣告策略（不含正式接入）

### 6.0 既有架構盤點（重要）

專案內**已有 ad adapter 框架**，本章節**不重新發明結構**，僅補完選型與部署策略：

- **`config/ads.php`** 已採 adapter pattern：`adapter` 環境變數可切 `adsense` / `trafficjunky`，每個 adapter 已預留 5 個 slot（home_banner / home_mid / lobby_side / game_end / share）
- **`resources/views/partials/ad-unit.blade.php`** 已存在，作為統一 ad unit 渲染元件
- 本章節要做的：(a) 確認 AdSense 適用性 (b) 補上其他網路 adapter (c) 補 lazy load + feature flag + Premium gating

### 6.1 廣告網路選型

#### 6.1.1 AdSense 適用性（明確說明）

**❌ Google AdSense 對成人主題站不可行。**

- AdSense 政策禁止 adult sexual content 整站投放（Google Publisher Policies, 2024 update）
- 即使本站定義為「情侶 / 升溫」邊界，AdSense 自動審核常將 truth-dare、wheel-game 標籤類視為違規
- 替代：Google **AdSense for Games** 對遊戲類有特殊條款，但對 couple/intimate 仍敏感
- **結論**：`config/ads.php` 內的 `adsense` adapter 保留作為其他主題站可重用，但本站 production 環境應設 `AD_ADAPTER=trafficjunky` 或新增成人友善 adapter

> 來源：Google Publisher Policies — https://support.google.com/publisherpolicies/answer/10502938 （2026-04-29 訪查）

#### 6.1.2 既有與新增 adapter（≥2 個成人友善網路）

##### A. **TrafficJunky**（既有 adapter，已在 `config/ads.php`）
| 項目 | 內容 | 來源 |
|---|---|---|
| 廣告類型 | Banner / Pop-under / Pre-roll / Native | https://www.trafficjunky.com/products |
| 費率 | CPM 範圍因類別差異極大；建議申請後查 dashboard | https://www.trafficjunky.com/publishers |
| 最低提領 | 約 $200（依方案）— **上線前驗證** | 同上 |
| 付款週期 | Net-30 — **上線前驗證** | 同上 |
| 付款方式 | Wire / Paxum | 同上 |
| 台灣可註冊 | 是（國際發行商均可申請） | 同上 |
| 流量門檻 | 建議 ≥ 5000 daily UV 才有顯著營收 | 業界共識 |
| 內容審核 | 較嚴；早期申請可能需追加流量資料 | 同上 |

##### B. **JuicyAds**（建議新增 adapter）
| 項目 | 內容 | 來源 |
|---|---|---|
| 廣告類型 | Banner / Pop-under / Native / Interstitial | https://www.juicyads.com/advertise.php |
| 費率 | CPM 範圍因類別差異 — **上線前由 dashboard 查實際** | https://www.juicyads.com/publisher.php |
| 最低提領 | 依方案 — **上線前驗證** | 同上 |
| 付款週期 | Net-15（標準方案） — **上線前驗證** | 同上 |
| 付款方式 | PayPal / Wire / Paxum / Bitcoin | 同上 |
| 台灣可註冊 | 是 | 同上 |
| 流量門檻 | 無強制下限 | 同上 |
| 內容審核 | 中度嚴格；情侶 / 性教育類通常通過 | 同上 |

##### C. **ExoClick**（候選 adapter）
| 項目 | 內容 | 來源 |
|---|---|---|
| 廣告類型 | Banner / Native / Push / Video / In-page Push | https://www.exoclick.com/ad-formats/ |
| 費率 | CPM 範圍因類別差異 — **上線前由 dashboard 查實際** | https://www.exoclick.com/publishers/ |
| 最低提領 | 依方案 — **上線前驗證** | 同上 |
| 付款週期 | Net-7 / Net-15（依等級） — **上線前驗證** | 同上 |
| 付款方式 | PayPal / Wire / Paxum / Bitcoin / ePayments | 同上 |
| 台灣可註冊 | 是 | 同上 |
| 流量門檻 | 無 | 同上 |
| 內容審核 | 嚴格度中等；自助式平台對新手友善 | 同上 |

> ⚠ **資訊時效**：以上費率與付款條件**僅為 2026-04-29 業界已知範圍**。各網路常調整方案，**接洽前必須親自登入該網路 publisher dashboard 查當下實際數字**。本表的官網 URL 為驗證入口。

### 6.2 版位設計

| 版位 | URL | 衝突風險 |
|---|---|---|
| 首頁 above-fold | `/` 首屏 banner | 低；但拉低 LCP，年齡確認後才載入 |
| 首頁 below-fold | `/` 滾動後 native | 低 |
| 文章/規則頁側欄 | `/games/*`、`/play/*` 規則區塊 | 中；polling 時不影響 |
| 列表頁 inline | `/games`（lobby）卡片間 | 中；遊戲列表需保持可點擊 |
| Interstitial | 進入遊戲前 5 秒倒數 | **高** — 干擾互動，建議 free-tier 才出現，premium 永久關閉 |

### 6.3 UX 影響評估

| 指標 | 預估影響 |
|---|---|
| **CLS** | 廣告 iframe 動態載入易造成 layout shift；解法：保留固定 placeholder 高度 |
| **LCP** | banner 在 above-fold 會直接拖慢 LCP；建議延後或放 below-fold |
| **行動端 viewport** | 360px 寬螢幕只剩 ~280px 給內容；橫幅廣告佔比過大時破版；建議 mobile 用 native 而非 banner |
| **polling race** | game polling /state 每 5 秒，廣告 script 也每 5–30 秒重新請求；**注意不要共用同一個 throttle queue** |

### 6.4 載入策略

```javascript
// resources/js/app.js 加入
window.addEventListener('age-verified', () => {
  // 等 3 秒讓主要內容渲染完成再載入廣告
  setTimeout(() => loadAds(), 3000);
});

function loadAds() {
  const slots = document.querySelectorAll('[data-ad-slot]');
  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        injectAdScript(entry.target);
        observer.unobserve(entry.target);
      }
    });
  }, { rootMargin: '200px' });  // 滾動進視窗前 200px 預載
  slots.forEach(slot => observer.observe(slot));
}
```

### 6.5 ad blocker 處理

- **不顯示反 ad-block 警告**（影響 UX、Google 對使用者體驗有 ranking factor）
- **不破壞主要功能**（遊戲、登入、付費都不依賴廣告腳本）
- 若使用者啟用 ad-blocker → 廣告 iframe 載入失敗 → placeholder 仍保留（以免 CLS）

### 6.6 Feature flag 設計（對齊既有 `config/ads.php` adapter pattern）

**既有 `config/ads.php` 結構**（不可重發明）：
```php
'adapter' => env('AD_ADAPTER', 'adsense'),  // 'adsense' | 'trafficjunky'
'adsense' => [ 'publisher_id' => env(...), 'slot_home_banner' => env(...), ... ],
'trafficjunky' => [ 'site_id' => env(...), 'spot_home_banner' => env(...), ... ],
```

**本藍圖建議擴充**（向後相容，加新 key 不動既有）：

```php
return [
    // 既有
    'adapter' => env('AD_ADAPTER', 'adsense'),
    'adsense' => [...],     // 保留，給之後其他主題站重用
    'trafficjunky' => [...], // 保留

    // 新增 adapter
    'juicyads' => [
        'site_id'         => env('JUICYADS_SITE_ID'),
        'zone_home_banner' => env('JUICYADS_ZONE_HOME_BANNER'),
        'zone_home_mid'    => env('JUICYADS_ZONE_HOME_MID'),
        'zone_lobby_side'  => env('JUICYADS_ZONE_LOBBY_SIDE'),
        'zone_game_end'    => env('JUICYADS_ZONE_GAME_END'),
        'zone_share'       => env('JUICYADS_ZONE_SHARE'),
    ],
    'exoclick' => [
        'site_id'         => env('EXOCLICK_SITE_ID'),
        'zone_home_banner' => env('EXOCLICK_ZONE_HOME_BANNER'),
        // ... 同上 5 個 slot
    ],

    // 新增 feature flag（精細到每個 slot）
    'enabled' => env('ADS_ENABLED', false),
    'slots' => [
        'home_banner' => env('ADS_SLOT_HOME_BANNER', true),
        'home_mid'    => env('ADS_SLOT_HOME_MID', true),
        'lobby_side'  => env('ADS_SLOT_LOBBY_SIDE', true),
        'game_end'    => env('ADS_SLOT_GAME_END', false),  // 預設關，避免干擾遊戲結束 UX
        'share'       => env('ADS_SLOT_SHARE', true),
    ],
];
```

**`.env.example` 新增**：
```
AD_ADAPTER=trafficjunky
ADS_ENABLED=false  # 預設關閉，待 dashboard 接通後開
ADS_SLOT_HOME_BANNER=true
ADS_SLOT_HOME_MID=true
ADS_SLOT_LOBBY_SIDE=true
ADS_SLOT_GAME_END=false
ADS_SLOT_SHARE=true

# JuicyAds
JUICYADS_SITE_ID=
JUICYADS_ZONE_HOME_BANNER=
# ...

# ExoClick
EXOCLICK_SITE_ID=
EXOCLICK_ZONE_HOME_BANNER=
# ...
```

**`resources/views/partials/ad-unit.blade.php`**（**既有元件，介面用 `zone` 不是 `slot`**）擴充支援更多 adapter。建議**直接擴充原檔內的 if/elseif 鏈**而非拆分成多個 partial（與既有架構一致）：

```blade
{{--
  廣告版位元件 — adapter 模式（擴充版）
  用法: @include('partials.ad-unit', ['zone' => 'home_banner'])
  Zones: home_banner, home_mid, lobby_side, game_end, share
--}}
@php
    // 既有 logic 保留
    $showAds = !auth()->check() || !auth()->user()?->isPremium();

    // 新增 feature flag 細粒度控制
    $globallyEnabled = config('ads.enabled', true);
    $zoneEnabled = config("ads.slots.$zone", true);
    $showAds = $showAds && $globallyEnabled && $zoneEnabled;

    $adapter = config('ads.adapter', 'adsense');
    $hasTJ = false; $hasAS = false; $hasJA = false; $hasEX = false;

    if ($showAds) {
        if ($adapter === 'trafficjunky') {
            $siteId = config('ads.trafficjunky.site_id');
            $spotId = config("ads.trafficjunky.spot_{$zone}");
            $hasTJ = $siteId && $spotId;
        } elseif ($adapter === 'juicyads') {
            $jaSiteId = config('ads.juicyads.site_id');
            $jaZoneId = config("ads.juicyads.zone_{$zone}");
            $hasJA = $jaSiteId && $jaZoneId;
        } elseif ($adapter === 'exoclick') {
            $exSiteId = config('ads.exoclick.site_id');
            $exZoneId = config("ads.exoclick.zone_{$zone}");
            $hasEX = $exSiteId && $exZoneId;
        }
        // adsense fallback（既有行為，無條件嘗試）
        $pubId = config('ads.adsense.publisher_id');
        $slotId = config("ads.adsense.slot_{$zone}");
        $hasAS = $pubId && $slotId;
    }
@endphp

@if($showAds && $hasTJ)
    {{-- 既有 trafficjunky 區塊保留 --}}
@elseif($showAds && $hasJA)
    <div class="ad-unit ad-unit--banner" aria-label="廣告" data-zone="{{ $zone }}">
        <ins class="adsbyjuicy" data-adzone="{{ $jaZoneId }}"></ins>
        <script async src="//adserver.juicyads.com/js/jads.js"></script>
    </div>
@elseif($showAds && $hasEX)
    <div class="ad-unit ad-unit--banner" aria-label="廣告" data-zone="{{ $zone }}">
        <ins class="eas6a97888e10" data-zoneid="{{ $exZoneId }}"></ins>
        <script async src="https://a.magsrv.com/ad-provider.js"></script>
    </div>
@elseif($showAds && $hasAS)
    {{-- 既有 adsense 區塊保留 --}}
@endif
```

> 重點：
> - **介面參數仍是 `$zone`**（與既有 12 個 call site 相容，包括 `home.blade.php:30,115,174`、`card-game/show.blade.php:185`、`dice-game/show.blade.php:88` 等）
> - 不拆 partial 子檔（保持單檔架構）
> - Premium gating 沿用既有 `auth()->user()?->isPremium()`
> - 新增 `config('ads.slots.$zone')` 細粒度 feature flag

### → 子 prompt 種子（E — 廣告）

```
[前置假設] CSP headers 已上 Report-Only（PR-A-06）；HTTP security headers 已部署（PR-A-07）；premium 訂閱機制已穩定
[依賴成果] 主題 A 的 PR-A-06（CSP）已知道哪些 ad-network domain 要白名單；PR-A-07（HTTP security headers）已部署
[邊界宣告] 本階段不接「正式」廣告；只做技術整合 + sandbox 測試帳號；
           不拆 partials/ads/* 子目錄，沿用既有單檔 partials/ad-unit.blade.php

任務：
  1. 擴充既有 config/ads.php 加入 juicyads / exoclick adapter（向後相容，不動既有 adsense / trafficjunky 結構）
  2. 擴充 resources/views/partials/ad-unit.blade.php 的 if/elseif 鏈支援新 adapter（介面參數仍是 $zone）
  3. 加入 6.4 IntersectionObserver lazy loading（resources/js/app.js 內）
  4. 測試 JuicyAds 沙盒帳號的 CSP 衝突清單；補進 PR-A-06 的 CSP 白名單
  5. 監控 CLS / LCP（Core Web Vitals）
  6. 不上線正式廣告，僅 sandbox 驗證
```

---

## 7. 階段交付計畫

### 階段 1（W1–W2）：安全審計報告 + P0 修復
- **產出**：審計報告 markdown 已就緒（章節 2.1）；PR-A-01 / PR-A-02 合併到 master
- **驗收標準**：所有 P0 風險在開發環境驗證已修復；CI 通過
- **Rollback 條件**：合併後 24 小時內若 production 出現 5xx > 1%，立刻 revert

### 階段 2（W3）：Google OAuth
- **產出**：3.2 migration 落地；OAuthController 上線；登入頁有 Google 按鈕
- **驗收標準**：Google 登入可成功（dev + prod）；3.4 三條帳號合併路徑都有 PHPUnit 覆蓋；is_admin 保護測試通過
- **Rollback 條件**：連續 10 個 OAuth callback 失敗（5xx）→ 切 feature flag 隱藏按鈕，密碼登入不受影響

### 階段 3（W4–W6）：i18n MVP
- **產出**：4 語系 lang 檔；DB 翻譯欄位 migration；hreflang + canonical；舊 URL 301 到 prefix
- **驗收標準**：4.2 對照表所有 prefix 可訪問；舊 URL 全 301；Google Search Console 收錄四個 sitemap；後台 /admin/translations 可編輯
- **Rollback 條件**：301 規則造成既有書籤 / 外部連結大量 404 → 暫時停用 redirect、保留兩種 URL 共存（會有 SEO 重複內容懲罰，但比 404 好）

### 階段 4（W7）：新遊戲規格書
- **產出**：5.3 推薦的 **D-02 共同清單** + **D-05 時間膠囊** 兩份規格書 + ER 圖 + 路由 + SEO outline
- **驗收標準**：兩份規格書能讓另一位工程師依照書面就獨立開發；D-03 升溫塔與 D-04 雙人填字暫列 backlog
- **Rollback 條件**：N/A（純文件）

### 階段 5（W8+，視營收）：廣告 A/B 測試
- **產出**：6.6 feature flag 框架；6.4 lazy load；JuicyAds sandbox 帳號接通
- **驗收標準**：沙盒帳號顯示測試廣告；CLS < 0.1；LCP 不增加 > 200ms；premium 使用者完全無廣告
- **Rollback 條件**：CLS > 0.25 或 bounce rate 增加 > 10% → 關 feature flag

---

## 8. 風險登記簿

| 階段 | 風險 | 偵測方式 | 觸發回滾條件 | 緩解策略 |
|---|---|---|---|---|
| 1 | P0 修復引入新 bug | CI 測試 + production 5xx 監控 | 5xx > 1% 持續 24h | 每個 PR 獨立分支，回滾單一 commit |
| 1 | CSP Report-Only 收到大量 violation | report-uri 收集端 dashboard | violation > 100/小時 | 不切正式 CSP，先白名單必要 domain |
| 2 | Google OAuth client_id 外洩 | .env 不入 git；image inspect | 檢出 .env 在 image | 立刻 revoke 該 client_id 並重新建立 |
| 2 | 既有 user 因合併規則被誤升級為 admin | 帳號合併 PHPUnit + production audit log | log 出現 OAuth 路徑修改 is_admin | 3.4 規則明確禁止；多一層 service-layer 檢查 |
| 3 | 301 規則影響既有 SEO 排名 | Google Search Console「索引覆蓋率」 | 主要關鍵字排名跌 > 30% 持續 4 週 | 暫時並行兩種 URL；重新提交 sitemap |
| 3 | 機翻品質低落 | 後台「待校對」清單堆積 | 50% 內容仍是機翻 | 雇用兼職譯者 / 招募社群協作翻譯 |
| 4 | D-03 升溫塔過於激烈引使用者投訴 | 客服信箱 + 後台檢舉機制 | 投訴 > 5/週 | 加強 spicy tier 內容審核；增加溫和 tier |
| 5 | 廣告影響 Core Web Vitals | Lighthouse + Real User Monitoring | LCP 跌入 Poor | 立刻關 feature flag；重新調整 lazy load 時機 |
| 5 | 成人廣告網路內容違反站點調性 | 抽查實際出現的廣告 | 出現露骨 / 暴力 / 仇恨內容 | 切換廣告網路或加強 brand safety filter |

---

**藍圖結束**。下一步：實作者依各章節 → 子 prompt 種子，跑單獨的 `/generate <種子>` 進行實作。
