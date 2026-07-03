<?php

if (! function_exists('asset_v')) {
    /**
     * asset() with a cache-busting version query derived from the file's
     * mtime. The public/css + public/js files are hand-maintained (no build
     * pipeline / no Vite manifest), and nginx serves them with long-lived
     * cache headers — without a version param every deploy leaves visitors
     * on stale CSS/JS until a hard refresh.
     */
    function asset_v(string $path): string
    {
        $full = public_path($path);
        $version = is_file($full) ? filemtime($full) : null;

        return asset($path).($version ? '?v='.$version : '');
    }
}
