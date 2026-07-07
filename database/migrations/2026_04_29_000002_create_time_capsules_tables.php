<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('time_capsules', function (Blueprint $table) {
            $table->id();
            $table->string('share_code', 10)->unique();
            $table->string('title', 100);
            $table->date('open_at');
            $table->timestamp('sealed_at')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->string('notify_email', 100)->nullable();
            $table->string('owner_token', 64);
            $table->string('partner_token', 64)->nullable();
            $table->boolean('reminder_sent')->default(false);
            $table->timestamps();

            $table->index('open_at');
            $table->index(['open_at', 'reminder_sent']);
        });

        Schema::create('capsule_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('capsule_id')->constrained('time_capsules')->cascadeOnDelete();
            $table->string('question', 200);
            $table->unsignedSmallInteger('position');
            $table->timestamps();

            $table->index('capsule_id');
        });

        Schema::create('capsule_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained('capsule_questions')->cascadeOnDelete();
            $table->string('role', 10); // 'owner' | 'partner'
            $table->text('answer');
            $table->timestamps();

            $table->unique(['question_id', 'role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('capsule_answers');
        Schema::dropIfExists('capsule_questions');
        Schema::dropIfExists('time_capsules');
    }
};
