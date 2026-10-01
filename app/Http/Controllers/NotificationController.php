<?php

namespace App\Http\Controllers;

use App\Notifications\SiteMessage;
use Illuminate\Http\Request;

/**
 * 站內通知。
 *
 *   GET  /notifications             全部通知;進來就算看過,全部標成已讀
 *   GET  /notifications/{id}        點一則:標成已讀,有連結就導過去
 *   POST /notifications/read-all    全部標為已讀(右上角面板用)
 *
 * 只要登入,不要求驗證信箱 —— 歡迎通知就是在還沒驗證的時候送到的。
 */
class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $notifications = $user->notifications()->paginate(20);

        // 先把這一頁渲染要用的未讀狀態抓下來,再標已讀 —— 這一次還要看得出哪幾則是新的
        $unreadIds = $notifications->getCollection()->whereNull('read_at')->pluck('id')->all();
        $user->unreadNotifications()->update(['read_at' => now()]);

        return view('notifications.index', compact('notifications', 'unreadIds'));
    }

    public function open(Request $request, string $id)
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        $url = SiteMessage::safeUrl($notification->data['url'] ?? null);

        return $url ? redirect($url) : redirect()->route('notifications.index');
    }

    public function readAll(Request $request)
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return back();
    }
}
