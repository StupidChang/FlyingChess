<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * 時間膠囊下架(2026-10-01)。下架當下三張表都是 0 列,沒有要寄出的提醒信。
 *
 * down() 不重建:功能的程式已經整份刪掉,重建空表也沒有東西會用它。
 * 真的要回來的話,舊的建表 migration 還在,從 git 歷史把功能一起拿回來。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('capsule_answers');
        Schema::dropIfExists('capsule_questions');
        Schema::dropIfExists('time_capsules');
    }

    public function down(): void
    {
        //
    }
};
