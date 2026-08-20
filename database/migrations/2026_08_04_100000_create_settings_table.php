<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * 通用的鍵值設定表。第一個用途是「後台可改的定價」—— 價格結構的預設仍在
 * config/premium.php,這裡只存管理員在後台改過的覆寫值(見 App\Models\Setting
 * 與 AppServiceProvider 的 mergePricingOverrides)。value 存 JSON 字串。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
