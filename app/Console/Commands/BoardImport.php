<?php

namespace App\Console\Commands;

use App\Models\Board;
use App\Models\BoardSquare;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Build or rewrite a board from a file produced by board:export (or hand-authored
 * in the same shape).
 *
 *   json  full board — creates a new one, or replaces --into=<board> entirely.
 *   csv   squares only — requires --into, because a CSV carries no path_data and
 *         guessing it would silently break boards whose path covers only part of
 *         the squares (the cross layout has 40 squares but a 23-step path).
 *
 * Nothing is written unless validation passes; --dry-run reports and stops.
 */
class BoardImport extends Command
{
    protected $signature = 'board:import
        {file : Path to a .json or .csv file}
        {--into= : Replace this board id instead of creating a new board}
        {--name= : Override the board name}
        {--template : Mark the new board as a selectable template}
        {--premium : Mark the new board as a premium template}
        {--dry-run : Validate and report without writing}';

    protected $description = 'Import a board (layout + square texts) from JSON or CSV';

    /** Square types the editor accepts, plus the start/end markers presets use. */
    private const COLORS = [
        'start', 'end', 'normal', 'action', 'drink', 'dare',
        'truth', 'strip', 'move', 'male', 'female',
        'p1', 'p2', 'p3', 'p4',
    ];

    public function handle(): int
    {
        $file = $this->argument('file');

        if (! is_readable($file)) {
            $this->error("Cannot read file: {$file}");

            return self::FAILURE;
        }

        $isCsv = str_ends_with(strtolower($file), '.csv');
        $target = $this->option('into') ? Board::find($this->option('into')) : null;

        if ($this->option('into') && ! $target) {
            $this->error('Board not found: '.$this->option('into'));

            return self::FAILURE;
        }

        if ($isCsv && ! $target) {
            $this->error('CSV import needs --into=<board id> — a CSV carries no path data.');

            return self::FAILURE;
        }

        $parsed = $isCsv ? $this->parseCsv($file) : $this->parseJson($file);

        if ($parsed === null) {
            return self::FAILURE;
        }

        $errors = $this->validate($parsed, $target, $isCsv);

        if ($errors) {
            $this->error('Validation failed:');
            foreach ($errors as $e) {
                $this->line('  • '.$e);
            }

            return self::FAILURE;
        }

        $count = count($parsed['squares']);
        $verb = $target ? "replace board {$target->id} ({$target->name})" : 'create a new board';
        $this->info("OK — {$count} squares, would {$verb}.");

        if ($this->option('dry-run')) {
            $this->comment('Dry run: nothing written.');

            return self::SUCCESS;
        }

        $board = $this->write($parsed, $target, $isCsv);
        $this->info("Done. Board {$board->id}: {$board->name} ({$count} squares)");

        return self::SUCCESS;
    }

    private function parseJson(string $file): ?array
    {
        $data = json_decode(file_get_contents($file), true);

        if (! is_array($data) || ! isset($data['squares']) || ! is_array($data['squares'])) {
            $this->error('JSON must be an object with a "squares" array.');

            return null;
        }

        return $data;
    }

    private function parseCsv(string $file): ?array
    {
        $fh = fopen($file, 'r');
        $header = fgetcsv($fh);

        if (! $header || ! in_array('position', $header, true) || ! in_array('text', $header, true)) {
            fclose($fh);
            $this->error('CSV needs at least a "position" and a "text" column.');

            return null;
        }

        $squares = [];

        while (($row = fgetcsv($fh)) !== false) {
            if (count(array_filter($row, fn ($v) => $v !== null && $v !== '')) === 0) {
                continue;
            }

            $r = array_combine($header, array_pad($row, count($header), null));
            $squares[] = [
                'position' => (int) $r['position'],
                'row' => isset($r['row']) && $r['row'] !== '' ? (int) $r['row'] : null,
                'col' => isset($r['col']) && $r['col'] !== '' ? (int) $r['col'] : null,
                'color' => $r['color'] ?? null,
                'fly_to' => isset($r['fly_to']) && $r['fly_to'] !== '' ? (int) $r['fly_to'] : null,
                'move_steps' => isset($r['move_steps']) && $r['move_steps'] !== '' ? (int) $r['move_steps'] : null,
                'skip_turn' => ! empty($r['skip_turn']) && $r['skip_turn'] !== '0',
                'text' => str_replace('\n', "\n", (string) ($r['text'] ?? '')),
            ];
        }

        fclose($fh);

        return ['squares' => $squares];
    }

    private function validate(array $parsed, ?Board $target, bool $isCsv): array
    {
        $errors = [];
        $squares = $parsed['squares'];

        if (! $squares) {
            return ['File contains no squares.'];
        }

        $positions = array_column($squares, 'position');
        $dupes = array_keys(array_filter(array_count_values($positions), fn ($n) => $n > 1));

        if ($dupes) {
            $errors[] = 'Duplicate positions: '.implode(', ', $dupes);
        }

        foreach ($squares as $i => $s) {
            $where = "square #{$i} (position {$s['position']})";

            if ($s['color'] !== null && $s['color'] !== '' && ! in_array($s['color'], self::COLORS, true)) {
                $errors[] = "{$where}: unknown color '{$s['color']}' — allowed: ".implode(', ', self::COLORS);
            }

            if ($s['fly_to'] !== null && ! in_array($s['fly_to'], $positions, true)) {
                $errors[] = "{$where}: fly_to {$s['fly_to']} points at a position that is not in this file";
            }
        }

        // A CSV only supplies text, so it must line up with the board it patches.
        if ($isCsv && $target) {
            $existing = $target->squares()->pluck('position')->all();
            $missing = array_diff($positions, $existing);

            if ($missing) {
                $errors[] = 'CSV has positions board '.$target->id.' does not: '.implode(', ', $missing);
            }
        }

        if (! $isCsv) {
            $rows = $parsed['canvas_rows'] ?? null;
            $cols = $parsed['canvas_cols'] ?? null;

            foreach ($squares as $s) {
                if ($s['row'] === null || $s['col'] === null) {
                    $errors[] = "position {$s['position']}: JSON import needs row and col";
                    break;
                }

                if ($rows && ($s['row'] < 1 || $s['row'] > $rows)) {
                    $errors[] = "position {$s['position']}: row {$s['row']} outside canvas (1..{$rows})";
                }

                if ($cols && ($s['col'] < 1 || $s['col'] > $cols)) {
                    $errors[] = "position {$s['position']}: col {$s['col']} outside canvas (1..{$cols})";
                }
            }

            foreach ((array) ($parsed['path_data']['all'] ?? []) as $p) {
                if (! in_array($p, $positions, true)) {
                    $errors[] = "path_data.all references position {$p}, which has no square";
                }
            }
        }

        return array_slice($errors, 0, 20);
    }

    private function write(array $parsed, ?Board $target, bool $isCsv): Board
    {
        return DB::transaction(function () use ($parsed, $target, $isCsv) {
            // CSV patches text in place so the layout and path survive untouched.
            if ($isCsv) {
                foreach ($parsed['squares'] as $s) {
                    $target->squares()->where('position', $s['position'])->update([
                        'text' => $s['text'],
                        'move_steps' => $s['move_steps'],
                        'skip_turn' => $s['skip_turn'],
                    ]);
                }

                if ($name = $this->option('name')) {
                    $target->update(['name' => $name]);
                }

                return $target->fresh();
            }

            $board = $target ?? new Board;
            $board->fill([
                'name' => $this->option('name') ?: ($parsed['name'] ?? 'Imported board'),
                'description' => $parsed['description'] ?? null,
                'canvas_rows' => $parsed['canvas_rows'] ?? 11,
                'canvas_cols' => $parsed['canvas_cols'] ?? 11,
                'path_data' => $parsed['path_data'] ?? ['all' => array_column($parsed['squares'], 'position'), 'male' => null, 'female' => null],
                'start_wheel' => $parsed['start_wheel'] ?? null,
                'capture_enabled' => (bool) ($parsed['capture_enabled'] ?? true),
            ]);

            if ($this->option('template')) {
                $board->is_template = true;
            }

            if ($this->option('premium')) {
                $board->is_premium_template = true;
            }

            $board->save();
            $board->squares()->delete();

            foreach ($parsed['squares'] as $s) {
                BoardSquare::create([
                    'board_id' => $board->id,
                    'position' => $s['position'],
                    'text' => $s['text'],
                    'color' => $s['color'] ?: 'normal',
                    'fly_to' => $s['fly_to'],
                    'move_steps' => $s['move_steps'] ?? null,
                    'skip_turn' => (bool) ($s['skip_turn'] ?? false),
                    'grid_row' => $s['row'],
                    'grid_col' => $s['col'],
                ]);
            }

            return $board;
        });
    }
}
