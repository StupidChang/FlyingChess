<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('board_squares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('board_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('position');
            $table->string('text', 200)->default('');
            $table->string('color', 20)->default('normal');
            $table->timestamps();

            $table->unique(['board_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('board_squares');
    }
};
