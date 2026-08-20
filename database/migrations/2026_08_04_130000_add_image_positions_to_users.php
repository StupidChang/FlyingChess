<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * 頭像 / 橫幅的顯示位置(焦點)。使用者上傳後可以拖曳決定要露出圖的哪一部分。
 *
 * 存成 0–100 的整數百分比(x, y),輸出時組成 object-position / background-position。
 * 用整數而不是自由字串 —— 位置值會被印進 inline style,整數從根本上杜絕 CSS 注入。
 * 預設 50 = 置中(和原本一樣)。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedTinyInteger('avatar_pos_x')->default(50);
            $table->unsignedTinyInteger('avatar_pos_y')->default(50);
            $table->unsignedTinyInteger('banner_pos_x')->default(50);
            $table->unsignedTinyInteger('banner_pos_y')->default(50);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['avatar_pos_x', 'avatar_pos_y', 'banner_pos_x', 'banner_pos_y']);
        });
    }
};
