{{--
    Resolve the display locale for error pages from the URL prefix
    (tw/cn/jp/en) or the `locale` cookie. Must be @include'd at the TOP of
    every error view (before any @section) because inline @section values
    evaluate during the child render, before the parent layout runs — and on
    503 (maintenance) / 404 no middleware has set the locale yet.
--}}
@php
    $errPrefixMap = ['tw' => 'zh_TW', 'cn' => 'zh_CN', 'jp' => 'ja', 'en' => 'en'];
    $errSeg = request()->segment(1);
    if (isset($errPrefixMap[$errSeg])) {
        app()->setLocale($errPrefixMap[$errSeg]);
    } elseif (($errCookieLocale = request()->cookie('locale')) && in_array($errCookieLocale, $errPrefixMap, true)) {
        app()->setLocale($errCookieLocale);
    }
@endphp
