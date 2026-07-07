<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('boards', function (Blueprint $table) {
            // null = private draft; pending = awaiting review; approved = live in community; rejected = sent back
            $table->string('publish_status', 20)->nullable()->index();
            $table->timestamp('published_at')->nullable();
            // Admin feedback shown to the owner on rejection
            $table->string('publish_note', 200)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('boards', function (Blueprint $table) {
            $table->dropColumn(['publish_status', 'published_at', 'publish_note']);
        });
    }
};
