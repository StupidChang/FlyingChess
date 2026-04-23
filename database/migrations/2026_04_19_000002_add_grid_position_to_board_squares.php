<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Current cross-shape GRID_POS: position → [row, col] (1-based, 11×13 grid)
    private const CROSS_MAP = [
        0=>[1,6],  1=>[1,7],  2=>[2,7],  3=>[3,7],  4=>[4,7],
        5=>[5,8],  6=>[5,9],  7=>[5,10], 8=>[5,11], 9=>[5,12], 10=>[5,13],
        11=>[6,13],12=>[7,13],13=>[7,12],14=>[7,11],15=>[7,10],
        16=>[7,9], 17=>[7,8], 18=>[8,7], 19=>[9,7], 20=>[10,7],21=>[11,7],
        22=>[11,6],23=>[11,5],24=>[10,5],25=>[9,5],  26=>[8,5],
        27=>[7,4], 28=>[7,3], 29=>[7,2], 30=>[7,1],
        31=>[6,1], 32=>[5,1], 33=>[5,2], 34=>[5,3],  35=>[5,4],
        36=>[4,5], 37=>[3,5], 38=>[2,5], 39=>[1,5],
    ];

    public function up(): void
    {
        Schema::table('board_squares', function (Blueprint $table) {
            $table->unsignedSmallInteger('grid_row')->default(1)->after('fly_to');
            $table->unsignedSmallInteger('grid_col')->default(1)->after('grid_row');
        });

        // Backfill existing squares with cross-shape grid coordinates
        foreach (self::CROSS_MAP as $pos => [$row, $col]) {
            DB::table('board_squares')
                ->where('position', $pos)
                ->update(['grid_row' => $row, 'grid_col' => $col]);
        }
    }

    public function down(): void
    {
        Schema::table('board_squares', function (Blueprint $table) {
            $table->dropColumn(['grid_row', 'grid_col']);
        });
    }
};
