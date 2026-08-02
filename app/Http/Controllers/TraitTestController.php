<?php

namespace App\Http\Controllers;

use App\Models\TraitResult;
use App\Services\TraitTestService;
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
    public function __construct(private readonly TraitTestService $service) {}

    public function show()
    {
        return view('trait-test.index', [
            'questions' => $this->service->questions(),
            'scale' => (array) trans('traits.scale'),
            'translated' => $this->service->isTranslated(),
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
    public function result(Request $request, string $slug)
    {
        $key = $this->service->keyFromSlug($slug);
        abort_if($key === null, 404);

        $result = $request->session()->get('trait_result');

        // 別人的結果不能套在這一頁上 —— 網址與分數對不起來會很混亂
        if ($result && ($result['top'] ?? null) !== $key) {
            $result = null;
        }

        return view('trait-test.result', [
            'key' => $key,
            'item' => $this->service->item($key),
            'result' => $result,
            'axes' => $this->service->axes(),
            'items' => (array) trans('traits.items'),
            'translated' => $this->service->isTranslated(),
        ]);
    }
}
