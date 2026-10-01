<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * games.status 加上 'abandoned'(閒置被 games:close-idle 關掉的場次)。
 *
 * 原本是 enum('waiting','playing','finished'),在 SQLite 上是一條 CHECK 約束,
 * 寫進其他值會直接被擋。SQLite 改不了 CHECK,只能重建整張表。
 *
 * ⚠ 不能用 ->change():game_players 有 FOREIGN KEY ... ON DELETE CASCADE 指向 games,
 * 重建時 DROP 舊表會把所有玩家紀錄一起刪掉。所以手動重建,並且在**交易外**
 * 先關掉 foreign_keys(PRAGMA foreign_keys 在交易裡是無效的,因此 $withinTransaction = false)。
 */
return new class extends Migration
{
    public $withinTransaction = false;

    private const COLUMNS = 'id, code, status, max_players, game_state, created_at, updated_at, game_type, is_private, finished_at, origin_locale, origin_referer';

    public function up(): void
    {
        $this->rebuild("'waiting', 'playing', 'finished', 'abandoned'");
    }

    public function down(): void
    {
        DB::table('games')->where('status', 'abandoned')->update(['status' => 'finished']);
        $this->rebuild("'waiting', 'playing', 'finished'");
    }

    private function rebuild(string $allowed): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('games', function (Blueprint $table) {
                $table->string('status')->default('waiting')->change();
            });

            return;
        }

        DB::statement('PRAGMA foreign_keys = OFF');

        try {
            DB::transaction(function () use ($allowed) {
                DB::statement(<<<SQL
                    CREATE TABLE "games__new" (
                        "id" integer primary key autoincrement not null,
                        "code" varchar not null,
                        "status" varchar check ("status" in ({$allowed})) not null default 'waiting',
                        "max_players" integer not null default '4',
                        "game_state" text,
                        "created_at" datetime,
                        "updated_at" datetime,
                        "game_type" varchar not null default 'flying_chess',
                        "is_private" tinyint(1) not null default '0',
                        "finished_at" datetime,
                        "origin_locale" varchar,
                        "origin_referer" varchar
                    )
                SQL);
                DB::statement('INSERT INTO "games__new" ('.self::COLUMNS.') SELECT '.self::COLUMNS.' FROM "games"');
                DB::statement('DROP TABLE "games"');
                DB::statement('ALTER TABLE "games__new" RENAME TO "games"');
                DB::statement('CREATE UNIQUE INDEX "games_code_unique" on "games" ("code")');
            });
        } finally {
            DB::statement('PRAGMA foreign_keys = ON');
        }
    }
};
