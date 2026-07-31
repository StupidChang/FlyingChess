<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Excluding the seven-day cooling-off period for digital content requires the
 * consumer's prior express consent. Recording when that consent was given turns
 * "our terms say so" into something we can actually evidence if a chargeback or
 * consumer complaint arrives. NULL on rows created before this existed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_orders', function (Blueprint $table) {
            $table->timestamp('consented_at')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('payment_orders', function (Blueprint $table) {
            $table->dropColumn('consented_at');
        });
    }
};
