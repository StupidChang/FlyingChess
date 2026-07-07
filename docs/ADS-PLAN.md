# 廣告變現計劃（枕邊遊戲 PillowPlay）

> 最後更新：2026-07-03
> 現況：站內已內建 adapter 式廣告版位（`partials/ad-unit.blade.php`），支援
> **ExoClick / TrafficJunky / AdSense** 三種聯播網，用 `.env` 切換，Premium 會員自動免廣告。

---

## 0. 最重要的前提：這是成人向網站

**Google AdSense 明文禁止成人內容**（性暗示、情趣內容都算）。把 AdSense 掛在本站
會導致帳號被停權，且會連坐同帳號下其他網站。程式裡保留 adsense adapter 只是為了
未來可能的非成人子站——**正式上線請勿對本站啟用 AdSense**。GA4 分析可以用，投放廣告不行。

成人網站的變現主力是「成人友善聯播網 + 聯盟行銷」，單價（CPM）雖低於主流網路，
但本站受眾（情侶、購買力、明確意圖）非常適合情趣用品聯盟導購，實際收益天花板反而更高。

---

## 1. 聯播網選擇（建議順序）

### 第一階段（上線即可申請）：ExoClick
- 全球最大成人廣告聯播網之一，**新站、低流量也能過審**，有站就能申請。
- 支援 banner / native / popunder / push 多種格式（建議只用 banner + native，
  popunder 對 SEO 與使用者體驗傷害大，先不要開）。
- 結算：NET-7 / 最低出金 USD 20（Paxum / 銀行電匯 / 加密貨幣）。
- 申請流程：
  1. exoclick.com 註冊 Publisher 帳號
  2. Sites & Zones → 新增網站（需通過內容審核，確認有 age gate 會加分）
  3. 為每個版位建立 Zone（300×250、728×90、responsive）
  4. 把 zone id 填入 `.env`：`EXOCLICK_ZONE_HOME_BANNER=` 等五個變數
  5. `AD_ADAPTER=exoclick`
  6. 後台取得你的 ads.txt 行，填入 `ADS_TXT_LINES`（已有 `/ads.txt` 路由）
- 台灣/日本流量單價參考：banner eCPM 約 USD 0.1–0.5,native 稍高。

### 第二階段（日流量 > 5k 再申請）：TrafficJunky
- Aylo（Pornhub 母公司）旗下,單價比 ExoClick 高,但審核較嚴、偏好較大流量。
- 程式已內建 adapter：`AD_ADAPTER=trafficjunky` + site/spot id 即可切換。

### 可並行評估：JuicyAds、TrafficStars、Adsterra
- 都接受成人內容。若 ExoClick 填充率或單價不理想，可測試比價。
- 若要接入,依 `config/ads.php` + `partials/ad-unit.blade.php` 的 adapter 模式加一個分支即可（約 20 行）。

---

## 2. 比廣告更適合本站的：情趣用品聯盟行銷（強烈建議並行）

情侶遊戲的場景天然就是情趣用品的購買前一刻,轉換率遠高於一般內容站：

| 通路 | 市場 | 說明 |
|---|---|---|
| 聯盟網 Affiliates.One / 通路王 iChannels | 台灣 | 有成人用品電商主（分潤約 8–15%） |
| 各大情趣電商自營聯盟（如 Dr.情趣、iSex 等） | 台灣 | 直接洽談,分潤更高 |
| CrakRevenue | 全球 | 成人 CPA 大盤商,dating/cam/玩具 offer 都有 |
| Amazon Associates（日/英文流量） | 日/美 | 成人玩具類目可導購 |

**建議做法**：在遊戲結束頁（`game_end` 版位）與棋格內容相關處放「今晚道具推薦」
原生卡片,比 banner 點擊率高一個數量級。這也是 `game_end` zone 目前保留的用途。

---

## 3. 站內版位現況（5 個 zone）

| Zone | 位置 | 已掛頁面 |
|---|---|---|
| `home_banner` | 首頁首屏下方 | home |
| `home_mid` | 首頁中段 | home |
| `lobby_side` | 大廳/遊戲設定側欄 | games/lobby、各小遊戲頁 |
| `game_end` | 遊戲結束畫面 | **尚未掛**（建議掛在勝利/結算畫面,曝光價值最高） |
| `share` | 分享頁 | play、truth-dare |

用法：`@include('partials.ad-unit', ['zone' => 'game_end'])`

**版位原則**（已內建）：
- Premium 會員不顯示任何廣告（付費去廣告是日後 premium 的主要賣點之一）
- 廣告皆有 `aria-label` 與固定容器,避免 CLS（版面跳動傷 SEO Core Web Vitals）
- 每頁最多 2 個 banner,遊戲進行中的畫面不放廣告（不打斷體驗）

---

## 4. 上線 checklist（廣告部分）

1. [ ] ExoClick Publisher 帳號 + 網站過審
2. [ ] 建立 5 個 zone,填入 `.env` 的 `EXOCLICK_ZONE_*`
3. [ ] `AD_ADAPTER=exoclick`
4. [ ] `ADS_TXT_LINES` 填入 ExoClick 後台提供的行,驗證 `https://你的網域/ads.txt`
5. [ ] `GOOGLE_GA4_ID` 設好,用 GA4 看各版位頁的流量分佈
6. [ ] 觀察兩週 eCPM 與填充率,再決定是否加測第二家聯播網
7. [ ] 洽談 1–2 家情趣用品聯盟,在 `game_end` 上原生推薦卡

## 5. 之後開放付費時（已預留）

- Premium 金流（ECPay）與 `premium_expires_at` 機制已存在,`/premium` 頁面已完成。
- 建議定位:「去廣告 + Premium 專屬模板 + 進階自訂」。
- 廣告收益穩定後,用 GA4 數據找出重度使用者的轉付費點再開。

## 6. 合規注意

- 年齡門（已實作,cookie 30 天,爬蟲例外以保 SEO）是成人聯播網過審的必要條件。
- 隱私權政策需揭露第三方廣告 cookie（`/privacy` 頁記得補一段,含 ExoClick）。
- 若有歐盟流量,ExoClick 會透過其 script 處理 GDPR consent;台灣/日本市場暫無強制 CMP,先以隱私權政策揭露為主。
- 使用者自訂內容(棋盤格文字)已有黑名單過濾與發佈審核制,廣告商內容審查時這是加分項。
