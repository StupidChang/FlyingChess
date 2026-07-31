{{--
    遊戲頁 FAQ 的 FAQPage 結構化資料,跟 partials/game-faq 讀同一份 lang 資料,
    所以畫面上看到的問答與機器讀到的必定一致 —— 兩邊各寫一份遲早會分岔,而
    Google 對「schema 宣告的內容頁面上找不到」是會判為違規的。

    參數與 game-faq 相同:$faqKey
--}}
@php
    $faqSchemaItems = __('faq.games.'.$faqKey);
    // 與 partials/game-faq 用同一組佔位符,兩邊代入的值才不會分岔。
    // :price 已含幣別符號(例如 "NT$249"),語言檔裡不再寫死幣別。
    $faqVars = [
        ':price' => \App\Support\Pricing::entryPrice(),
        ':minutes' => \App\Support\PremiumAccess::rewardedMinutes(),
    ];
@endphp
@if(is_array($faqSchemaItems) && count($faqSchemaItems))
<script type="application/ld+json">
{
  "@@context": "https://schema.org",
  "@@type": "FAQPage",
  "inLanguage": @json(\App\Support\LocaleHelper::hreflang(app()->getLocale())),
  "mainEntity": [
@foreach($faqSchemaItems as $i => $item)
    { "@@type": "Question", "name": @json($item['q']),
      "acceptedAnswer": { "@@type": "Answer", "text": @json(strtr($item['a'], $faqVars)) } }@if($i < count($faqSchemaItems) - 1),@endif

@endforeach
  ]
}
</script>
@endif
