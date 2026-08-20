<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * 個人化 / 聯誼的基礎欄位。
 *
 * profile_public 預設 false:成人向站點,個人資料在使用者「明確公開」之前一律不對
 * 外顯示。avatar_path 存的是 storage/app/public 底下的相對路徑(由 public/storage
 * 這個 symlink 對外),不是使用者可控的完整路徑。profile_theme 存的是 config/profile
 * 定義好的主題「代碼」,不是任意色碼 —— 顯示時只輸出白名單裡的色碼,不會有 CSS 注入。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('city', 60)->nullable();
            $table->string('looking_for', 120)->nullable();
            $table->text('bio')->nullable();
            $table->string('avatar_path')->nullable();
            $table->string('profile_theme', 20)->nullable();
            $table->boolean('profile_public')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['city', 'looking_for', 'bio', 'avatar_path', 'profile_theme', 'profile_public']);
        });
    }
};
