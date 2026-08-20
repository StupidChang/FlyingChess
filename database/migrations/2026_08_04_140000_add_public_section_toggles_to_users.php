<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * 公開個人頁上「要不要顯示某個區塊」的開關。預設顯示;只有在 profile_public
 * 打開時才有意義(整個公開頁本來就要 opt-in)。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('show_traits')->default(true);
            $table->boolean('show_boards')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['show_traits', 'show_boards']);
        });
    }
};
