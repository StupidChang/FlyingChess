<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('game_players', function (Blueprint $table) {
            // Nullable: guests keep playing with session_id only. No FK constraint —
            // SQLite ALTER TABLE cannot add one, and users may be deleted while
            // their play history rows remain.
            $table->unsignedBigInteger('user_id')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('game_players', function (Blueprint $table) {
            $table->dropColumn('user_id');
        });
    }
};
