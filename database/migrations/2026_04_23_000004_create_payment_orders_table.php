<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('order_no', 30)->unique();
            $table->unsignedInteger('amount');
            $table->string('status', 20)->default('pending'); // pending, paid, failed
            $table->string('trade_no')->nullable(); // ECPay TradeNo
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_orders');
    }
};
