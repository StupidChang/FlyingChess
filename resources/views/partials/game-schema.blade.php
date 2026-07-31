{{--
    單一遊戲頁的結構化資料:VideoGame + BreadcrumbList。

    參數(全部必填,除了 $maxPlayers):
      $gameName        遊戲名稱(用可見的 *_title,不要用塞了關鍵字的 *_seo_title)
      $gameDescription 一句話說明
      $gamePath        不含語系前綴的路徑,例如 'card-game'
      $minPlayers      最少人數
      $maxPlayers      最多人數;單人或不設上限時傳 null

    用 VideoGame 而不是 Game,因為 VideoGame 才有 playMode、gamePlatform 這些
    「怎麼玩、在哪玩」的欄位 —— 生成式引擎回答「幾個人可以玩」「要不要下載」
    時抽的就是這幾格。numberOfPlayers 是這裡最重要的一欄:站上每個遊戲的人數
    上下限本來只寫在畫面文案裡,機器讀不到。

    isFamilyFriendly 與 contentRating 是刻意誠實標示的。這個站有年齡閘,對外
    宣告成人向可以讓 AI 引擎在回答時自己處理分級,而不是誤推薦給一般客群。
--}}
@php
    use App\Support\LocaleHelper;
    $locale = app()->getLocale();
    $orgId = LocaleHelper::localizedUrl(LocaleHelper::defaultLocale(), '').'#organization';
    $gameUrl = LocaleHelper::localizedUrl($locale, $gamePath);
    $maxPlayers = $maxPlayers ?? null;
@endphp
<script type="application/ld+json">
{
  "@@context": "https://schema.org",
  "@@type": "VideoGame",
  "name": @json($gameName),
  "description": @json($gameDescription),
  "url": @json($gameUrl),
  "inLanguage": @json(LocaleHelper::hreflang($locale)),
  "publisher": { "@@id": @json($orgId) },
  "gamePlatform": "Web browser",
  "operatingSystem": "Any",
  "applicationCategory": "GameApplication",
  {{-- playMode 看的是上限而不是下限:真心話大冒險下限是 1,但它是 1–6 人的房間,
       只憑下限判斷會把它標成純單人遊戲。下限 1 且上限 >1 時兩種模式都列。 --}}
  "playMode": @json(
      ($maxPlayers === null || $maxPlayers > 1)
          ? ($minPlayers <= 1 ? ['SinglePlayer', 'MultiPlayer'] : 'MultiPlayer')
          : 'SinglePlayer'
  ),
  "numberOfPlayers": {
    "@@type": "QuantitativeValue",
    "minValue": {{ (int) $minPlayers }}@if($maxPlayers),
    "maxValue": {{ (int) $maxPlayers }}@endif

  },
  "isAccessibleForFree": true,
  "isFamilyFriendly": false,
  "contentRating": "Adults Only"
}
</script>
<script type="application/ld+json">
{
  "@@context": "https://schema.org",
  "@@type": "BreadcrumbList",
  "itemListElement": [
    { "@@type": "ListItem", "position": 1, "name": @json(__('ui.site_name')),
      "item": @json(LocaleHelper::localizedUrl($locale, '')) },
    { "@@type": "ListItem", "position": 2, "name": @json(__('seo.lobby_title')),
      "item": @json(LocaleHelper::localizedUrl($locale, 'game-hall')) },
    { "@@type": "ListItem", "position": 3, "name": @json($gameName),
      "item": @json($gameUrl) }
  ]
}
</script>
