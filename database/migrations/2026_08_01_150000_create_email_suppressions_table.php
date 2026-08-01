<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 不該再寄信過去的地址。
 *
 * 來源是 SES 透過 SNS 送來的退信與客訴通知。SES 自己的 account-level suppression
 * list 已經會擋掉硬退信,但那份清單我們看不到也查不到 —— 出事時只知道「寄不出去」
 * 卻不知道是誰、有多少。自己留一份的用途是**看得見**:退信率在漲的時候,那是
 * 寄信被停權之前唯一的預警。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_suppressions', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            // bounce（硬退信）| complaint（被標記為垃圾信）
            $table->string('reason', 16);
            // 通知裡的細節:bounceSubType / complaintFeedbackType 之類,方便事後判讀
            $table->string('detail')->nullable();
            $table->timestamp('created_at')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_suppressions');
    }
};
