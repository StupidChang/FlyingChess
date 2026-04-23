<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // SQLite: recreate table to change color from enum to varchar
        // and remove unique(game_id, color) constraint.
        // This allows truth_or_dare players to all have color='none'.

        if (Schema::hasTable('game_players_temp')) {
            DB::statement("DROP TABLE game_players_temp");
        }

        DB::statement("CREATE TABLE game_players_temp AS SELECT * FROM game_players");
        Schema::drop('game_players');

        DB::statement("
            CREATE TABLE game_players (
                id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                game_id INTEGER NOT NULL,
                session_id VARCHAR(64) NOT NULL,
                player_name VARCHAR(30) NOT NULL,
                color VARCHAR(10) NOT NULL DEFAULT 'none',
                is_host TINYINT(1) NOT NULL DEFAULT 0,
                created_at DATETIME,
                updated_at DATETIME,
                FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE
            )
        ");

        DB::statement("CREATE UNIQUE INDEX game_players_game_id_session_id_unique ON game_players (game_id, session_id)");

        DB::statement("INSERT INTO game_players SELECT * FROM game_players_temp");
        DB::statement("DROP TABLE game_players_temp");
    }

    public function down(): void
    {
        // Not reversible safely
    }
};
