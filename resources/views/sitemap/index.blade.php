<?php echo '<?xml version="1.0" encoding="UTF-8"?>'."\n"; ?>
<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
@foreach ($locales as $locale => $meta)
    <sitemap>
        <loc>{{ url('/sitemap-'.$meta['prefix'].'.xml') }}</loc>
    </sitemap>
@endforeach
</sitemapindex>
