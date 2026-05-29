# 外部操作清單

> **版本**：2026-04-29
> **目的**：列出所有需要你親自到外部服務操作 / 申請 / 提供 key 的事項。完成後把對應的 key / URL 貼給我，我就可以接著整合。

按依賴順序與優先度排列。打 ⭐ 的是其他主題會依賴的「基礎建設」。

---

## ⭐ 1. 網域（Domain）— 核心建設

### 為什麼需要
- HTTPS 必備（沒網域簽不到正式憑證）
- Google OAuth 不接受 IP 直連，必須是網域
- HSTS / cookie secure 攻擊面降低
- SEO（hreflang、canonical 都需要穩定 URL）

### 建議流程
1. 至 **Cloudflare Registrar** 申請（推薦，無溢價、自帶 DNS + 免費 CDN/SSL）
   - https://dash.cloudflare.com → Domain Registration
2. 建議方向（依先前討論）：
   - 個人 hub：`yourname.dev` / `yourname.io`
   - 飛行棋（成人，建議獨立）：`couplay.fun` / `pairdice.fun` / 你選的名字
3. 註冊完成後到 Cloudflare DNS 加 A record：
   ```
   yourdomain.com    A    43.213.67.146    Proxied (orange cloud)
   www.yourdomain.com  CNAME  yourdomain.com  Proxied
   ```

### 完成後給我
- 網域名稱（例：`couplay.fun`）

---

## ⭐ 2. HTTPS / SSL 憑證 — 基礎建設

### 為什麼需要
- 所有 OAuth 流程都要 HTTPS 才能跑
- HSTS（已部署）、SESSION_SECURE_COOKIE 必須在 HTTPS 才有意義
- Google 排名加分

### 兩種選擇

#### 選擇 A：Cloudflare Proxy（最簡單，推薦）
1. 上面網域已用 Cloudflare 註冊 + 開橘色雲（Proxied）→ Cloudflare 自動發 SSL 給訪客
2. 後台 SSL/TLS → 設定為 **Full** 或 **Flexible**
3. 完全不用在 server 處理憑證

**這個選擇下你不用做任何 server 操作**。

#### 選擇 B：在 server 自簽 Let's Encrypt
1. 確認 AWS Security Group 開 80 + 443
2. 安裝 certbot + nginx plugin（要進容器或 host 處理）
3. 我會幫你寫一個 docker-compose 或 host script 自動跑

**選擇 A 比較推薦**。

### 完成後給我
- 你選哪個（A or B）
- 若選 A：在 Cloudflare 設定的 SSL/TLS 模式

---

## 3. AWS Security Group — 開 port

### 現狀
- 80 port 已開（外部可訪問，但被另一個服務佔用）
- 8080 port **未開**（飛行棋目前掛這裡，外部連不到）
- 443 port **未開**（HTTPS）

### 需要開的 port
| Port | 用途 |
|---|---|
| 80   | HTTP（給 Let's Encrypt 驗證、或重導到 443） |
| 443  | HTTPS（主要服務） |
| 8080 | 暫時用，等 domain + 443 配好後可關掉 |

### 流程
1. AWS Console → EC2 → 你的 instance 的 Security Group
2. Edit Inbound rules → Add rule
   - Type: HTTP, Port: 80, Source: 0.0.0.0/0
   - Type: HTTPS, Port: 443, Source: 0.0.0.0/0
   - Type: Custom TCP, Port: 8080, Source: 0.0.0.0/0（暫時）

### 完成後給我
- 不用給我什麼；自己驗證 `curl -I http://43.213.67.146:8080/` 從外部能連上

---

## ⭐ 4. SMTP / Email 服務 — 影響密碼重設、註冊驗證、時間膠囊

### 為什麼需要
- 註冊驗證信目前可能寄不出去（Laravel 預設 `MAIL_MAILER=log` 只寫 log）
- 密碼重設（PR-A-08 修了 rate limit，但需要實際寄信才完整）
- D-05 時間膠囊一年後寄提醒信

### 推薦選擇

#### 選擇 A：Mailgun（推薦給個人 / 小流量）
1. https://www.mailgun.com/ 註冊（免費 5000 封/月，3 個月後降為 1000/月）
2. Domains → Add New Domain → 填你買的網域
3. Mailgun 會給你 5 筆 DNS record 要加到 Cloudflare
   - SPF（TXT）
   - DKIM（TXT，2 筆）
   - MX（2 筆）
4. 加完 DNS 後在 Mailgun 點「Verify DNS」
5. Domain settings → Sending → SMTP credentials → 拿到：
   - SMTP hostname: `smtp.mailgun.org`
   - SMTP port: 587
   - SMTP username: `postmaster@yourdomain.com`
   - SMTP password: （點 Reset 取得）

#### 選擇 B：Amazon SES（最便宜 / 大流量）
1. 已在 AWS，可直接用
2. AWS Console → SES → Verify domain（流程類似 Mailgun）
3. 沙盒模式只能寄給已驗證 email；申請正式模式需 1–2 天審核
4. SMTP credentials 從 SES → Account dashboard 取

#### 選擇 C：Gmail / Google Workspace（個人最簡單，量小才用）
1. 開啟 2FA → 產生 App Password
2. SMTP: smtp.gmail.com:587, username = 你的 gmail, password = App Password
3. 限制：每天 500 封；若被判定為「商業寄信」可能被封

### 完成後給我
給我 4 個值，我會更新 `.env`：
```
MAIL_MAILER=smtp
MAIL_HOST=（你的 SMTP host）
MAIL_PORT=587
MAIL_USERNAME=（你的 username）
MAIL_PASSWORD=（你的 password）
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="情侶飛行棋"
```

---

## 5. Google OAuth — 影響 B 主題（Google 登入）

### 流程
1. https://console.cloud.google.com/ 用你的 Google 帳號登入
2. 上方下拉選單 → New Project → 名稱「Flying Chess」（或任意）
3. 左側 menu → APIs & Services → OAuth consent screen
   - User type: External
   - 應用程式名稱、support email、developer email 填一填
   - Scopes: 加 `email` + `profile`
   - Test users: 先加你自己的 gmail（測試階段才能登入）
4. Credentials → Create Credentials → OAuth Client ID
   - Application type: Web application
   - Authorized redirect URIs（**必填且要對**）：
     ```
     https://yourdomain.com/auth/google/callback
     http://localhost:8080/auth/google/callback   ← 開發測試用
     ```
5. 拿到 Client ID + Client Secret

### 上線時要做
- OAuth consent screen → 從 Testing 切到 In production（Google 會審核 1–7 天）
- 通過審核後才能讓非 test users 登入

### 完成後給我
```
GOOGLE_CLIENT_ID=xxxxxx.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=GOCSPX-xxxxx
GOOGLE_REDIRECT_URI=https://yourdomain.com/auth/google/callback
```

---

## 6. 廣告網路註冊 — 影響 E 主題（廣告投放）

### ⚠ 前置條件
- 網域已上線
- 流量穩定（建議 ≥ 1000 daily UV 才申請，不然多半被拒）
- 內容已上幾篇 SEO 文章（landing page、規則頁、教學頁）

### 推薦順序

#### A. JuicyAds（門檻最低，先申請）
1. https://www.juicyads.com/ → Sign up as Publisher
2. 加站（Add Site）→ 填網域、流量、內容類別（選 Adult / Couples / Sex Education）
3. 等審核（通常 1–3 天）
4. 通過後 → 後台 Zones → Create Zone → 拿到 zone_id
5. 為每個版位建一個 zone（home_banner / home_mid / lobby_side / game_end / share）

#### B. ExoClick（自助式，介面好）
1. https://www.exoclick.com/ → Sign up as Publisher
2. 流程類似 JuicyAds
3. Zones → Create New Zone → 拿到 zone id

#### C. TrafficJunky（PornHub 集團，要 5000+ UV 比較好過）
- 暫緩，等流量起來再申請

### 完成後給我
每個網路 4 組值（5 個版位 × 1 個 zone_id）：
```
JUICYADS_SITE_ID=
JUICYADS_ZONE_HOME_BANNER=
JUICYADS_ZONE_HOME_MID=
JUICYADS_ZONE_LOBBY_SIDE=
JUICYADS_ZONE_GAME_END=
JUICYADS_ZONE_SHARE=
```

---

## 7. Google Search Console + Analytics — SEO 監控

### Search Console（必裝）
1. https://search.google.com/search-console
2. Add Property → URL prefix → `https://yourdomain.com`
3. 驗證方式選 DNS（在 Cloudflare 加 TXT record）或 HTML file
4. 驗證通過後：
   - Submit sitemap: `https://yourdomain.com/sitemap.xml`
   - 等 1–2 週看「索引覆蓋率」
5. **i18n 上線後**：每個 locale prefix 要分別 Submit Sitemap：
   - `https://yourdomain.com/sitemap-tw.xml`
   - `https://yourdomain.com/sitemap-cn.xml`
   - `https://yourdomain.com/sitemap-jp.xml`
   - `https://yourdomain.com/sitemap-en.xml`

### Google Analytics（建議）
1. https://analytics.google.com/ → Create Property
2. Data Streams → Web → 拿到 Measurement ID（`G-XXXXXXXXXX`）
3. 注意：成人站 GA4 政策模糊，內容判定為「敏感」可能被限制

### 完成後給我
- Search Console 不用給我什麼（你自己看數據）
- GA4：`GTAG_MEASUREMENT_ID=G-XXXXXXXX`（我會嵌進 layout）

---

## 8. 金流（Premium 訂閱）— **目前已有，僅供參考**

### 現狀
- 已整合綠界 ECPay（看 `app/Http/Controllers/PremiumController.php`）
- `.env` 內已有 `ECPAY_*` 設定
- 不需要額外操作

### 若想換金流商
- Stripe（國際，需要美國 / 香港公司）
- Paddle（國際，個人戶可註冊）
- 藍新金流（台灣，個人戶可）

---

## 📋 你動作優先度建議

| # | 動作 | 阻塞什麼 | 估時 |
|---|---|---|---|
| 1 | AWS Security Group 開 80/443/8080 | 一切 | 5 分鐘 |
| 2 | 申請網域 | OAuth、HTTPS、SEO | 30 分鐘 |
| 3 | Cloudflare DNS + Proxy | HTTPS、Email 驗證 | 10 分鐘 |
| 4 | Mailgun / SES 申請 + DNS | 密碼重設、註冊信、時間膠囊 | 30 分鐘 + 等驗證 |
| 5 | Google OAuth Console | B 主題（Google 登入） | 20 分鐘 |
| 6 | Search Console + Analytics | SEO 監控 | 15 分鐘 |
| 7 | 廣告網路註冊 | E 主題（要等流量起來） | 2–7 天等審核 |

**建議今天先做 #1, #2, #3，明天做 #4, #5, #6**。

---

## 🚦 完成後告訴我什麼

把所有拿到的 key / 設定值整理成一段文字貼給我即可，例如：

```
網域：couplay.fun
Cloudflare SSL: Full
SMTP: Mailgun
  MAIL_HOST=smtp.mailgun.org
  MAIL_USERNAME=postmaster@couplay.fun
  MAIL_PASSWORD=xxxxxxx
Google OAuth:
  CLIENT_ID=xxx.apps.googleusercontent.com
  CLIENT_SECRET=GOCSPX-xxx
GA4: G-XXXXXXXX
JuicyAds:
  SITE_ID=12345
  ZONE_HOME_BANNER=67890
  ...
```

我會把這些值寫進 `.env` 並做對應整合（OAuth Controller / Mail config / Ads adapter / GA4 layout 注入），不需要你再自己改設定檔。
