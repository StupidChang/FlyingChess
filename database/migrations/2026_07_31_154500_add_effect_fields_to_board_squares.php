<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Move squares used to work only because board.js regex-matched the Traditional
 * Chinese wording out of the square text (前進N格 / 後退N格 / 跳過). Any other
 * locale — including Simplified Chinese 前进 — silently produced no effect, so a
 * translated board would look right and play wrong.
 *
 * move_steps: signed, + forward / - backward. skip_turn: lose the next roll.
 * NULL/false means "no structured effect"; board.js then falls back to parsing
 * the text, which keeps hand-made boards working.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('board_squares', function (Blueprint $table) {
            $table->integer('move_steps')->nullable()->after('fly_to');
            $table->boolean('skip_turn')->default(false)->after('move_steps');
        });

        // Backfill from the existing zh-TW wording so current boards gain the
        // structured values without anyone re-editing them.
        foreach (DB::table('board_squares')->select('id', 'text')->get() as $row) {
            $text = (string) $row->text;
            $steps = null;
            $skip = false;

            if (preg_match('/前進\s*(\d+)\s*格/u', $text, $m)) {
                $steps = (int) $m[1];
            } elseif (preg_match('/後退\s*(\d+)\s*格/u', $text, $m)) {
                $steps = -(int) $m[1];
            }

            if (preg_match('/跳過|下一輪休息/u', $text)) {
                $skip = true;
            }

            if ($steps !== null || $skip) {
                DB::table('board_squares')->where('id', $row->id)
                    ->update(['move_steps' => $steps, 'skip_turn' => $skip]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('board_squares', function (Blueprint $table) {
            $table->dropColumn(['move_steps', 'skip_turn']);
        });
    }
};
