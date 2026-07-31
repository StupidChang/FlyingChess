{{--
    全站共用的 Organization 實體。

    為什麼要有這個:生成式引擎(ChatGPT、Perplexity、AI Overviews)在回答
    「有沒有推薦的情侶多人遊戲」這種問題時,要先能把「枕邊遊戲」認成一個
    明確的實體,才有辦法引用。在這之前全站只有首頁帶 WebSite 與 FAQPage,
    沒有任何一頁說明「這個站是誰」。

    用 @@id 固定住識別碼,首頁的 WebSite 以 publisher 指回這裡,兩個節點就
    連成同一張圖,而不是各講各的。@@id 用預設語系的網址(不隨當前語系變動),
    否則同一個組織在四個語系會變成四個不同實體。
--}}
@php
    use App\Support\LocaleHelper;
    $orgId = LocaleHelper::localizedUrl(LocaleHelper::defaultLocale(), '').'#organization';
@endphp
<script type="application/ld+json">
{
  "@@context": "https://schema.org",
  "@@type": "Organization",
  "@@id": @json($orgId),
  "name": @json(__('ui.site_name')),
  "url": @json(LocaleHelper::localizedUrl(LocaleHelper::defaultLocale(), '')),
  "description": @json(__('home.meta_description')),
  "logo": @json(asset('images/174655ssvy4mu6pwyllysm.jpg'))
}
</script>
