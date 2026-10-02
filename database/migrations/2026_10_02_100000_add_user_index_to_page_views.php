<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 後台會員列表要查「每個會員最近一次瀏覽用的語系」(AdminController::users),
 * 那是每一列一次、依 user_id 找最新一筆的子查詢。沒有這個索引的話,每一列都要
 * 掃整張瀏覽紀錄,一頁 100 位會員就是掃 100 次。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('page_views', function (Blueprint $table) {
            $table->index(['user_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::table('page_views', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'id']);
        });
    }
};
