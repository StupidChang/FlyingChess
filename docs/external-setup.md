# 外部操作清單

> **版本**：2026-07-31（原始版本 2026-04-29）
> **目的**：列出所有需要你親自到外部服務操作 / 申請 / 提供 key 的事項。完成後把對應的 key / URL 貼給我，我就可以接著整合。

按依賴順序與優先度排列。打 ⭐ 的是其他主題會依賴的「基礎建設」。

## 現況速覽（2026-07-31）

| 項目 | 狀態 |
|---|---|
| 網域 `pillownight.com` | ✅ 已上線，Cloudflare 代理中 |
| HTTPS（Cloudflare Origin 憑證，2041 到期） | ✅ 已完成，Full (strict) + Always Use HTTPS |
| www → 主網域 301 | ✅ 已完成 |
| 四語系 SEO / GEO（sitemap 60 URL、hreflang、JSON-LD、llms.txt） | ✅ 已完成 |
| AI 檢索爬蟲放行（ChatGPT / Perplexity / Claude） | ✅ 已驗證可取得內容 |
| SMTP（Amazon SES） | ✅ 已完成 — 已實際寄出測試信，見下方 ⚠️ 連接埠陷阱 |
| Cloudflare Email Routing | ✅ 已完成 — 根網域 MX 指向 Cloudflare |
| SES production access | ❌ **未申請** — 仍在 sandbox，除已驗證地址外任何人註冊都收不到驗證信 |
| 廣告（ExoClick） | ✅ 七個 zone 投放中（2026-08-01）— 但 `ADS_TXT_LINES` 仍空、`/ads.txt` 回 404 |
| 金流 | ⛔ **綠界已整份移除、全站沒有任何付款入口**（2026-08-01,刻意的,見下方 § 金流已停用） |
| Google 登入 / GA4 / Search Console | ❌ 三個 env 值未填（Google 登入未填時路由 404、按鈕隱藏，不會壞） |
| HSTS / 限制級標示 / Secure cookie | ✅ 已完成 |
| 猶豫期排除條款與結帳同意存證 | ✅ 已完成 |
| 異地備份 | ❌ **未設定** — 36 份備份全在本機 `/var/backups/sites/`，機器沒了就全沒 |
| 外部監控 / 錯誤監控 | ❌ 未設定 — `/up` 端點現成可用 |

**基礎建設與寄信已完成，剩下的是「有了才有收入」與「出事才知道」的項目。**

### § 金流已停用（2026-08-01）

**綠界的 driver 與 `config/ecpay.php` 已經整份刪掉**，預設 driver 換成
`DisabledGateway`（`isLive()` 永遠 false）。原因有二:綠界的特約商店條款排除
情色類、本站過不了審;而先前留在設定裡的是綠界**公開的測試憑證**,任何人都能
算出合法簽章直接 POST 到 callback 幫自己開通。

現在的狀態是「站上不存在付款入口」,不是「按了會失敗」:

- `/premium` 沒有任何按鈕,只有一行說明文字（disabled 的按鈕還是會被誤觸）
- `premium.checkout` 路由回 **404**（不是 503 —— 這不是暫時故障）
- 付費棋盤範本的卡片只顯示「看廣告解鎖」,升級連結整個不出現
- 所有付款入口都掛在 `$purchaseEnabled`（AppServiceProvider 的 view composer
  問 gateway 的 `isLive()`）,接上新金流後會自己回來,不用逐頁改

### § 金流已可抽換

`app/Support/Payments/` 有 `PaymentGateway` 介面。接 CCBill / SegPay 的步驟是：
寫一個新 class → 加進 `config/payments.php` → 改 `PAYMENT_GATEWAY`。
`PremiumController` 一行都不用動,幣別檢查也會自動改問新 driver 的
`supportedCurrencies()`,付款按鈕與結帳路由也會一起回來。

測試不依賴任何廠商的沙箱憑證:結帳規則的測試自己綁 `Tests\Support\FakeLiveGateway`。

---

## ⭐ 1. 網域（Domain）— ✅ 已完成

**`pillownight.com`**，在 Cloudflare Registrar 註冊，DNS 也在 Cloudflare。

```
pillownight.com        A    104.64.144.208    Proxied（橘雲）
www.pillownight.com    A    104.64.144.208    Proxied（橘雲）
```

`www` 由 nginx 301 導回主網域（見下方第 2 節），所以只有主網域會提供內容。

> **舊版文件寫的 `43.213.67.146` 與 AWS 已不適用** —— 實際主機是 Linode，對外 IP `104.64.144.208`。

---

## ⭐ 2. HTTPS / SSL 憑證 — ✅ 已完成

用的是 **Cloudflare Origin Certificate**（不是 Let's Encrypt）：

| 項目 | 值 |
|---|---|
| 憑證 | `/etc/ssl/cloudflare/origin.pem`（644） |
| 私鑰 | `/etc/ssl/cloudflare/origin.key`（600） |
| 涵蓋 | `pillownight.com`、`*.pillownight.com` |
| 到期 | **2041-07-27**（15 年，不需續簽） |
| nginx TLS 設定 | `/etc/nginx/tls/flyingchess.conf` |
| www 301 | `/etc/nginx/tls/flyingchess-extra.conf` |

### ⚠️ 三件維護時一定要知道的事

**1. TLS 設定刻意放在 `/etc/nginx/tls/` 而不是 vhost 裡。**
`bootstrap.sh` 會用模板重新產生 `sites-available/flyingchess.conf`，寫在裡面會被洗掉。模板用 `include /etc/nginx/tls/${POOL_NAME}.conf*;` 引入（結尾的 `*` 讓檔案不存在時不報錯，所以貼貼那站不受影響）。

**2. 不要對這個網域跑 certbot。**
`certbot --nginx` 會往同一個 server block 再插一組 `listen 443` 與憑證路徑，和現有那組重複，`nginx -t` 直接失敗。`bootstrap.sh` 已加偵測：只要 `/etc/nginx/tls/<pool>.conf` 存在就跳過 certbot。

**3. Cloudflare 的 SSL/TLS 模式必須是 Full (strict)，絕對不能用 Flexible。**
（舊版文件寫「Full 或 Flexible」是錯的。）Flexible 會讓 Cloudflare 用 HTTP 連來源站，配上 Laravel 產生的 https 連結會造成**轉址迴圈**，而且來源站那一段是明文。

### Cloudflare 後台的正確設定

| 設定 | 位置 | 值 |
|---|---|---|
| 加密模式 | SSL/TLS → Overview | **Full (strict)** |
| Always Use HTTPS | SSL/TLS → Edge Certificates | **開啟** |
| Bot Fight Mode | Security → Bots | **關閉** |
| AI Scrapers & Crawlers 封鎖 | Security → Bots | **關閉** |
| Cache Everything | Caching → Cache Rules | **不要建立這種規則** |

後三項的理由：我們刻意在年齡閘白名單放行 OAI-SearchBot、PerplexityBot、Claude-User 等**檢索型** AI 爬蟲，好讓 ChatGPT／Perplexity 回答問題時引用得到本站。Cloudflare 那兩個開關會在更前面就擋掉它們，整套 GEO 設定會失效。而快取 HTML 會讓 A 使用者看到 B 的年齡閘狀態、語系甚至登入狀態。

### 應用程式端的配套（已完成，僅供理解）

`bootstrap/app.php` 設了 `trustProxies`，IP 範圍在 `config/cloudflare.php`（取自 Cloudflare 官方清單，14 筆 v4 + 6 筆 v6）。**沒有這個設定的話**，PHP 會以為連線是 HTTP，canonical / hreflang / sitemap / llms.txt / JSON-LD 的 `@id` 全部輸出 `http://`，而且所有訪客的 IP 都會變成 Cloudflare 的位址（throttle 會把大家算成同一個人）。

刻意**不用 `'*'`**：來源站可以被直接連到，用萬用字元等於任何人繞過 Cloudflare 就能偽造協定標頭。Cloudflare 很少改這份清單但確實會改，改了而沒更新的症狀是「網站看起來正常，但 canonical 變回 http」——沒有錯誤訊息，值得偶爾對一次。

---

## 3. 防火牆 / 對外連接埠 — ✅ 已完成（原標題「AWS Security Group」已不適用）

### ✅ 已完成 —— 但這一節原本的做法已經過時

**實際主機是 Linode，不是 AWS**，防火牆用的是主機上的 `ufw`（`bootstrap.sh` 第 6 步會設），不是 AWS Security Group。80 與 443 都已放行（`ufw allow 'Nginx Full'`），`8080` 從來沒用過。

現況確認：

```bash
sudo ufw status          # 應看到 Nginx Full
ss -lnt | grep -E ':80 |:443 '
```

另外機器上有 fail2ban 在擋 SSH 暴力嘗試，`ufw status` 會看到一長串 REJECT，那是正常的。

---

## ⭐ 4. SMTP / Email 服務 — ✅ 已完成（2026-07-31），但仍在 SES sandbox

### ⚠️ 這台主機的 SMTP 連接埠陷阱（踩過一次，會再踩）

**對外 TCP 25 / 465 / 587 全部被主機商封鎖**，只有 SES 的備用埠 **2587** 通得。
症狀是寄信無聲 timeout、沒有錯誤訊息，非常容易誤判成帳密錯誤或 DNS 問題。

```
MAIL_HOST=email-smtp.us-east-1.amazonaws.com
MAIL_PORT=2587      # ← 不是 587
MAIL_SCHEME=smtp    # ← 不是 tls，Laravel/Symfony 只接受 smtp / smtps
```

`MAIL_SCHEME=tls` 會直接丟 `UnsupportedSchemeException`。2587 是 SES 官方的備用
STARTTLS 埠，加密與功能和 587 完全相同。

驗證連線用：`timeout 8 bash -c "exec 3<>/dev/tcp/email-smtp.us-east-1.amazonaws.com/2587"`

### ⚠️ 還沒做：申請脫離 sandbox

**在核准之前，除了已驗證的地址，任何人註冊都收不到驗證信**，而 `User` 有
`MustVerifyEmail` — 等於註冊流程是斷的，站不能開放註冊。

Account dashboard → Request production access，Mail type 選 **Transactional**，
用途說明要寫明：只寄交易信（註冊驗證、密碼重設、使用者自己填的膠囊提醒）、
不做行銷、網域已完成 DKIM/SPF/DMARC、預估量 <200 封/日。

### 為什麼需要
- 註冊驗證信目前可能寄不出去（Laravel 預設 `MAIL_MAILER=log` 只寫 log）
- 密碼重設（PR-A-08 修了 rate limit，但需要實際寄信才完整）
- D-05 時間膠囊一年後寄提醒信

### 先算清楚:你的量級很小,所以重點不是價格

只有密碼重設、Email 驗證、時間膠囊提醒。假設每天 100 個新註冊,大約 150 封/日
= **4,500 封/月**,在 Amazon SES 上是 **$0.45/月**。

所以「免費 vs 付費」在這個量級不是重點,**重點是哪一家不會因為本站是成人向而把
帳號停掉** —— 寄信一停,使用者就救不回帳號了。

### 推薦選擇

| 服務 | 費用 | 成人站可用性 | 備註 |
|---|---|---|---|
| **Amazon SES**(建議) | $0.10 / 1000 封,無月費 | 條款最寬鬆 | 要申請脫離沙盒(1–2 天) |
| Brevo | 免費 300 封/日 | ⚠️ 條款多半禁成人 | 想先求快可暫用 |
| Resend | 免費 3,000 封/月 | ⚠️ 同上 | 開發體驗最好 |
| MailerSend | 免費 3,000 封/月 | ⚠️ 同上 | |
| ~~Mailgun~~ | **永久免費方案已取消**,約 $15/月起 | | 舊版文件寫的「免費 5000 封/月」已不適用 |
| Gmail App Password | 免費,500 封/日 | 不適合正式用 | 商業寄信可能被封,送達率差 |

> **不建議自架 Postfix**:小型 VPS 的 IP 沒有寄信信譽,密碼重設信會直接進垃圾桶。

#### Amazon SES 流程
1. AWS Console → SES → **Verified identities** → 驗證網域 `pillownight.com`
2. SES 會給幾筆 DNS 記錄(DKIM 為主),加到 Cloudflare
3. **申請脫離沙盒**(Account dashboard → Request production access),沙盒只能寄給已驗證信箱
4. SMTP credentials 從 SES → **SMTP settings** → Create SMTP credentials 取得
   - host 形如 `email-smtp.<region>.amazonaws.com`,port 587

#### Brevo(想先求快時)
1. 註冊 → Senders & Domains → 驗證網域,把 DNS 記錄加到 Cloudflare
2. SMTP & API → 取得 SMTP 帳密
3. 之後換 SES 只要改 `.env` 幾行,程式完全不用動

> ⚠️ 定價與使用條款變動很快(Mailgun 就是例子)。申請前請自己到官網確認當下的
> 免費額度與 AUP,不要照抄這份表。

### ⚠️ 為什麼這項優先度比你想像的高

`.env` 目前**完全沒有 `MAIL_*`**，所以 `MAIL_MAILER` 會落回 Laravel 預設值 `log`——信只寫進 `storage/logs/laravel.log`，**一封都不會真的寄出**。而「忘記密碼」的回應是刻意做成不論成功失敗都顯示「已寄出」的（避免洩漏哪些 Email 有註冊），所以**這個故障完全沒有徵兆**：使用者救不回自己的帳號，你也不會收到任何錯誤。

### 完成後給我
給我這幾個值，我會更新 `.env`：
```
MAIL_MAILER=smtp
MAIL_HOST=（你的 SMTP host）
MAIL_PORT=587
MAIL_USERNAME=（你的 username）
MAIL_PASSWORD=（你的 password）
MAIL_SCHEME=tls
MAIL_FROM_ADDRESS=noreply@pillownight.com
MAIL_FROM_NAME="枕邊遊戲"
```

### 另外：Cloudflare Email Routing（收信）

上面是**寄信**。要能**收信**（`support@pillownight.com`）還要另外設定：

1. Cloudflare → 你的網域 → **Email** → Email Routing
2. 啟用後它會自動加必要的 MX 與 TXT 記錄
3. 建立轉寄規則：`support@pillownight.com` → 你的個人信箱

付費頁顯示的客服信箱來自 `.env` 的 `SUPPORT_EMAIL`（目前是 `support@pillownight.com`）。**在 Email Routing 設好之前，那個信箱是收不到信的**——留空的話頁面會自動不顯示該項，不會留下空欄位。

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
     https://pillownight.com/auth/google/callback
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
GOOGLE_REDIRECT_URI=https://pillownight.com/auth/google/callback
```

---

## 6. 廣告網路 — ✅ 已上線（2026-08-01）

ExoClick 已過審並投放中。網站驗證檔是 `public/31e11d6b2ba10a4e7666452a52016dbd.html`
（**在版控裡，不要刪**：`deploy.sh` 會 `reset --hard`，而 ExoClick 之後會複驗；
另外每個路由都有 `/{locale}` 前綴，根目錄不存在的路徑會 301 到 `/tw` 而不是 404，
所以驗證檔一定要是 `public/` 底下的實體檔案，靠 nginx `try_files` 先攔下來）。

### 目前的 zone

| env | zone id | 尺寸 | 位置 |
|---|---|---|---|
| `EXOCLICK_ZONE_HOME_BANNER` | 5992252 | 300x250 | 首頁首屏下方（窄螢幕） |
| `EXOCLICK_ZONE_HOME_BANNER_DESKTOP` | 5992304 | 900x250 | 同上（≥940px） |
| `EXOCLICK_ZONE_HOME_MID` | 5992270 | 300x250 | 首頁中段（窄螢幕） |
| `EXOCLICK_ZONE_HOME_MID_DESKTOP` | 5992306 | 900x250 | 同上（≥940px） |
| `EXOCLICK_ZONE_LOBBY_SIDE` | 5992276 | 300x250 | 大廳／遊戲設定側欄 |
| `EXOCLICK_ZONE_GAME_END` | 5992278 | 300x250 | 「看廣告解鎖」彈窗 |
| `EXOCLICK_ZONE_SHARE` | 5992280 | 300x250 | 分享頁 |

雙尺寸只有滿版的兩個版位需要；側欄與彈窗容器本來就窄，塞寬版會被裁切。
斷點在 `partials/ad-unit.blade.php`（940px = 900 素材 + 邊距，平板落到窄版）。

### 還沒完成的

- **`ADS_TXT_LINES` 仍是空的 → `/ads.txt` 回 404。** ExoClick 的公開文件沒有 ads.txt
  這一節，後台也不好找；要開 ticket 跟 support 要，記得問「including any reseller
  lines」——通常不只 DIRECT 那一行。**不要自己編 seller id 填進去**，錯的比沒有更糟。
  沒有它不影響投放，只是部分 RTB 需求方不出價。
- **素材分級**：`game_end` 只擋了視訊/約砲/成人影音三類，其餘保留（整組擋掉 Adult
  會讓這個 Adult 分類站台的填充率崩掉，代價不成比例）。實際跑出來的素材要定期自己
  點開看，不能接受的用「封鎖廣告主」逐個處理。目前觀察到首頁常出現 Stripchat 類
  視訊廣告——要不要全帳號擋掉是定位問題，擋了會少賺。
- **「看廣告解鎖」仍是純前端計時**，掛上 banner 也不會驗證真的看完。要真的擋得換成
  有 server-to-server reward callback 的獎勵式廣告。

### 兩週後要看的數據

各 zone 的 eCPM 與填充率。桌機/手機分開的 zone 就是為了這個——確認寬版桌機是否真的
比較賺，以及要不要對 `home_*` 設價格地板（目前五個 zone 都是 Floor 0 + Soft floor）。

---

## 6b. 廣告網路註冊流程（存查）

### ⚠️ 程式只支援三種 adapter

`config/ads.php` 的 `adapter` 只認 **`exoclick`｜`trafficjunky`｜`adsense`**，目前設定是 `exoclick`。
**舊版文件叫你先申請 JuicyAds，但程式沒有 JuicyAds 的 adapter** —— 真的要用得先請我加。除非有特別理由，直接走 ExoClick 比較省事。

`adsense` 那個 adapter 不能用在本站：Google AdSense 政策禁止成人內容，套上去等於送帳號去被停權。它只保留給未來的內容轉型或非成人子站。

### 推薦順序

#### A. ExoClick（目前的預設，先申請這個）
1. https://www.exoclick.com/ → Sign up as Publisher
2. 加站（Sites & Zones）→ 填 `pillownight.com`、流量、內容類別
3. 等審核
4. 通過後 → Zones → Create New Zone，**為五個版位各建一個**：
   `home_banner`／`home_mid`／`lobby_side`／`game_end`／`share`

> **`game_end` 這個 zone 特別重要**：「看廣告解鎖 30 分鐘」的彈窗用的就是它。
> 而那個彈窗會在**一群人圍著一台裝置**的情境下被打開，所以請跟 ExoClick 確認
> 這個 zone 的素材能不能限定分級——突然跳出露骨廣告會直接毀掉場子，比付費牆更傷。
> 若不能限定，建議這個版位單獨改用非成人聯播網。

#### B. TrafficJunky（PornHub 集團，要 5000+ UV 比較好過）
- 暫緩，等流量起來再申請。程式已支援，填 env 即可切換。

### 完成後給我
```
EXOCLICK_ZONE_HOME_BANNER=
EXOCLICK_ZONE_HOME_MID=
EXOCLICK_ZONE_LOBBY_SIDE=
EXOCLICK_ZONE_GAME_END=
EXOCLICK_ZONE_SHARE=
ADS_TXT_LINES=exoclick.com, 你的publisherID, DIRECT
```

> `ADS_TXT_LINES` 沒設的話 `/ads.txt` 會回 **404**（現在就是），聯播網無法驗證你有權販售版位。
> 多行用 `|` 分隔。

---

## 7. Google Search Console + Analytics — SEO 監控

### Search Console（必裝）

1. https://search.google.com/search-console
2. Add Property → **Domain**（推薦，一次涵蓋 www 與所有子網域）→ `pillownight.com`
   - 驗證方式會是 DNS TXT。因為 DNS 就在 Cloudflare，直接在 DNS 頁面加那筆 TXT 即可
   - 若選 URL prefix，則要填 `https://pillownight.com`
3. **只需要提交索引 sitemap 這一個**：
   ```
   https://pillownight.com/sitemap.xml
   ```
   它是 sitemap index，裡面已經列出四個子 sitemap，Google 會自己去抓。四個子檔也可以個別提交，但沒有必要：
   ```
   sitemap-tw.xml / sitemap-en.xml / sitemap-cn.xml / sitemap-jp.xml
   ```
   目前總計 **60 個 URL**（4 語系 × 15 頁）。
4. 提交後手動要求索引這幾頁（每個語系各一次）：
   - `/tw`、`/tw/game-hall`、`/tw/who-most-likely`、`/tw/community`
5. **兩週後回來看這三項**：
   - 「網頁索引」→ 有沒有大量「已檢索但目前尚未建立索引」
   - 「網站體驗」→ Core Web Vitals
   - **國際定位**：確認沒有 hreflang 錯誤（四語系互指是我們自己產的，若報錯多半是某個語系的頁面回了非 200）

### Bing（順手做，成本很低）

Bing Webmaster Tools 支援直接從 Search Console 匯入，五分鐘。值得做的原因是 **ChatGPT 的搜尋結果有一部分來自 Bing 索引**——這跟我們做的 GEO 是同一件事的兩端。

### Google Analytics（建議）

1. https://analytics.google.com/ → Create Property
2. Data Streams → Web → 拿到 Measurement ID（`G-XXXXXXXXXX`）
3. 注意：成人站 GA4 政策模糊，內容判定為「敏感」可能被限制

### 完成後給我
- Search Console 不用給我什麼（你自己看數據）
- **GA4：`GOOGLE_GA4_ID=G-XXXXXXXX`**
  （舊版文件寫 `GTAG_MEASUREMENT_ID` 是錯的，實際讀的是 `config('services.ga4.id')` → `GOOGLE_GA4_ID`）
- Search Console 的 HTML 標籤驗證碼若有用到：`GOOGLE_SITE_VERIFICATION=xxx`

---

## 8. 金流（Premium 訂閱）— ❌ **尚未可收款**

> 舊版文件寫「已整合，不需要額外操作」是**錯的**，以下是實際狀況。

### 現狀
- `.env` **沒有任何 `ECPAY_*`**，全部吃 `config/ecpay.php` 的預設值 → 目前指向 **sandbox**（`payment-stage.ecpay.com.tw`）與綠界的**公開測試商店 `3002607``
- 沒有定期定額。付款是**單筆延展**：`premium_expires_at = max(現有到期日, now) + 方案天數`

### ⚠️ 真正的問題：成人內容過不了綠界

綠界、藍新這類台灣金流的特約商店條款基本都排除情色類。本站有年齡閘、廣告走 ExoClick、meta 也含「同房互換／多P」等字，審核方會歸類為成人。**過不了審是一回事，過了之後被抽查停權更麻煩**——款項可能被凍結。

### 建議方向

| 選項 | 說明 |
|---|---|
| **CCBill / SegPay**（建議） | 成人訂閱的標準選擇，明確接受，訂閱與爭議處理成熟。抽成約 10–15%（台灣金流約 2–3%）。**都是 Merchant of Record**，會代收代繳歐盟／英國 VAT 等數位服務稅——做全球生意時這件事的行政成本遠大於那幾個百分點 |
| Verotel / Epoch / RocketGate | 同類型，備選 |
| 自架 BTCPay（加密貨幣） | 不看內容類型、幾乎零抽成，但台灣接受度低，適合當備援不是主通路 |

**申請前先拿 pre-approval**：帶網址直接問業務「我這個內容型態你們收不收」，不要串完才發現不收。

### 已經備好、切換時只要改設定的部分

- `config/premium.php` 已是多幣別 + 多方案結構。**美元標價已填好**（月 US$7.99 / 年 US$34.99），把 `default_currency` 改成 `USD`（或設 `PREMIUM_CURRENCY=USD`）就全站切換
- 目前預設是 `TWD`，因為設成 USD 的話每次結帳都會被擋下（綠界只收台幣，`PremiumController` 寧可回 503 也不用台幣金額去收美元標價的單）
- 金額一律以**最小單位**存放（US$7.99 → `799`），`payment_orders` 有 `currency` 與 `plan` 欄位
- **單價偏低要先處理**：US$3 等級的單筆會被高風險金流的固定費吃掉近三成。建議主推年票，而且你現有的「單筆延展」模型天生沒有自動續扣的退款爭議問題——那正是成人站被金流終止合約最常見的原因，不要為了做訂閱而放棄這個優勢

---

## 📋 你動作優先度建議（2026-07-31 更新）

1～3 已完成。剩下的依「不做會怎樣」排序：

| # | 動作 | 不做會怎樣 | 估時 |
|---|---|---|---|
| 1 | **SMTP**（Mailgun / SES） | 使用者忘記密碼就永久救不回帳號，而且沒有任何錯誤徵兆 | 30 分鐘 + 等 DNS |
| 2 | **Cloudflare Email Routing** | `support@pillownight.com` 收不到信，等於付費頁上寫著一個死信箱 | 10 分鐘 |
| 3 | **ExoClick zone id + `ADS_TXT_LINES`** | **全站 0 個廣告版位，廣告收入為零**；「看廣告解鎖」也沒有廣告可播 | 看審核 |
| 4 | **Search Console + Bing** | 收錄慢、看不到問題。Bing 順帶影響 ChatGPT 的引用 | 20 分鐘 |
| 5 | **GA4** | 上線第一天量不到任何數據，之後無法回溯 | 15 分鐘 |
| 6 | **Google OAuth** | 少一個註冊管道（非阻塞） | 20 分鐘 |
| 7 | **金流**（CCBill / SegPay） | 收不到錢。要先拿 pre-approval，時程最長，建議現在就去問 | 數天～數週 |

**建議順序：今天 #1 #2（都不用等外部審核），接著 #4 #5，然後同時去跑 #3 和 #7 的申請。**

---

## 🚦 上線首日檢查清單

部署後照著跑一遍。前四項我可以幫你跑，後面幾項要真人操作。

### 自動可驗（跟我說一聲我就跑）

- [ ] `http://` 與 `www` 都 301 收斂到 `https://pillownight.com`，沒有迴圈
- [ ] 四語系的 canonical / hreflang 都是 https 且指向正確主機
- [ ] `sitemap.xml` 列出 4 個子檔、總計 60 個 URL；`robots.txt` 與 `llms.txt` 都帶正式網域
- [ ] 每個遊戲頁的 JSON-LD 可被 `json_decode`，且 `Organization` 的 `@id` **四語系相同**
- [ ] 檢索型 AI 爬蟲（OAI-SearchBot / PerplexityBot / Claude-User）拿得到真實內容，訓練型（GPTBot / ClaudeBot）仍停在年齡閘

### 需要真人操作

- [ ] 用**沒登入的瀏覽器**開首頁 → 看到年齡閘 → 確認後能正常進站
- [ ] 註冊 → 收到驗證信 → 點連結完成驗證（**這一步會直接驗出 SMTP 有沒有設好**）
- [ ] 忘記密碼 → 收到信 → 用連結重設 → 登入
- [ ] 寄一封信到 `support@pillownight.com`，確認轉寄得到
- [ ] 建立一個飛行棋房間，用另一台裝置以房號加入，兩邊都能操作
- [ ] 玩到免費回合上限 → 出現提示 → 按「看廣告解鎖」→ 確認流程走得完
- [ ] 手機實機看一次首頁與任一遊戲頁（版面、年齡閘、廣告版位）
- [ ] 切換四個語系，確認文字、價格幣別、日期格式都正確
- [ ] `/tw/nope` 之類的網址 → 出現站台自己的 404 而不是 Laravel 預設頁

### 上線後一週內

- [ ] Search Console 的「網頁索引」開始有資料，沒有大量 hreflang 錯誤
- [ ] `storage/logs/laravel.log` 沒有反覆出現的例外
- [ ] 後台儀表板看得到流量與註冊數
- [ ] 到 ChatGPT / Perplexity 問「情侶多人遊戲推薦」，看有沒有被引用（通常要更久，但值得建立基準）

---

## 🔧 部署與維護備忘

```bash
# 每次上傳新程式碼後
php artisan optimize:clear
php artisan config:cache && php artisan route:cache && php artisan view:cache
sudo systemctl reload php8.3-fpm      # opcache.validate_timestamps=0，不 reload 看不到改動

# 上線前最後一關
./vendor/bin/pint --test && php artisan test
```

幾個容易踩到的點：

- **改了 Blade 或 PHP 卻沒動靜** → 忘了 reload php-fpm（這台機器關掉了 opcache 時間戳檢查）
- **改了路由卻沒生效** → 忘了重建 route cache
- **nginx 設定被還原** → `sites-available/*.conf` 是 `bootstrap.sh` 由模板產生的。要長久保留的東西放 `/etc/nginx/tls/<pool>.conf`（TLS）或 `<pool>-extra.conf`（額外 server block）
- **改了 `config/premium.php` 的分鐘數或價格** → 四語系的 FAQ 與 `FAQPage` schema 會自動跟著變（用的是 `:price` / `:minutes` 佔位符），不用手改語言檔

---

## 🚦 完成後告訴我什麼

把所有拿到的 key / 設定值整理成一段文字貼給我即可，例如：

```
SMTP: Mailgun
  MAIL_HOST=smtp.mailgun.org
  MAIL_USERNAME=postmaster@pillownight.com
  MAIL_PASSWORD=xxxxxxx
Google OAuth:
  GOOGLE_CLIENT_ID=xxx.apps.googleusercontent.com
  GOOGLE_CLIENT_SECRET=GOCSPX-xxx
GA4: GOOGLE_GA4_ID=G-XXXXXXXX
Search Console: GOOGLE_SITE_VERIFICATION=xxx
ExoClick:
  EXOCLICK_ZONE_HOME_BANNER=12345
  EXOCLICK_ZONE_HOME_MID=...
  EXOCLICK_ZONE_LOBBY_SIDE=...
  EXOCLICK_ZONE_GAME_END=...      ← 「看廣告解鎖」的彈窗用這個版位
  EXOCLICK_ZONE_SHARE=...
  ADS_TXT_LINES=exoclick.com, 123456, DIRECT
```

> 網域與 HTTPS 已完成，不用再給。`GOOGLE_REDIRECT_URI` 我已經設成
> `https://pillownight.com/auth/google/callback`，記得在 Google Cloud Console
> 的「已授權的重新導向 URI」加上同一個值。

我會把這些值寫進 `.env` 並做對應整合（OAuth Controller / Mail config / Ads adapter / GA4 layout 注入），不需要你再自己改設定檔。
