<?php

/*
 * 个人主页 / 个性化 / 公开资料页的文案。fallback_locale 是 zh_TW,所以还没翻译的
 * 语系会退回那一份(个人主页本来就 noindex,退回中文没有 SEO 问题)。
 */
return [
    'saved' => '个人资料已更新',
    'avatar_saved' => '头像已更新',
    'avatar_removed' => '头像已移除',
    'banner_saved' => '横幅已更新',
    'banner_removed' => '横幅已移除',

    // 编辑页
    'edit_title' => '编辑个人资料',
    'edit_heading' => '打造你的个人主页',
    'edit_intro' => '填得越完整,别人越了解你 —— 这些会显示在你的公开个人主页上。',
    'back_to_profile' => '返回个人资料',
    'view_public' => '查看公开主页',
    'edit_profile' => '编辑个人主页',

    'section_avatar' => '头像',
    'section_banner' => '个人主页横幅',
    'section_photos' => '照片与封面',
    'photo_hint' => '头像最大 2MB、横幅最大 4MB(JPG / PNG / WebP)。换图后在上方预览里直接拖拽,就能调整要露出的位置,点下方「保存」一起生效。',
    'avatar_label_short' => '头像',
    'banner_label_short' => '横幅',
    'banner_hint' => '会显示在你个人主页最上方的封面图。没上传就用你选的渐变配色。',
    'banner_upload' => '上传横幅',
    'banner_change' => '更换横幅',
    'banner_remove' => '移除横幅',
    'avatar_change2' => '更换',
    'avatar_upload2' => '上传',
    'remove' => '移除',
    'reposition_hint' => '拖拽可调整位置',
    'adjust' => '调整范围',
    'crop_avatar_title' => '调整头像显示范围',
    'crop_banner_title' => '调整横幅显示范围',
    'crop_hint' => '拖拽方框选择要保留的范围,拉动边角可缩放。只有框内的部分会被保存。',
    'crop_processing' => '处理中…',
    'crop_cancel' => '取消',
    'crop_apply' => '应用',
    'section_basics' => '基本资料',
    'section_theme' => '个人主页配色',
    'section_privacy' => '公开设置',

    'avatar_hint' => 'JPG / PNG / WebP,最大 2MB。',
    'avatar_upload' => '上传头像',
    'avatar_change' => '更换头像',
    'avatar_remove' => '移除头像',

    'name_label' => '昵称',
    'city_label' => '所在城市',
    'city_placeholder' => '例如:北京、上海、Tokyo',
    'looking_for_label' => '想认识什么样的人',
    'looking_for_placeholder' => '例如:想找一起玩桌游、聊得来的对象',
    'bio_label' => '自我介绍',
    'bio_placeholder' => '写几句关于你自己 —— 喜欢什么、在找什么样的关系……',

    'theme_hint' => '选一个颜色,应用在你的个人主页横幅与强调色上。',

    'public_label' => '公开我的个人主页',
    'public_hint' => '打开后,别人可以通过你的个人主页链接看到上面这些资料(不含 email 与游玩记录)。关闭则只有你自己看得到。',
    'public_on' => '公开中',
    'public_off' => '未公开',
    'show_traits_label' => '在公开主页显示我的性癖属性',
    'show_boards_label' => '在公开主页显示我制作的棋盘',
    'public_sections_hint' => '这些只有在「公开我的个人主页」打开时才会出现。',

    'save' => '保存',

    // 公开主页(别人看到的)
    'public_meta' => ':name 的个人主页',
    'lives_in' => '住在 :city',
    'looking_for_title' => '想认识',
    'top_trait_title' => '性癖属性',
    'boards_title' => '我制作的棋盘',
    'boards_play' => '去玩',
    'top_trait_take' => '也来测测自己的属性',
    'no_bio' => '这个人还没写自我介绍。',
    'member_since' => ':date 加入',
    'this_is_you' => '这是你的公开主页 —— 别人看到的就是这个样子。',
    'edit_mine' => '编辑',
    'private_notice' => '这个个人主页尚未公开。',

    // 发现(探索页)
    'discover_title' => '发现',
    'discover_heading' => '发现站上的人',
    'discover_intro' => '这里是开放了公开个人主页的用户。想被找到的话,到你的个人主页打开「公开」。',
    'discover_search' => '搜索',
    'discover_city_placeholder' => '按城市搜索,例如:北京',
    'discover_count' => '共 :n 位公开了个人主页',
    'discover_empty' => '目前这里还没有人 —— 成为第一个公开个人主页的人吧。',
    'discover_empty_city' => '「:city」目前没有公开的个人主页。',
    'discover_looking' => '想认识:',
    'discover_view' => '看看',
];
