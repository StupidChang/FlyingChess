<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Remember which language each recipient signed up / created a capsule in, so
 * mail sent outside a request (scheduler, queue) can still pick the right
 * translation. NULL means "unknown" and callers fall back to the site default.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('locale', 5)->nullable()->after('email');
        });

        Schema::table('time_capsules', function (Blueprint $table) {
            $table->string('locale', 5)->nullable()->after('notify_email');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('locale');
        });

        Schema::table('time_capsules', function (Blueprint $table) {
            $table->dropColumn('locale');
        });
    }
};
