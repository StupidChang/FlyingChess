<?php

namespace App\Console\Commands;

use App\Models\Board;
use App\Models\GamePrompt;
use App\Models\TruthDareCard;
use App\Models\WheelSegment;
use App\Services\CardGameService;
use App\Services\DiceGameService;
use App\Services\KingGameService;
use App\Services\WheelGameService;
use App\Services\WhoMostLikelyService;
use App\Support\ContentTranslations;
use App\Support\LocaleHelper;
use Illuminate\Console\Command;

/**
 * 列出站上內建內容裡,哪些句子在某個語系還沒有翻譯。
 *
 * 內建內容 = 範本／預設棋盤(名稱、說明、格子、進場轉盤)、真心話大冒險題卡、
 * 轉盤選項、四個小遊戲的題庫(資料表與程式碼預設)。使用者自己的棋盤不算。
 *
 * 字典是以繁中原文當 key 的,所以後台改了一句題目,那一句的翻譯就會對不上、
 * 退回繁中 —— 這支就是用來抓這種漏網之魚的。改完內容跑一次:
 *
 *   php artisan content:translation-coverage            # 全部語系的摘要
 *   php artisan content:translation-coverage --locale=en --list
 */
class ContentTranslationCoverage extends Command
{
    protected $signature = 'content:translation-coverage
                            {--locale= : 只看一個語系(en、zh_CN、ja)}
                            {--list : 把缺的句子逐句印出來}';

    protected $description = 'Report built-in content (boards, cards, wheel, prompts) missing a translation';

    /**
     * 目前資料庫裡所有的內建內容,依種類分組。
     *
     * @return array<string, array<int, string>>
     */
    public static function sources(): array
    {
        $boards = Board::with('squares')
            ->where(fn ($q) => $q->where('is_template', true)->orWhere('is_default', true))
            ->get();

        $out = ['boards' => [], 'squares' => [], 'cards' => [], 'wheel' => [], 'prompts' => []];

        foreach ($boards as $board) {
            $out['boards'][] = $board->getRawOriginal('name');
            $out['boards'][] = $board->getRawOriginal('description');
            foreach ($board->squares as $sq) {
                $out['squares'][] = $sq->getRawOriginal('text');
            }
            foreach ((array) ($board->start_wheel['segments'] ?? []) as $seg) {
                $out['squares'][] = $seg['text'] ?? null;
            }
        }
        foreach (Board::DEFAULT_START_WHEEL as $seg) {
            $out['squares'][] = $seg['text'];
        }

        $out['cards'] = TruthDareCard::query()->pluck('content')->all();
        $out['wheel'] = WheelSegment::query()->pluck('content')->all();
        foreach (WheelGameService::defaultPools() as $items) {
            array_push($out['wheel'], ...array_values($items));
        }
        $out['prompts'] = GamePrompt::query()->pluck('content')->all();

        foreach ([WhoMostLikelyService::class, CardGameService::class, KingGameService::class, DiceGameService::class] as $service) {
            foreach ($service::defaultPools() as $items) {
                array_push($out['prompts'], ...array_values($items));
            }
        }

        // 只留需要翻的:有中文字的句子,去重
        return array_map(
            fn ($texts) => array_values(array_unique(array_filter(
                array_map(fn ($t) => is_string($t) ? ContentTranslations::normalize($t) : null, $texts),
                fn ($t) => $t !== null && preg_match('/\p{Han}/u', $t),
            ))),
            $out,
        );
    }

    public function handle(): int
    {
        $locales = $this->option('locale')
            ? [$this->option('locale')]
            : array_values(array_diff(array_keys(LocaleHelper::supported()), [LocaleHelper::defaultLocale()]));

        $sources = self::sources();
        $rows = [];
        $missingTotal = 0;

        foreach ($locales as $locale) {
            foreach ($sources as $kind => $texts) {
                $missing = array_values(array_filter($texts, fn ($t) => ContentTranslations::lookup($t, $locale) === null));
                $missingTotal += count($missing);
                $rows[] = [$locale, $kind, count($texts), count($missing)];

                if ($this->option('list')) {
                    foreach ($missing as $text) {
                        $this->line("  [{$locale}/{$kind}] ".str_replace("\n", '⏎', $text));
                    }
                }
            }
        }

        $this->table(['locale', 'kind', 'sentences', 'missing'], $rows);

        return $missingTotal === 0 ? self::SUCCESS : self::FAILURE;
    }
}
