<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('board_squares', function (Blueprint $table) {
            $table->unsignedSmallInteger('fly_to')->nullable()->after('color');
        });
    }

    public function down(): void
    {
        Schema::table('board_squares', function (Blueprint $table) {
            $table->dropColumn('fly_to');
        });
    }
};
