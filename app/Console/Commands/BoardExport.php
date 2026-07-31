<?php

namespace App\Console\Commands;

use App\Models\Board;
use Illuminate\Console\Command;

/**
 * Dump a board to an editable file. Pair with board:import — export, edit the
 * texts in a spreadsheet or editor, import back.
 *
 * json = full fidelity (canvas size, path_data, every square). Use this to clone
 *        a board or hand-author a new one.
 * csv  = squares only, one row per square. Easiest for bulk text rewrites;
 *        import it with --into=<board> so the original layout is reused.
 */
class BoardExport extends Command
{
    protected $signature = 'board:export
        {board : Board id}
        {--format=json : json or csv}
        {--out= : Write here instead of stdout}';

    protected $description = 'Export a board (layout + square texts) to JSON or CSV';

    public function handle(): int
    {
        $board = Board::with(['squares' => fn ($q) => $q->orderBy('position')])->find($this->argument('board'));

        if (! $board) {
            $this->error('Board not found: '.$this->argument('board'));

            return self::FAILURE;
        }

        $format = strtolower($this->option('format'));

        if (! in_array($format, ['json', 'csv'], true)) {
            $this->error('--format must be json or csv');

            return self::FAILURE;
        }

        $payload = $format === 'json' ? $this->toJson($board) : $this->toCsv($board);

        if ($out = $this->option('out')) {
            file_put_contents($out, $payload);
            $this->info("Wrote {$board->squares->count()} squares to {$out}");

            return self::SUCCESS;
        }

        $this->line($payload);

        return self::SUCCESS;
    }

    private function toJson(Board $board): string
    {
        return json_encode([
            'name' => $board->name,
            'description' => $board->description,
            'canvas_rows' => $board->canvas_rows,
            'canvas_cols' => $board->canvas_cols,
            'is_template' => (bool) $board->is_template,
            'is_premium_template' => (bool) $board->is_premium_template,
            'path_data' => $board->path_data,
            'start_wheel' => $board->start_wheel,
            'capture_enabled' => (bool) $board->capture_enabled,
            'squares' => $board->squares->map(fn ($s) => [
                'position' => $s->position,
                'row' => $s->grid_row,
                'col' => $s->grid_col,
                'color' => $s->color,
                'fly_to' => $s->fly_to,
                'move_steps' => $s->move_steps,
                'skip_turn' => (bool) $s->skip_turn,
                'text' => $s->text,
            ])->all(),
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT).PHP_EOL;
    }

    /**
     * Newlines are written as a literal \n so every square stays on one CSV row —
     * board:import converts them back. Keep that convention when hand-editing.
     */
    private function toCsv(Board $board): string
    {
        $fh = fopen('php://temp', 'r+');
        fputcsv($fh, ['position', 'row', 'col', 'color', 'fly_to', 'move_steps', 'skip_turn', 'text']);

        foreach ($board->squares as $s) {
            fputcsv($fh, [
                $s->position,
                $s->grid_row,
                $s->grid_col,
                $s->color,
                $s->fly_to,
                $s->move_steps,
                $s->skip_turn ? 1 : 0,
                str_replace("\n", '\n', (string) $s->text),
            ]);
        }

        rewind($fh);
        $csv = stream_get_contents($fh);
        fclose($fh);

        return $csv;
    }
}
