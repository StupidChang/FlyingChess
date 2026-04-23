<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('truth_dare_cards', function (Blueprint $table) {
            $table->id();
            $table->string('category', 20); // truth, dare, couple, party
            $table->text('content');
            $table->string('tier', 10)->default('free'); // free, premium
            $table->timestamps();

            $table->index(['category', 'tier']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('truth_dare_cards');
    }
};
