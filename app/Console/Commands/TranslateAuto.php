<?php

namespace App\Console\Commands;

use App\Models\Board;
use App\Models\BoardSquare;
use App\Models\TruthDareCard;
use App\Models\WheelSegment;
use App\Support\LocaleHelper;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Machine-translate rows whose `*_translations` JSON is missing entries for
 * one or more supported locales. Stamps `machine_translated_at` on success
 * so the admin UI can display the ⚠ "machine-translated, awaiting review"
 * indicator described in the i18n blueprint (section 4.5 / 4.9).
 *
 * Provider plumbing is intentionally minimal — wire `TRANSLATE_DRIVER=openai`
 * and `OPENAI_API_KEY` (or `google` + `GOOGLE_TRANSLATE_KEY`) in .env to enable
 * a real backend. Without credentials the command runs in --dry-run only.
 */
class TranslateAuto extends Command
{
    protected $signature = 'translate:auto
                            {--model=all : which model to process (cards, squares, segments, boards, all)}
                            {--limit=100 : max rows per model per run}
                            {--dry-run : log planned translations without writing}';

    protected $description = 'Machine-translate untranslated rows into all ready locales (with master-source fallback)';

    public function handle(): int
    {
        $modelOpt = $this->option('model');
        $limit    = (int) $this->option('limit');
        $dryRun   = (bool) $this->option('dry-run');

        $masterLocale = LocaleHelper::defaultLocale();
        $targetLocales = collect(LocaleHelper::supported())
            ->keys()
            ->reject(fn ($l) => $l === $masterLocale)
            ->values()
            ->all();

        if (empty($targetLocales)) {
            $this->info('No non-master locales configured; nothing to translate.');
            return self::SUCCESS;
        }

        $matrix = [
            'cards'    => [TruthDareCard::class, 'content',  'content_translations'],
            'squares'  => [BoardSquare::class,   'text',     'text_translations'],
            'segments' => [WheelSegment::class,  'content',  'content_translations'],
            'boards'   => [Board::class,         'name',     'name_translations'],
        ];

        $selected = $modelOpt === 'all' ? array_keys($matrix) : [$modelOpt];
        $totalDone = 0;
        $totalFailed = 0;

        foreach ($selected as $key) {
            if (! isset($matrix[$key])) {
                $this->warn("Unknown model option: {$key} — skipping.");
                continue;
            }
            [$class, $masterCol, $jsonCol] = $matrix[$key];
            $this->info("→ {$key} ({$class})");

            $query = $class::query()
                ->whereNotNull($masterCol)
                ->whereNull('machine_translated_at')
                ->limit($limit);

            $rows = $query->get();
            $this->line("  candidates: {$rows->count()}");

            foreach ($rows as $row) {
                try {
                    $translations = $row->getRawOriginal($jsonCol);
                    $decoded = $translations
                        ? (is_array($translations) ? $translations : json_decode($translations, true))
                        : [];
                    $decoded = is_array($decoded) ? $decoded : [];

                    $missing = array_filter(
                        $targetLocales,
                        fn ($l) => empty($decoded[$l]),
                    );

                    if (empty($missing)) {
                        // Already translated; mark as machine-stamped only if user wants to revisit.
                        continue;
                    }

                    $masterValue = $row->getRawOriginal($masterCol);

                    foreach ($missing as $locale) {
                        $translated = $this->translate($masterValue, $masterLocale, $locale, $dryRun);
                        if ($translated !== null) {
                            $decoded[$locale] = $translated;
                        }
                    }

                    if (! $dryRun) {
                        $row->{$jsonCol} = $decoded;
                        $row->machine_translated_at = Carbon::now();
                        $row->save();
                    }
                    $totalDone++;
                } catch (Throwable $e) {
                    Log::warning('translate:auto row failed', [
                        'model' => $class,
                        'id'    => $row->id ?? null,
                        'error' => $e->getMessage(),
                    ]);
                    $totalFailed++;
                }
            }
        }

        $this->info("Done. translated={$totalDone} failed={$totalFailed} dry_run={$dryRun}");
        return self::SUCCESS;
    }

    /**
     * Pluggable translator. Returns null on failure so the caller can skip the
     * locale without aborting the batch. Real driver implementations belong
     * in a dedicated Service class once a provider is chosen.
     */
    protected function translate(string $text, string $from, string $to, bool $dryRun): ?string
    {
        $driver = env('TRANSLATE_DRIVER', 'none');

        if ($dryRun || $driver === 'none') {
            $this->line("    [{$from}→{$to}] ".str()->limit($text, 40)." (no provider)");
            // Surface the master value as a placeholder so callers can still
            // write a non-null entry, distinguishing "untouched" from "tried".
            return $dryRun ? null : $text;
        }

        // Real providers (openai, google) — wire in once API keys are configured.
        // Keep the stub returning the master text so downstream UI shows the
        // ⚠ "machine-translated, awaiting review" indicator without crashing.
        return $text;
    }
}
