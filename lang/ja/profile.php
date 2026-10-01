<?php

/*
 * プロフィールページ / パーソナライズ / 公開プロフィールの文言。fallback_locale は zh_TW
 * なので、未翻訳のロケールはそちらへ戻る（プロフィールページはもともと noindex で、
 * 中国語に戻っても SEO 上の問題はない）。
 */
return [
    'saved' => 'プロフィールを更新しました',
    'avatar_saved' => 'アバターを更新しました',
    'avatar_removed' => 'アバターを削除しました',
    'banner_saved' => 'バナーを更新しました',
    'banner_removed' => 'バナーを削除しました',

    // 編集ページ
    'edit_title' => 'プロフィールを編集',
    'edit_heading' => 'あなたのプロフィールページを作ろう',
    'edit_intro' => '書けば書くほど、あなたのことが伝わります —— これらは公開プロフィールページに表示されます。',
    'back_to_profile' => 'プロフィールに戻る',
    'view_public' => '公開ページを見る',
    'edit_profile' => 'プロフィールページを編集',

    'section_avatar' => 'アバター',
    'section_banner' => 'プロフィールバナー',
    'section_photos' => '写真とカバー',
    'photo_hint' => 'アバターは最大 2MB、バナーは最大 4MB（JPG / PNG / WebP）。画像を変えたら上のプレビューを直接ドラッグして見せたい位置を調整でき、下の「保存」を押すとまとめて反映されます。',
    'avatar_label_short' => 'アバター',
    'banner_label_short' => 'バナー',
    'banner_hint' => 'プロフィールページの最上部に表示されるカバー画像です。未設定の場合は選んだグラデーションカラーが使われます。',
    'banner_upload' => 'バナーをアップロード',
    'banner_change' => 'バナーを変更',
    'banner_remove' => 'バナーを削除',
    'avatar_change2' => '変更',
    'avatar_upload2' => 'アップロード',
    'remove' => '削除',
    'reposition_hint' => 'ドラッグで位置を調整',
    'adjust' => '範囲を調整',
    'crop_avatar_title' => 'アバターの表示範囲を調整',
    'crop_banner_title' => 'バナーの表示範囲を調整',
    'crop_hint' => '枠をドラッグして残す範囲を選び、角をつまむと拡大・縮小できます。枠内の部分だけが保存されます。',
    'crop_processing' => '処理中…',
    'crop_cancel' => 'キャンセル',
    'crop_apply' => '適用',
    'section_basics' => '基本情報',
    'section_theme' => 'プロフィールカラー',
    'section_privacy' => '公開設定',

    'avatar_hint' => 'JPG / PNG / WebP、最大 2MB。',
    'avatar_upload' => 'アバターをアップロード',
    'avatar_change' => 'アバターを変更',
    'avatar_remove' => 'アバターを削除',

    'name_label' => 'ニックネーム',
    'city_label' => '住んでいる都市',
    'city_placeholder' => '例：東京、大阪、台北',
    'looking_for_label' => 'どんな人と知り合いたいか',
    'looking_for_placeholder' => '例：一緒にボードゲームで遊べて、話の合う相手を探しています',
    'bio_label' => '自己紹介',
    'bio_placeholder' => '自分について少し書いてみましょう —— 好きなこと、探している関係……',

    'theme_hint' => '色を選ぶと、プロフィールページのバナーとアクセントカラーに適用されます。',

    'public_label' => 'プロフィールページを公開する',
    'public_hint' => 'オンにすると、あなたのプロフィールページのリンクから上記の情報が他の人にも見えるようになります（メールアドレスとプレイ履歴は含まれません）。オフの場合は自分だけが見られます。',
    'public_on' => '公開中',
    'public_off' => '非公開',
    'show_traits_label' => '公開ページに自分の性癖属性を表示',
    'show_boards_label' => '公開ページに自分が作ったボードを表示',
    'public_sections_hint' => 'これらは「プロフィールページを公開する」がオンのときにだけ表示されます。',

    'save' => '保存',

    // 公開ページ（他の人に見える側）
    'public_meta' => ':name さんのプロフィールページ',
    'lives_in' => ':city 在住',
    'looking_for_title' => '知り合いたい人',
    'top_trait_title' => '性癖属性',
    'boards_title' => '制作したボード',
    'boards_play' => '遊ぶ',
    'top_trait_take' => '自分の属性も診断してみる',
    'no_bio' => 'この人はまだ自己紹介を書いていません。',
    'member_since' => ':date に登録',
    'this_is_you' => 'これはあなたの公開ページです —— 他の人にはこのように見えています。',
    'edit_mine' => '編集',
    'private_notice' => 'このプロフィールページは非公開です。',

    // 探す（ディスカバーページ）
    'discover_title' => '探す',
    'discover_heading' => 'サイト内の人を探す',
    'discover_intro' => 'ここにはプロフィールページを公開しているユーザーが表示されます。見つけてほしい場合は、自分のプロフィールページで「公開」をオンにしてください。',
    'discover_search' => '検索',
    'discover_city_placeholder' => '都市名で検索、例：東京',
    'discover_count' => '公開プロフィール :n 人',
    'discover_empty' => 'まだ誰もいません —— 最初にプロフィールを公開する人になりましょう。',
    'discover_empty_city' => '「:city」には公開プロフィールがまだありません。',
    'discover_looking' => '知り合いたい人：',
    'discover_view' => '見てみる',
];
