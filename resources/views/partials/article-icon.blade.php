{{--
    文章段落的圖示。指南文章(/guide)與屬性測驗結果頁的區塊標題共用同一組。

    注意這**不是** partials/icon.blade.php —— 那是站台通用的 Heroicons 組(參數是
    name / cls,用在導覽列、個人資料頁)。這一組是文章版面自己的線稿,參數是
    icon / class,兩者刻意不合併:合併之後任何一邊要調線寬或尺寸都會動到對方。

    為什麼是自己畫的 inline SVG,不是抓現成圖片:抓來的圖有授權問題(而且這個站
    的聯播網對素材來源很敏感),外部圖檔還會多一次請求。這一組是 24×24 的線稿,
    stroke 吃 currentColor,所以顏色由 CSS 決定、深淺主題都對。

    用法:@include('partials.article-icon', ['icon' => 'clock'])
    要換尺寸類別就傳 class:['icon' => 'clock', 'class' => 'tt-sec-ico']
    文案端在 section 裡寫 'icon' => 'clock';沒寫或寫錯名字都會退回一個小圓點,
    不會破版。
--}}
@php
    $paths = [
        // 時間、行程
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/>',
        // 關係、身體
        'heart' => '<path d="M12 20s-7-4.5-7-9.5A3.8 3.8 0 0 1 12 8a3.8 3.8 0 0 1 7 2.5C19 15.5 12 20 12 20z"/>',
        'bed' => '<path d="M3 18v-7h18v7M3 11V7M21 11v7M7 11V9a2 2 0 0 1 2-2h2v4"/>',
        'sparkle' => '<path d="M12 3l1.8 5.2L19 10l-5.2 1.8L12 17l-1.8-5.2L5 10l5.2-1.8z"/><path d="M18 16.5l.9 2.1 2.1.9-2.1.9-.9 2.1-.9-2.1-2.1-.9 2.1-.9z"/>',
        // 溝通
        'chat' => '<path d="M20 15a2 2 0 0 1-2 2H8l-4 4V6a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2z"/>',
        'quote' => '<path d="M8 7H5a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h3v3a4 4 0 0 1-4 4M19 7h-3a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h3v3a4 4 0 0 1-4 4"/>',
        'question' => '<circle cx="12" cy="12" r="9"/><path d="M9.5 9.5a2.5 2.5 0 1 1 3.4 2.3c-.6.3-.9.8-.9 1.4v.4"/><path d="M12 17h.01"/>',
        // 提醒、界線
        'warning' => '<path d="M12 4l9 16H3z"/><path d="M12 10v4M12 17h.01"/>',
        'shield' => '<path d="M12 3l8 3v6c0 5-3.4 8-8 9-4.6-1-8-4-8-9V6z"/><path d="M9 12.5l2 2 4-4"/>',
        // 清單、步驟
        'list' => '<path d="M8 6h13M8 12h13M8 18h13M3.5 6h.01M3.5 12h.01M3.5 18h.01"/>',
        'steps' => '<path d="M3 20h5v-5h5v-5h5V5"/>',
        // 距離、裝置
        'plane' => '<path d="M10.5 19.5L12 21l1.5-1.5-.6-4.2 5.4 2.7 1.2-1.2-4.5-5.4 4.2-1.8-1.2-1.2-5.1 1.5L12 3l-1.5 1.5.9 5.7-5.1-1.5-1.2 1.2 4.2 1.8L4.8 18l1.2 1.2 5.1-2.7z"/>',
        'video' => '<rect x="3" y="6" width="12" height="12" rx="2"/><path d="M15 10.5l6-3v9l-6-3z"/>',
        'gift' => '<rect x="3" y="9" width="18" height="12" rx="2"/><path d="M3 13h18M12 9v12"/><path d="M8 9a2.5 2.5 0 1 1 0-5c1.8 0 4 5 4 5M16 9a2.5 2.5 0 1 0 0-5c-1.8 0-4 5-4 5"/>',
        'users' => '<circle cx="9" cy="8" r="3"/><path d="M3 20v-1a5 5 0 0 1 10 0v1"/><path d="M16 6.5a3 3 0 0 1 0 5.8M17.5 20v-1a5 5 0 0 0-2-4"/>',
        'wallet' => '<rect x="3" y="6" width="18" height="13" rx="2"/><path d="M3 10h18M16 14h2"/>',
        // 屬性測驗結果頁的區塊
        'pulse' => '<path d="M3 12h4l2.5-6 4 12L16 12h5"/>',
        'sun' => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M2 12h2M20 12h2M5 5l1.5 1.5M17.5 17.5L19 19M19 5l-1.5 1.5M6.5 17.5L5 19"/>',
        'bars' => '<path d="M3 21h18"/><path d="M6.5 21V11M12 21V5M17.5 21v-6"/>',
        'sliders' => '<path d="M4 7h16M4 12h16M4 17h16"/><circle cx="9" cy="7" r="2"/><circle cx="15" cy="12" r="2"/><circle cx="7" cy="17" r="2"/>',
        'bulb' => '<path d="M9.5 18h5M10.5 21h3"/><path d="M12 3a6 6 0 0 0-3.5 10.9c.5.4.8 1 .8 1.6h5.4c0-.6.3-1.2.8-1.6A6 6 0 0 0 12 3z"/>',
        'calc' => '<rect x="4" y="3" width="16" height="18" rx="2"/><path d="M8 7.5h8M8 12h.01M12 12h.01M16 12h.01M8 16.5h.01M12 16.5h.01M16 16.5h.01"/>',
        'link' => '<path d="M9.5 14.5l5-5"/><path d="M13 7l1.5-1.5a3.5 3.5 0 0 1 5 5L18 12"/><path d="M11 17l-1.5 1.5a3.5 3.5 0 0 1-5-5L6 12"/>',
        'zap' => '<path d="M13 2L4.5 13.5H11l-1 8.5L19.5 10.5H12z"/>',
        'grid' => '<rect x="3" y="3" width="7.5" height="7.5" rx="1.6"/><rect x="13.5" y="3" width="7.5" height="7.5" rx="1.6"/><rect x="3" y="13.5" width="7.5" height="7.5" rx="1.6"/><rect x="13.5" y="13.5" width="7.5" height="7.5" rx="1.6"/>',
        'dot' => '<circle cx="12" cy="12" r="4"/>',
    ];
    $d = $paths[$icon ?? 'dot'] ?? $paths['dot'];
@endphp
<svg class="{{ $class ?? 'gd-icon' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"
     stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">{!! $d !!}</svg>
