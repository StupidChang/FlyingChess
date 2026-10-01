<?php

/*
 * Only the field names live here. The messages themselves come from the
 * framework's bundled en/validation.php, which Laravel merges underneath this
 * file. The names still have to exist, though: `fallback_locale` is zh_TW, so
 * an attribute missing here is looked up in lang/zh_TW/validation.php and the
 * English page would say "The 內容 field is required."
 */

return [
    'attributes' => [
        'name' => 'name',
        'email' => 'email',
        'password' => 'password',
        'title' => 'title',
        'description' => 'description',
        'content' => 'content',
        'text' => 'text',
        'message' => 'message',
        'contact' => 'contact details',
        'page_path' => 'page',
        'type' => 'type',
        'category' => 'category',
        'player_name' => 'player name',
        'players' => 'players',
        'players.*' => 'player name',
        'gender' => 'gender',
        'genders.*' => 'gender',
        'bio' => 'bio',
        'city' => 'city',
        'looking_for' => 'looking for',
        'avatar' => 'avatar',
        'banner' => 'cover image',
        'items' => 'items',
        'items.*' => 'item',
        'segments' => 'wheel options',
        'answers.*' => 'answer',
        'open_at' => 'opening date',
        'notify_email' => 'notification email',
        'publish_note' => 'note to the reviewer',
    ],
];
