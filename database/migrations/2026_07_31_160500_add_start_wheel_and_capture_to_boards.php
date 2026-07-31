<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Two per-board rules that used to be hardcoded in board.js.
 *
 * start_wheel — the entry wheel from the physical V8.0 board: before a piece is
 *   on the track, the roll is read off a six-slot wheel instead of moving. Only
 *   the slots flagged `enter` put the piece on the board. NULL = no wheel, so
 *   every existing board keeps starting immediately.
 *
 * capture_enabled — landing on an opponent sends them back to the start. Always
 *   on until now; some boards (and some couples) want it off.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('boards', function (Blueprint $table) {
            $table->json('start_wheel')->nullable()->after('path_data');
            $table->boolean('capture_enabled')->default(true)->after('start_wheel');
        });
    }

    public function down(): void
    {
        Schema::table('boards', function (Blueprint $table) {
            $table->dropColumn(['start_wheel', 'capture_enabled']);
        });
    }
};
