<?php
echo '<?xml version="1.0" encoding="UTF-8"?>'."\n";
use App\Support\LocaleHelper;

$paths = [
    ['path' => '',                'priority' => '1.0', 'changefreq' => 'weekly'],
    ['path' => 'play',            'priority' => '0.9', 'changefreq' => 'weekly'],
    ['path' => 'game-hall',       'priority' => '0.8', 'changefreq' => 'weekly'],
    // 共同清單 / 時光膠囊 暫時隱藏，不列入 sitemap（日後還原時取消註解即可）
    // ['path' => 'bucket-list',     'priority' => '0.8', 'changefreq' => 'weekly'],
    // ['path' => 'time-capsule',    'priority' => '0.8', 'changefreq' => 'weekly'],
    ['path' => 'truth-dare',      'priority' => '0.7', 'changefreq' => 'weekly'],
    ['path' => 'card-game',       'priority' => '0.7', 'changefreq' => 'monthly'],
    ['path' => 'dice-game',       'priority' => '0.7', 'changefreq' => 'monthly'],
    ['path' => 'king-game',       'priority' => '0.7', 'changefreq' => 'monthly'],
    ['path' => 'wheel-game',      'priority' => '0.7', 'changefreq' => 'monthly'],
    // 純轉盤。首頁與遊戲大廳各連了 4 次、回 200 可索引,但一直漏在 sitemap 外。
    ['path' => 'wheel',           'priority' => '0.6', 'changefreq' => 'monthly'],
    ['path' => 'who-most-likely', 'priority' => '0.7', 'changefreq' => 'monthly'],
    ['path' => 'trait-test',      'priority' => '0.8', 'changefreq' => 'monthly'],
    ['path' => 'custom-wheel',    'priority' => '0.6', 'changefreq' => 'monthly'],
    ['path' => 'templates',       'priority' => '0.6', 'changefreq' => 'monthly'],
    ['path' => 'community',       'priority' => '0.7', 'changefreq' => 'daily'],
    ['path' => 'premium',         'priority' => '0.5', 'changefreq' => 'monthly'],
    ['path' => 'privacy',         'priority' => '0.3', 'changefreq' => 'yearly'],
    ['path' => 'terms',           'priority' => '0.3', 'changefreq' => 'yearly'],
];

/* 屬性測驗的 20 個結果頁。每一種屬性都是一個獨立的落地頁 —— 這才是這個測驗
   對搜尋的價值,只收錄測驗本身的話等於只有一頁。
   只在有翻譯的語系列出:沒翻譯的那幾頁是 noindex,列進 sitemap 是自相矛盾。 */
if (in_array($currentLocale, (array) config('traits.translated', []), true)) {
    foreach ((array) trans('traits.items', [], $currentLocale) as $item) {
        if (! empty($item['slug'])) {
            $paths[] = ['path' => 'trait-test/'.$item['slug'], 'priority' => '0.6', 'changefreq' => 'monthly'];
        }
    }
}

foreach ($boards as $b) {
    $paths[] = ['path' => 'play/share/'.$b->share_code, 'priority' => '0.6', 'changefreq' => 'monthly'];
}
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:xhtml="http://www.w3.org/1999/xhtml">
@foreach ($paths as $p)
    <url>
        <loc>{{ LocaleHelper::localizedUrl($currentLocale, $p['path']) }}</loc>
        @foreach ($supported as $locale => $meta)
        <xhtml:link rel="alternate" hreflang="{{ $meta['hreflang'] }}" href="{{ LocaleHelper::localizedUrl($locale, $p['path']) }}"/>
        @endforeach
        <xhtml:link rel="alternate" hreflang="x-default" href="{{ LocaleHelper::localizedUrl(LocaleHelper::defaultLocale(), $p['path']) }}"/>
        <changefreq>{{ $p['changefreq'] }}</changefreq>
        <priority>{{ $p['priority'] }}</priority>
    </url>
@endforeach
</urlset>
