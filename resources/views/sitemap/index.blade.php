<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">

    {{-- 靜態核心頁面 --}}
    <url>
        <loc>{{ url('/') }}</loc>
        <changefreq>weekly</changefreq>
        <priority>1.0</priority>
    </url>
    <url>
        <loc>{{ url('/play') }}</loc>
        <changefreq>weekly</changefreq>
        <priority>0.9</priority>
    </url>
    {{-- /games is noindex — omitted from sitemap --}}
    <url>
        <loc>{{ url('/privacy') }}</loc>
        <changefreq>yearly</changefreq>
        <priority>0.3</priority>
    </url>
    <url>
        <loc>{{ url('/terms') }}</loc>
        <changefreq>yearly</changefreq>
        <priority>0.3</priority>
    </url>

    {{-- 公開棋盤分享頁（有 share_code 的棋盤） --}}
    @foreach ($boards as $board)
    <url>
        <loc>{{ url('/play/share/' . $board->share_code) }}</loc>
        <changefreq>monthly</changefreq>
        <priority>0.6</priority>
    </url>
    @endforeach

</urlset>
