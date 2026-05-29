<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bucket_lists', function (Blueprint $table) {
            $table->id();
            $table->string('share_code', 10)->unique();
            $table->string('title', 100);
            $table->string('owner_token', 64);
            $table->string('partner_token', 64)->nullable();
            $table->timestamps();
        });

        Schema::create('bucket_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bucket_list_id')->constrained()->cascadeOnDelete();
            $table->string('content', 200);
            $table->string('proposer', 10);
            $table->string('owner_vote', 10)->nullable();
            $table->string('partner_vote', 10)->nullable();
            $table->timestamps();

            $table->index('bucket_list_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bucket_items');
        Schema::dropIfExists('bucket_lists');
    }
};
