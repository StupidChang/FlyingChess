<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->string('session_id', 64);
            $table->string('player_name', 30);
            $table->enum('color', ['yellow', 'blue', 'green', 'red']);
            $table->boolean('is_host')->default(false);
            $table->timestamps();

            $table->unique(['game_id', 'color']);
            $table->unique(['game_id', 'session_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_players');
    }
};
