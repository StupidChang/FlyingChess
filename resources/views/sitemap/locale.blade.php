<?php
echo '<?xml version="1.0" encoding="UTF-8"?>'."\n";
use App\Support\LocaleHelper;

$paths = [
    ['path' => '',                'priority' => '1.0', 'changefreq' => 'weekly'],
    ['path' => 'play',            'priority' => '0.9', 'changefreq' => 'weekly'],
    ['path' => 'game-hall',       'priority' => '0.8', 'changefreq' => 'weekly'],
    ['path' => 'bucket-list',     'priority' => '0.8', 'changefreq' => 'weekly'],
    ['path' => 'time-capsule',    'priority' => '0.8', 'changefreq' => 'weekly'],
    ['path' => 'truth-dare',      'priority' => '0.7', 'changefreq' => 'weekly'],
    ['path' => 'card-game',       'priority' => '0.7', 'changefreq' => 'monthly'],
    ['path' => 'dice-game',       'priority' => '0.7', 'changefreq' => 'monthly'],
    ['path' => 'king-game',       'priority' => '0.7', 'changefreq' => 'monthly'],
    ['path' => 'wheel-game',      'priority' => '0.7', 'changefreq' => 'monthly'],
    ['path' => 'templates',       'priority' => '0.6', 'changefreq' => 'monthly'],
    ['path' => 'community',       'priority' => '0.7', 'changefreq' => 'daily'],
    ['path' => 'premium',         'priority' => '0.5', 'changefreq' => 'monthly'],
    ['path' => 'privacy',         'priority' => '0.3', 'changefreq' => 'yearly'],
    ['path' => 'terms',           'priority' => '0.3', 'changefreq' => 'yearly'],
];

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
