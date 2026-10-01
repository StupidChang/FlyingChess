<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * 站內回報:錯誤、題目建議、功能想法。
 *
 * 之前唯一的管道是 SUPPORT_EMAIL。要開信箱、要打地址,而使用者按下「回報問題」
 * 的那一刻通常是遇到 bug 正在煩、或剛好想到一個題目 —— 多一步就會放棄。
 *
 * 兩個刻意的決定:
 *
 * 1. **message 不套 NoBlockedWords。** 這一點跟站上其他所有使用者輸入相反,
 *    而且是故意的:如果有人要回報「社群棋盤上有人寫了 XXX」,擋詞規則會讓他
 *    根本送不出這份檢舉。回報管道套內容過濾等於把最需要知道的事情擋在門外。
 *    防濫用交給路由的 throttle:5,60 與蜜罐,不靠關鍵字。
 *
 * 2. **不需要登入。** 遇到 bug 的人有很高比例根本還沒註冊,而「先去註冊才能
 *    告訴我們哪裡壞了」是最好的勸退方式。登入的人會自動帶上 user_id。
 */
class FeedbackController extends Controller
{
    public function show(Request $request)
    {
        return view('feedback.index', [
            // 從公告或頁尾按過來時會帶 ?from=,填進表單當作「在哪一頁遇到的」
            'pagePath' => Feedback::sanitizePageUrl($request->query('from')),
            // 從「回報這一題」之類的入口按過來時帶 ?type=prompt,直接選好類型
            'presetType' => in_array($request->query('type'), Feedback::TYPES, true)
                ? $request->query('type')
                : Feedback::TYPE_BUG,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(Feedback::TYPES)],
            // 上限 2000:再長的內容 textarea 本身就不好寫,而且真的講不完的人
            // 會留聯絡方式。下限 3 是為了讓「壞了」「太少」這種真實回報過得去。
            'message' => ['required', 'string', 'min:3', 'max:2000'],
            'contact' => ['nullable', 'string', 'max:120'],
            'page_path' => ['nullable', 'string', 'max:500'],
            // 蜜罐:畫面上看不到,只有機器人會填
            'website' => ['nullable', 'string', 'max:255'],
        ]);

        /* 蜜罐中了就當作沒這回事 —— 一樣回覆成功畫面,不告訴對方被擋掉了。
           回一個錯誤只是在教機器人下一次要避開哪個欄位。 */
        if (! empty($data['website'])) {
            return redirect()->route('feedback.show')->with('feedback_ok', true);
        }

        $feedback = Feedback::create([
            'type' => $data['type'],
            'message' => $data['message'],
            // validate() 不會回傳沒送出來的 nullable 欄位,所以每一個都要給預設
            // 會員不用自己填:直接用帳號的 email,表單送來的值不採信(畫面上也沒有那一格)
            'contact' => $request->user()?->email ?: (($data['contact'] ?? null) ?: null),
            'page_path' => Feedback::sanitizePageUrl($data['page_path'] ?? null),
            'locale' => app()->getLocale(),
            'user_id' => $request->user()?->id,
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 255) ?: null,
            'status' => Feedback::STATUS_NEW,
        ]);

        /* 帶回編號:之後使用者寫信或再回報時說「#123 那則」,後台一搜就找到。
           蜜罐那一支刻意不給編號 —— 沒有真的存,編號只會是假的。 */
        return redirect()->route('feedback.show')
            ->with('feedback_ok', true)
            ->with('feedback_id', $feedback->id);
    }
}
