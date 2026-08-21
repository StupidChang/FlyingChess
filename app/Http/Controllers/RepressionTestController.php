<?php

namespace App\Http\Controllers;

use App\Services\RepressionTestService;
use App\Support\LocaleHelper;
use App\Support\PremiumAccess;
use Illuminate\Http\Request;

/**
 * 性壓抑指數測驗。
 *
 * 三個頁面:
 *   GET  /repression-test              題目
 *   POST /repression-test              交卷 → 算分 → 導到結果頁
 *   GET  /repression-test/{slug}       某一個級距的結果頁
 *
 * 和屬性測驗一樣,結果頁刻意是**獨立網址**而不是交卷後的一次性畫面:五個級距就是
 * 五個可以被搜尋到、可以被分享的頁面。做成一次性畫面的話,這個測驗對 SEO 的貢獻
 * 是零。級距只有五個(屬性測驗有 20 個),所以每一頁的內容要夠厚,不能只有一行。
 */
class RepressionTestController extends Controller
{
    public function __construct(private readonly RepressionTestService $service) {}

    public function show()
    {
        return view('repression-test.index', [
            'questions' => $this->service->questions(),
            'scale' => (array) trans('repression.scale'),
            'dimensions' => $this->service->dimensions(),
            'bands' => $this->service->bands(),
            'translated' => $this->service->isTranslated(),
            // 只有翻好的語系才宣告 hreflang —— 其他語系這一頁是 noindex
            'hreflangLocales' => LocaleHelper::hreflangSet((array) config('repression.translated', [])),
        ]);
    }

    public function submit(Request $request)
    {
        $count = count(config('repression.questions'));

        $data = $request->validate([
            'a' => ['required', 'array', 'size:'.$count],
            'a.*' => ['required', 'integer', 'between:'.RepressionTestService::MIN.','.RepressionTestService::MAX],
        ], [], ['a' => trans('repression.title')]);

        $result = $this->service->score($data['a']);

        /* 分數放 session 帶到結果頁,不放網址。放網址的話會產生無限多個帶參數的
           結果網址,對 SEO 是災難(同一頁被收錄成幾千個),而且別人一看網址就
           知道怎麼偽造分數。 */
        return redirect()
            ->route('repression-test.result', ['slug' => $this->service->slug($result['band'])])
            ->with('repression_result', $result);
    }

    /**
     * 某一個級距的頁面。
     *
     * 剛交完卷的人會帶著自己的分數進來(session),看到的是完整結果;
     * 從搜尋或分享連結進來的人沒有分數,看到的是這個級距本身的介紹 ——
     * 同一個網址,兩種深度。這樣頁面對搜尋引擎永遠有內容。
     */
    public function result(Request $request, string $slug)
    {
        $key = $this->service->keyFromSlug($slug);
        abort_if($key === null, 404);

        $result = $request->session()->get('repression_result');

        // 別人的結果不能套在這一頁上 —— 網址與分數對不起來會很混亂
        if ($result && ($result['band'] ?? null) !== $key) {
            $result = null;
        }

        /* 深入解讀要看廣告或當會員才看得到。鎖住的時候**不渲染**那段內容 ——
           塞進 HTML 再用 CSS 遮起來,等於檢視原始碼就破解了。 */
        $unlocked = PremiumAccess::content($request->user());

        return view('repression-test.result', [
            'key' => $key,
            'band' => $this->service->band($key),
            'bands' => $this->service->bands(),
            'result' => $result,
            'dimensions' => $this->service->dimensions(),
            'translated' => $this->service->isTranslated(),
            // 只有翻好的語系才宣告 hreflang —— 其他語系這一頁是 noindex
            'hreflangLocales' => LocaleHelper::hreflangSet((array) config('repression.translated', [])),
            'unlocked' => $unlocked,
            'reading' => $unlocked && $result ? $this->service->dimensionReading($result['dimensions']) : [],
            /* 依據**不上鎖**。免費的人至少要知道這個數字怎麼來的,不然「你 68 分」
               跟星座沒兩樣;鎖住的是「你該怎麼做」那一半(建議、三步、給對方的話、
               五個面向逐條)。 */
            'basis' => $this->service->basis(),
            'range' => $this->service->range($key),
            'confidence' => $result ? $this->service->confidence($result) : [],
        ]);
    }
}
