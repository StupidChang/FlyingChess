<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('boards', function (Blueprint $table) {
            $table->unsignedSmallInteger('canvas_rows')->default(11)->after('is_default');
            $table->unsignedSmallInteger('canvas_cols')->default(13)->after('canvas_rows');
            $table->json('path_data')->nullable()->after('canvas_cols');
        });

        // Backfill existing boards with cross-shape defaults
        DB::table('boards')->update([
            'canvas_rows' => 11,
            'canvas_cols' => 13,
            'path_data'   => json_encode([
                'all'    => range(0, 22),
                'male'   => null,
                'female' => null,
            ]),
        ]);
    }

    public function down(): void
    {
        Schema::table('boards', function (Blueprint $table) {
            $table->dropColumn(['canvas_rows', 'canvas_cols', 'path_data']);
        });
    }
};
