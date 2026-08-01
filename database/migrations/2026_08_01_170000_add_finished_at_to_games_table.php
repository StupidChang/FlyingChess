<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 這一局什麼時候結束的。
 *
 * 遊玩紀錄本來只有「玩家什麼時候加入」,看不出玩了多久。用 updated_at 推算不行:
 * 那個欄位任何一次寫入都會動(改房名、有人中途加入、狀態變更),算出來的「時長」
 * 會跟著無關的操作漂移。結束時間要自己一個欄位,只在真的結束時寫一次。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->timestamp('finished_at')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->dropColumn('finished_at');
        });
    }
};
