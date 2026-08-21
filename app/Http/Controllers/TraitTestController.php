<?php

namespace App\Http\Controllers;

use App\Models\TraitResult;
use App\Services\OgImageService;
use App\Services\TraitTestService;
use App\Support\LocaleHelper;
use App\Support\PremiumAccess;
use Illuminate\Http\Request;

/**
 * 枕邊屬性測驗。
 *
 * 三個頁面:
 *   GET  /trait-test              題目
 *   POST /trait-test              交卷 → 算分 → 導到結果頁
 *   GET  /trait-test/{slug}       某一種屬性的結果頁
 *
 * 結果頁刻意是**獨立網址**而不是交卷後的一次性畫面:20 種屬性就是 20 個可以被
 * 搜尋到、可以被分享的頁面。做成一次性畫面的話,這個測驗對 SEO 的貢獻是零。
 */
class TraitTestController extends Controller
{
    public function __construct(
        private readonly TraitTestService $service,
        private readonly OgImageService $og,
    ) {}

    public function show()
    {
        return view('trait-test.index', [
            'questions' => $this->service->questions(),
            'scale' => (array) trans('traits.scale'),
            // 20 個屬性的清單:題目頁要連得到 20 個結果頁,見那一頁的說明
            'items' => (array) trans('traits.items'),
            'translated' => $this->service->isTranslated(),
            // 只有翻好的語系才宣告 hreflang —— 其他語系這一頁是 noindex
            'hreflangLocales' => LocaleHelper::hreflangSet((array) config('traits.translated', [])),
        ]);
    }

    public function submit(Request $request)
    {
        $count = count(config('traits.questions'));

        $data = $request->validate([
            'a' => ['required', 'array', 'size:'.$count],
            'a.*' => ['required', 'integer', 'between:'.TraitTestService::MIN.','.TraitTestService::MAX],
        ], [], ['a' => trans('traits.title')]);

        $result = $this->service->score($data['a']);

        // 只有登入的人存得起來 —— 時間軸在個人資料頁,沒帳號就沒有地方顯示
        if ($user = $request->user()) {
            TraitResult::create([
                'user_id' => $user->id,
                'top_trait' => $result['top'],
                'traits' => $result['traits'],
                'axes' => $result['axes'],
            ]);
        }

        /* 分數放 session 帶到結果頁,不放網址。放網址的話會產生無限多個帶參數的
           結果網址,對 SEO 是災難(同一頁被收錄成幾千個),而且別人一看網址就
           知道怎麼偽造分數。 */
        return redirect()
            ->route('trait-test.result', ['slug' => $this->service->slug($result['top'])])
            ->with('trait_result', $result);
    }

    /**
     * 某一種屬性的頁面。
     *
     * 剛交完卷的人會帶著自己的分數進來(session),看到的是完整結果;
     * 從搜尋或分享連結進來的人沒有分數,看到的是這個屬性本身的介紹 ——
     * 同一個網址,兩種深度。這樣頁面對搜尋引擎永遠有內容。
     */
    /**
     * 兩人對照(工具頁)。兩邊的型別放在網址上,所以整頁是可分享、可回訪的 ——
     * 但**刻意 noindex**:20 型兩兩就是 190 種組合,內容是同一批文字重新排列,
     * 生成成可索引的頁面等於自己跟自己搶字。要吃 SEO 的話該手寫精選幾組,
     * 不是把組合數當頁數。
     */
    public function compare(Request $request)
    {
        $a = $this->service->keyFromSlug((string) $request->query('a', ''));
        $b = $this->service->keyFromSlug((string) $request->query('b', ''));

        /* 深入那一段(合拍／磨合／給對方的話)在結果頁就是付費內容,這裡必須是
           同一條線 —— 不然對照頁就成了繞過付費牆的入口。免費看得到的是四條
           光譜怎麼疊,那跟計分依據同一個層級。 */
        $unlocked = PremiumAccess::content($request->user());

        return view('trait-test.compare', [
            'items' => (array) trans('traits.items'),
            'aKey' => $a,
            'bKey' => $b,
            'a' => $a === null ? null : $this->service->item($a),
            'b' => $b === null ? null : $this->service->item($b),
            'comparison' => $a === null || $b === null ? null : $this->service->comparison($a, $b),
            'translated' => $this->service->isTranslated(),
            'unlocked' => $unlocked,
        ]);
    }

    public function result(Request $request, string $slug)
    {
        $key = $this->service->keyFromSlug($slug);
        abort_if($key === null, 404);

        $result = $request->session()->get('trait_result');

        // 別人的結果不能套在這一頁上 —— 網址與分數對不起來會很混亂
        if ($result && ($result['top'] ?? null) !== $key) {
            $result = null;
        }

        /* 深入解讀要看廣告或當會員才看得到。鎖住的時候**不渲染**那段內容 ——
           塞進 HTML 再用 CSS 遮起來,等於檢視原始碼就破解了。 */
        $unlocked = PremiumAccess::content($request->user());

        return view('trait-test.result', [
            'key' => $key,
            'item' => $this->service->item($key),
            'result' => $result,
            'axes' => $this->service->axes(),
            'items' => (array) trans('traits.items'),
            'translated' => $this->service->isTranslated(),
            // 只有翻好的語系才宣告 hreflang —— 其他語系這一頁是 noindex
            'hreflangLocales' => LocaleHelper::hreflangSet((array) config('traits.translated', [])),
            'unlocked' => $unlocked,
            'axisReading' => $unlocked && $result ? $this->service->axisReading($result['axes']) : [],
            /* 依據與共現都是從權重表現算的,**不上鎖**。免費的人至少要拿得到
               「這一型是什麼」與「這個數字怎麼來的」;鎖住的是「你該怎麼做」
               那一半(深入解讀、配對、光譜逐條)。 */
            'basis' => $this->service->basis($key),
            'related' => $this->service->related($key),
            'confidence' => $result ? $this->service->confidence($result) : [],
            /* 這一型專屬的分享卡片。`?v=` 是內容指紋 —— Facebook 這類抓取器按網址
               記憶,不換網址的話改了文案也只會沿用它上次抓到的舊圖。 */
            'ogImage' => route('trait-test.og', ['slug' => $slug]).'?v='
                .$this->og->fingerprint('trait', $key, app()->getLocale()),
        ]);
    }
}
