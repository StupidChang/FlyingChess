<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * 個人頁的橫幅圖(封面)。與 avatar_path 一樣存的是 upload disk 上的相對路徑
 * (見 config/profile.upload_disk —— 設好 R2 後就是 R2 上的 key)。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('banner_path')->nullable()->after('avatar_path');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('banner_path');
        });
    }
};
