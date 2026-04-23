# Structured Prompt — 情侶飛行棋成人遊戲平台完善

## Objective
將現有的情侶飛行棋 Laravel 專案完善為可變現的成人遊戲平台。包含：首頁改版（精緻感 + 雙品牌配色切換）、Navbar/Footer 完整設計、新增「真心話大冒險」小遊戲（含免費/付費題庫）、廣告系統整合（adapter 模式，預設 AdSense）、付費會員機制（NT$99/月，免廣告 + 進階功能）、SEO 強化、18+ 年齡驗證、棋盤編輯流程優化、GA4 追蹤埋點。目標是達到可以開始投放廣告並收取訂閱費用的產品完成度。

## Context
- 既有 Laravel 12 + Blade + Tailwind CSS v4 + Vite 7 + SQLite 專案
- 已有功能：飛行棋（session-based 多人 + Bot AI）、自訂棋盤編輯器（canvas editor）、使用者認證（email 驗證）、法律頁面、sitemap
- 成人向內容站，目標用戶：情侶（主力）、朋友聚會、遠距伴侶
- 主要市場：繁體中文（台灣），尚未上線
- 內容尺度：文字指令為主，公開頁面 PG-13（不含具體性行為描述、無裸露圖片、僅用「進階互動題庫」「18+ 會員專屬內容」等抽象描述），成人內容僅在付費題庫
- 廣告策略：adapter 架構，公開頁面 AdSense 相容，遊戲頁面日後可切換成人聯盟
- 金流：綠界 ECPay，單一月費 NT$99（config('premium.price') 可調），不自動續訂
- 續費規則：新到期日 = max(premium_expires_at, now()) + 30 天
- 配色雙主題：柔和粉色系（預設，情侶甜蜜）+ 深色成熟系（派對/刺激），CSS 變數 + localStorage

## Constraints
- 純 Laravel SSR + Blade + vanilla JS（可用少量 Alpine.js 做 toggle/dropdown）
- 不引入 SPA 框架（Livewire / Inertia / Vue / React）
- SQLite 預設 DB，不可更換
- 既有資料表只能新增欄位，不能刪改既有欄位
- 既有 URL 結構（/games/*, /play/*, /boards/*）不能改動
- 會員判斷：users.premium_expires_at > now() 為唯一真值（null 或過期 = 免費）
- 付款訂單：獨立 payment_orders 表
  - order_no: unique key，格式自訂
  - status: pending / paid / failed
  - callback 冪等：已 paid 直接回 1|OK
  - 每次付款建新訂單（不沿用舊 pending 訂單）
  - amount 從 config('premium.price') 讀取
  - 必做 CheckMacValue 驗證
  - RtnCode = 1 為成功，其他一律 failed
- ECPay 前台導頁：ReturnURL → /premium/result（依 query string MerchantTradeNo 查訂單），ClientBackURL → /premium
  - 查不到訂單時顯示通用文案導向 /premium
- 遊戲類型：games 表新增 game_type 欄位（flying_chess / truth_or_dare）
- 私人房間：games 表新增 is_private boolean
  - 建立需 premium，加入不需要（持 code 即可進入）
  - 不出現在大廳列表
- 真心話大冒險：
  - 沿用 games + game_players 表
  - 類別枚舉：truth / dare / couple / party
  - 抽題：依類別 + tier(free/premium) 分池完全隨機
  - 房主（建立者）為付費會員時，整房可用 premium 題庫
  - 回合順序：依 game_players 加入順序輪流
  - DB 記錄 current_player_index + last_card_id（存在 games.game_state JSON）
  - 離開時從剩餘順序繼續
  - 任何玩家都可開始遊戲
  - 房主離開房間仍可繼續
  - UI 狀態：選類別 → 抽題 → 顯示題目 → 手動按「下一位」（不需完成/跳過/重抽）
  - 每回合可重選類別
  - 題數不足時提示「此類別已無更多題目」
  - 支援 1-6 人
- 題庫管理：migration + seeder 預載，Phase 1 不做玩家自訂題庫
- 棋盤模板：
  - boards 表新增 is_template boolean + is_premium_template boolean
  - 使用模板 = clone 成使用者自己的棋盤
  - 免費用戶可預覽付費模板（僅靜態預覽頁 + CTA「升級會員解鎖」），不進 editor
  - 第一階段：3 免費模板 + 2 付費模板，由 seeder 預載
- 年齡驗證：
  - Middleware 層級
  - Cookie: age_verified，固定 30 天（非 rolling）
  - 全站攔截（含首頁 /）
  - 白名單：/privacy, /terms, /sitemap.xml, robots.txt, 靜態資產（/build/*, favicon, 圖片）
  - 爬蟲 UA 自動放行（Googlebot, Bingbot 等）
  - /play/share/{code} 需過 age gate
  - UI：全屏遮罩，標題「年齡確認」，聲明「本站含有成人內容，僅限 18 歲以上使用者瀏覽」，按鈕「我已滿 18 歲（進入）」「離開（→ google.com）」，底部連結 /privacy + /terms
- 敏感詞過濾：
  - 阻擋型，子字串匹配（str_contains），大小寫不敏感
  - Config: config/moderation.php → ['blocked_words' => [...]]
  - 覆蓋：暱稱、房間名稱、棋盤格子文字
  - 回傳 Laravel validation error，統一欄位級別錯誤
  - 棋盤格子錯誤 key: squares.{position}.text
  - 錯誤訊息：「此內容包含不允許的用語，請修改後重試」
- 廣告版位：
  - Blade component 封裝，adapter 模式，版位 ID 從 config 讀取
  - 顯示廣告頁面：首頁（banner + 中間插入）、遊戲等待/大廳（桌面側邊/手機下方 banner）、遊戲結束（插頁）、公開分享頁（底部）
  - 不顯示廣告頁面：年齡驗證、登入/註冊、付款、會員中心、棋盤編輯
  - 付費會員全站免廣告
  - 手機 RWD：側邊版位 fallback 為內容下方 banner
- GA4：
  - Measurement ID 從 .env(GOOGLE_GA4_ID) 讀取
  - 付費會員登入後全站不載入 GA4（layout @auth + premium check）
  - 7 個必備事件：age_gate_confirm, signup_completed（註冊成功當下，不等 email verify）, game_created, game_joined, truth_dare_card_drawn, checkout_started, payment_success（callback 成功時前台觸發）
- SEO：
  - Layout 預設 index,follow，指定路由覆寫 noindex,nofollow
  - index,follow：/ (首頁), /play, /play/share/{code}, /privacy, /terms
  - noindex,nofollow：/login, /register, /forgot-password, /reset-password/*, /email/verify*, /games/* (房間), /boards/* (CRUD/編輯), /premium* (付款), /games (大廳)
  - 分享頁 OG：棋盤名稱 + 描述（不含格子文字），外部連結 nofollow
- 首頁 PG-13 文案規則：
  - 不出現具體性行為描述
  - 不出現成人題庫示例內容
  - 僅用「進階互動題庫」「18+ 會員專屬內容」等抽象描述
  - 圖片/插圖不含裸露或暗示性視覺
  - 用「情侶升溫」「派對助興」等安全用語

## Inputs
- 既有程式碼庫（Controllers: Auth/Board/Game/Home/Legal/PasswordReset/Play/Sitemap, Models: User/Board/BoardSquare/Game/GamePlayer, Services: GameService）
- 新遊戲題庫由 seeder 預載至 truth_dare_cards 表（category, content, tier）
- 棋盤模板 3 免費 + 2 付費由 seeder 預載
- 使用者輸入（暱稱、房名、棋盤格子文字）經敏感詞過濾
- 廣告 ad slot ID 從 config/ads.php 注入
- ECPay callback POST（MerchantTradeNo, RtnCode, TradeNo, CheckMacValue 等）
- GA4 measurement ID 從 .env(GOOGLE_GA4_ID)

## Expected output
完整實作規格（15 項）：
1. 頁面清單 + 每頁區塊結構（廣告版位位置、會員/非會員差異、文案方向、SEO meta、index/noindex）
2. 新增/修改資料表 schema（payment_orders, truth_dare_cards; users 加 premium_expires_at; games 加 game_type + is_private; boards 加 is_template + is_premium_template）
3. 真心話大冒險：truth_dare_cards schema、回合流程、game_state JSON 結構、UI 狀態機、premium 解鎖條件
4. CSS 配色系統：CSS 變數定義表、柔和粉色系 + 深色成熟系完整色票、切換 JS 邏輯
5. Navbar 結構：所有連結、登入/匿名狀態差異、會員標示、配色 toggle、RWD
6. Footer 結構：法律連結、站點資訊、Copyright、社群佔位
7. 付費會員完整流程：DB schema、premium middleware、ECPay 建單→付款頁→callback→開通→到期降級、前台 /premium + /premium/result 頁面
8. 廣告整合：Blade component 設計、adapter config 結構、版位規則表（顯示/不顯示頁面 + 桌面/手機差異）
9. SEO：每頁 title/description/OG/canonical/robots 規格、JSON-LD schema、index/noindex 路由清單
10. 年齡驗證：middleware 實作、cookie 規格、全屏 UI 設計、白名單路由與爬蟲規則
11. 註冊漏斗：匿名→遊戲→CTA→註冊→付費完整路徑、各 CTA 觸發點與位置
12. 棋盤編輯流程優化：步驟引導 UI、預覽功能、儲存前驗證
13. GA4：7 個必備事件的 event name、觸發時機、payload 定義
14. 敏感詞過濾：config 結構、Form Request 驗證規則、逐欄位錯誤回傳
15. 開發優先級與階段劃分（Phase 1 必做清單 + Phase 2 規劃）

## Acceptance criteria
- 首頁改版完成：視覺吸引力、明確 CTA（開始玩/註冊）、遊戲介紹區塊、PG-13 文案規則遵守
- 配色切換：柔和粉色系 + 深色成熟系，全站一致，localStorage 持久化，切換無閃爍
- Navbar：Logo、遊戲列表、我的棋盤（登入後）、登入/註冊 or 用戶名+登出、會員標示（付費）、配色 toggle、RWD 漢堡選單
- Footer：/privacy + /terms 連結、站點名稱、Copyright、社群連結佔位
- 真心話大冒險可玩：選類別（truth/dare/couple/party，每回合可重選）→ 隨機抽題 → 顯示 → 按「下一位」→ 輪流，1-6 人，房主付費時整房用 premium 題庫，題數不足時提示
- 廣告版位正確：首頁 banner+插入、等待頁側邊（手機改下方）、結束頁插頁、分享頁底部；禁放頁面無廣告；付費全站免廣告
- ECPay 完整流程：/premium 選方案 → 建單(pending) → ECPay 付款頁 → callback(CheckMacValue 驗證, 冪等) → 開通(premium_expires_at = max(原值,now())+30天) → /premium/result 顯示結果；到期自動降級（premium_expires_at 判斷）
- 私人房間：建立需 premium（否則提示升級），加入持 code 即可，大廳不顯示
- 棋盤編輯：步驟引導（建立→編輯格子→設定路徑→預覽→儲存），儲存前驗證
- 棋盤模板：3 免費可 clone、2 付費僅靜態預覽 + CTA 升級
- SEO：index 頁完整 meta（title/desc/OG/canonical）；noindex 頁正確 robots；layout 預設 index → 路由覆寫；分享頁 OG 不含格子文字
- 年齡驗證：全站攔截（含首頁），全屏遮罩 UI，聲明文字 + 法律連結，cookie 固定 30 天，法律頁/sitemap/robots/靜態資產/爬蟲不攔
- 敏感詞：暱稱/房名/棋盤格子文字，阻擋型，逐欄位錯誤（棋盤逐格 squares.{pos}.text），統一錯誤訊息
- GA4：7 個事件正確觸發，付費會員全站不載入 GA4
- 匿名可玩飛行棋 + 免費真心話大冒險，遊戲結束 CTA 導向註冊
- payment_orders.status 正確流轉 pending → paid/failed

## Non-goals
- WebSocket 即時連線（維持 polling）
- 聊天系統、推薦演算法、行動 App（RWD 即可）
- 複雜後台 CMS（config 管理即可）
- 社群功能（留言牆、好友系統、個人主頁）
- 多語系（第一階段僅繁中）
- 第三方 OAuth（Google/Facebook 登入）
- 信用卡/海外金流（僅台灣 ECPay）
- A/B test、進階分析儀表板（GA4 基礎即可）
- AI 內容審核（基礎敏感詞過濾即可）
- 自動續訂（手動續費）
- 玩家自訂題庫（Phase 2）
- 匿名遊戲歷史承接（Phase 2）
- 敏感詞正規表示式/符號繞過處理（Phase 2）
- 完成/跳過/重抽功能（Phase 2）

## Out-of-scope
- GameService.php 核心邏輯（52 格棋盤、relToAbs、Bot AI、安全格、捕獲機制）— 只能微調 UX
- BoardSquare 既有資料結構（position/grid_row/grid_col/color/fly_to）— 不能破壞
- 既有 URL 結構（/games/*, /play/*, /boards/*）— 不能改動
- Docker 部署架構（Dockerfile, docker-compose.yml, docker/）— 不能改動
- 既有法律頁面（/privacy, /terms）— 不能移除，可更新內容
- sitemap 生成邏輯 — 不能移除，可擴充新頁面
- 既有認證流程（register/login/logout/email verify/password reset）— 不能重構，可微調 UI
