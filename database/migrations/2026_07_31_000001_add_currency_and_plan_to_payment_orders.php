<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * payment_orders 原本只有一個 unsignedInteger amount,隱含「新台幣、整數元」。
 * 要收美元就撐不住了:US$7.99 存不進整數欄位,而且沒有幣別欄位的話,一筆 799 到底
 * 是 799 元台幣還是 7.99 美元無從分辨 —— 對帳與退款都會出事。
 *
 * 兩個決定:
 *
 * 1. amount 改為存「最小單位」(USD 用分、TWD/JPY 用元),欄位型別不變。金額不用
 *    浮點數是金流的基本要求,7.99 在 IEEE 754 裡不是精確值。
 *    既有資料不需要換算 —— 舊訂單都是台幣,而台幣沒有小數,最小單位就等於元。
 *
 * 2. 加上 plan,記下當初買的是哪個方案。付款成功要延展幾天是看方案(30 / 365),
 *    不能再讀 config 當下的值 —— 否則日後改了方案天數,連舊訂單的補發都會給錯。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_orders', function (Blueprint $table) {
            $table->string('currency', 3)->default('TWD')->after('amount');
            $table->string('plan', 20)->default('monthly')->after('currency');
        });

        // 既有訂單都是上線前的台幣月費單,明確標記而不是靠欄位預設值,
        // 這樣日後把 default 改掉也不會影響它們。
        DB::table('payment_orders')->update(['currency' => 'TWD', 'plan' => 'monthly']);
    }

    public function down(): void
    {
        Schema::table('payment_orders', function (Blueprint $table) {
            $table->dropColumn(['currency', 'plan']);
        });
    }
};
