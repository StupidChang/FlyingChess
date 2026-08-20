<?php

/*
 * 個人頁 / 個人化 / 公開檔案的文案。fallback_locale 是 zh_TW,所以還沒翻譯的
 * 語系會退回這一份(個人頁本來就 noindex,退回中文沒有 SEO 疑慮)。
 */
return [
    'saved' => '個人資料已更新',
    'avatar_saved' => '頭像已更新',
    'avatar_removed' => '頭像已移除',
    'banner_saved' => '橫幅已更新',
    'banner_removed' => '橫幅已移除',

    // 編輯頁
    'edit_title' => '編輯個人資料',
    'edit_heading' => '打造你的個人頁',
    'edit_intro' => '填得越完整,別人越認識你 —— 這些會顯示在你的公開個人頁上。',
    'back_to_profile' => '回個人資料',
    'view_public' => '查看公開頁',
    'edit_profile' => '編輯個人頁',

    'section_avatar' => '頭像',
    'section_banner' => '個人頁橫幅',
    'section_photos' => '照片與封面',
    'photo_hint' => '頭像最大 2MB、橫幅最大 4MB(JPG / PNG / WebP)。換圖後在上方預覽直接拖曳,就能調整要露出的位置,按下方「儲存」一起生效。',
    'avatar_label_short' => '頭像',
    'banner_label_short' => '橫幅',
    'banner_hint' => '會顯示在你個人頁最上方的封面圖。沒上傳就用你選的漸層配色。',
    'banner_upload' => '上傳橫幅',
    'banner_change' => '更換橫幅',
    'banner_remove' => '移除橫幅',
    'avatar_change2' => '更換',
    'avatar_upload2' => '上傳',
    'remove' => '移除',
    'reposition_hint' => '拖曳可調整位置',
    'adjust' => '調整範圍',
    'crop_avatar_title' => '調整頭像顯示範圍',
    'crop_banner_title' => '調整橫幅顯示範圍',
    'crop_hint' => '拖曳方框選擇要保留的範圍,拉角落可縮放。只有框內的部分會被儲存。',
    'crop_processing' => '處理中…',
    'crop_cancel' => '取消',
    'crop_apply' => '套用',
    'section_basics' => '基本資料',
    'section_theme' => '個人頁配色',
    'section_privacy' => '公開設定',

    'avatar_hint' => 'JPG / PNG / WebP,最大 2MB。',
    'avatar_upload' => '上傳頭像',
    'avatar_change' => '更換頭像',
    'avatar_remove' => '移除頭像',

    'name_label' => '暱稱',
    'city_label' => '所在城市',
    'city_placeholder' => '例如:台北、高雄、Tokyo',
    'looking_for_label' => '想認識什麼樣的人',
    'looking_for_placeholder' => '例如:想找一起玩桌遊、聊得來的對象',
    'bio_label' => '自我介紹',
    'bio_placeholder' => '寫幾句關於你自己 —— 喜歡什麼、在找什麼樣的關係……',

    'theme_hint' => '選一個顏色,套用在你的個人頁橫幅與重點色。',

    'public_label' => '公開我的個人頁',
    'public_hint' => '打開後,別人可以透過你的個人頁連結看到上面這些資料(不含 email 與遊玩紀錄)。關閉則只有你自己看得到。',
    'public_on' => '公開中',
    'public_off' => '未公開',
    'show_traits_label' => '在公開頁顯示我的性癖屬性',
    'show_boards_label' => '在公開頁顯示我製作的棋盤',
    'public_sections_hint' => '這些只有在「公開我的個人頁」打開時才會出現。',

    'save' => '儲存',

    // 公開頁(別人看到的)
    'public_meta' => ':name 的個人頁',
    'lives_in' => '住在 :city',
    'looking_for_title' => '想認識',
    'top_trait_title' => '性癖屬性',
    'boards_title' => '我製作的棋盤',
    'boards_play' => '去玩',
    'top_trait_take' => '也來測測自己的屬性',
    'no_bio' => '這個人還沒寫自我介紹。',
    'member_since' => ':date 加入',
    'this_is_you' => '這是你的公開頁 —— 別人看到的就是這個樣子。',
    'edit_mine' => '編輯',
    'private_notice' => '這個個人頁尚未公開。',

    // 尋找(探索頁)
    'discover_title' => '尋找',
    'discover_heading' => '尋找站上的人',
    'discover_intro' => '這裡是有公開個人頁的使用者。想被找到的話,到你的個人頁打開「公開」。',
    'discover_search' => '搜尋',
    'discover_city_placeholder' => '用城市搜尋,例如:台北',
    'discover_count' => '共 :n 位公開個人頁',
    'discover_empty' => '目前這裡還沒有人 —— 成為第一個公開個人頁的人吧。',
    'discover_empty_city' => '「:city」目前沒有公開的個人頁。',
    'discover_looking' => '想認識:',
    'discover_view' => '看看',
];
