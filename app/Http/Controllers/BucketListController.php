<?php

namespace App\Http\Controllers;

use App\Models\BucketItem;
use App\Models\BucketList;
use App\Rules\NoBlockedWords;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BucketListController extends Controller
{
    private const ROLE_COOKIE_PREFIX = 'bucket_role_';

    private const ROLE_COOKIE_DAYS = 60;

    public function lobby()
    {
        return view('bucket-list.lobby');
    }

    public function create(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'min:1', 'max:100', new NoBlockedWords],
        ], [
            'title.required' => '請輸入清單名稱',
            'title.max' => '清單名稱不可超過 100 字',
        ]);

        $ownerToken = Str::random(48);

        $list = BucketList::create([
            'title' => $data['title'],
            'owner_token' => $ownerToken,
        ]);

        return redirect()
            ->route('bucket-list.show', ['shareCode' => $list->share_code])
            ->withCookie(cookie(
                self::ROLE_COOKIE_PREFIX.$list->share_code,
                $ownerToken,
                self::ROLE_COOKIE_DAYS * 24 * 60
            ));
    }

    public function show(Request $request, string $shareCode)
    {
        $list = BucketList::where('share_code', $shareCode)->firstOrFail();
        $role = $this->resolveRole($request, $list);

        // First-time partner visitor — assign partner token
        $cookieJar = null;
        if ($role === 'partner-new') {
            $partnerToken = Str::random(48);
            $list->update(['partner_token' => $partnerToken]);
            $cookieJar = cookie(
                self::ROLE_COOKIE_PREFIX.$list->share_code,
                $partnerToken,
                self::ROLE_COOKIE_DAYS * 24 * 60
            );
            $role = 'partner';
        }

        $items = $list->items()->get();

        $response = response()->view('bucket-list.show', [
            'list' => $list,
            'role' => $role,
            'items' => $items,
        ]);

        return $cookieJar ? $response->withCookie($cookieJar) : $response;
    }

    public function addItem(Request $request, string $shareCode)
    {
        $list = BucketList::where('share_code', $shareCode)->firstOrFail();
        $role = $this->resolveRole($request, $list);

        if (! in_array($role, ['owner', 'partner'])) {
            return back()->withErrors(['content' => '只有清單擁有者或夥伴可以新增項目']);
        }

        $data = $request->validate([
            'content' => ['required', 'string', 'min:1', 'max:200', new NoBlockedWords],
        ], [
            'content.required' => '請輸入想做的事',
            'content.max' => '單筆內容不可超過 200 字',
        ]);

        BucketItem::create([
            'bucket_list_id' => $list->id,
            'content' => $data['content'],
            'proposer' => $role,
            // proposer 自動投 yes（你提的事預設你想做）
            $role.'_vote' => 'yes',
        ]);

        return back();
    }

    public function voteItem(Request $request, string $shareCode, int $itemId)
    {
        $list = BucketList::where('share_code', $shareCode)->firstOrFail();
        $role = $this->resolveRole($request, $list);

        if (! in_array($role, ['owner', 'partner'])) {
            return back()->withErrors(['vote' => '無投票權']);
        }

        $data = $request->validate([
            'vote' => ['required', 'in:yes,no,maybe'],
        ]);

        $item = BucketItem::where('bucket_list_id', $list->id)
            ->where('id', $itemId)
            ->firstOrFail();

        $item->update([$role.'_vote' => $data['vote']]);

        return back();
    }

    public function deleteItem(Request $request, string $shareCode, int $itemId)
    {
        $list = BucketList::where('share_code', $shareCode)->firstOrFail();
        $role = $this->resolveRole($request, $list);

        $item = BucketItem::where('bucket_list_id', $list->id)
            ->where('id', $itemId)
            ->firstOrFail();

        // Only proposer can delete their own item
        if ($item->proposer !== $role) {
            return back()->withErrors(['delete' => '只能刪除自己提的項目']);
        }

        $item->delete();

        return back();
    }

    /**
     * Determine viewer's role for this list.
     *
     * @return 'owner'|'partner'|'partner-new'|'viewer'
     *                                                  - 'owner'       cookie token matches owner_token
     *                                                  - 'partner'     cookie token matches partner_token
     *                                                  - 'partner-new' no partner_token set yet — caller must assign
     *                                                  - 'viewer'      third-party visitor (read-only)
     */
    private function resolveRole(Request $request, BucketList $list): string
    {
        $cookie = $request->cookie(self::ROLE_COOKIE_PREFIX.$list->share_code);

        if ($cookie && hash_equals($list->owner_token, $cookie)) {
            return 'owner';
        }
        if ($list->partner_token && $cookie && hash_equals($list->partner_token, $cookie)) {
            return 'partner';
        }
        if (is_null($list->partner_token)) {
            return 'partner-new';
        }

        return 'viewer';
    }
}
