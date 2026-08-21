<?php

namespace App\Http\Controllers;

use App\Services\OgImageService;
use App\Services\RepressionTestService;
use App\Services\TraitTestService;
use Illuminate\Http\Response;

/**
 * 分享卡片(og:image)的端點。
 *
 *   GET /{locale}/trait-test/{slug}/og.png
 *   GET /{locale}/repression-test/{slug}/og.png
 *
 * 為什麼是即時產生 + 檔案快取,而不是事先產好一批靜態圖:卡片文字來自語系檔,
 * 而這台機器上改 lang 檔是存檔即生效(不經過 build)。快取鍵含語系檔 mtime,
 * 改完文案下一次抓取就是新圖,不必記得跑任何指令。
 *
 * 網址帶 `?v=` 指紋是給對面的快取用的 —— Facebook 這類抓取器是按網址記憶的,
 * 不換網址,改了文案也只會沿用它上次抓到的舊圖。
 */
class OgImageController extends Controller
{
    public function __construct(
        private readonly OgImageService $og,
        private readonly TraitTestService $traits,
        private readonly RepressionTestService $repression,
    ) {}

    public function traitTest(string $slug): Response
    {
        $key = $this->traits->keyFromSlug($slug);
        abort_if($key === null, 404);

        return $this->serve('trait', $key, fn () => $this->og->traitCard($key));
    }

    public function repressionTest(string $slug): Response
    {
        $key = $this->repression->keyFromSlug($slug);
        abort_if($key === null, 404);

        return $this->serve('repression', $key, fn () => $this->og->repressionCard($key));
    }

    /**
     * 有快取就送檔,沒有就畫一張再送。
     *
     * 畫不出來(環境缺 GD 或缺中文字型)時退回站台預設圖,而不是回 500 ——
     * 分享預覽壞掉不該讓整個連結看起來像死站。
     */
    private function serve(string $kind, string $key, callable $render): Response
    {
        if (! $this->og->available()) {
            return redirect()->away(asset('images/174655ssvy4mu6pwyllysm.jpg'));
        }

        $dir = (string) config('og.cache_dir');
        $path = $dir.'/'.app()->getLocale().'-'.$kind.'-'.$key.'-'
            .$this->og->fingerprint($kind, $key, app()->getLocale()).'.png';

        if (! is_file($path)) {
            if (! is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }

            /* 先寫暫存檔再 rename。同一張卡片同時被幾個抓取器要走的時候,直接寫
               目標檔會讓其中一個讀到只寫了一半的 PNG。rename 在同一個檔案系統上
               是原子操作。 */
            $tmp = $path.'.'.getmypid().'.tmp';
            if (@file_put_contents($tmp, $render()) !== false) {
                @rename($tmp, $path);
            } else {
                // 寫不進去(權限)也還是要把圖送出去,只是每次都重畫。
                return $this->png($render());
            }
        }

        return $this->png((string) file_get_contents($path));
    }

    private function png(string $data): Response
    {
        return response($data, 200, [
            'Content-Type' => 'image/png',
            'Content-Length' => (string) strlen($data),
            'Cache-Control' => 'public, max-age='.(int) config('og.ttl'),
        ]);
    }
}
