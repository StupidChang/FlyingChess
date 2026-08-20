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
            'title.required' => __('minigame.bucket_title_required'),
            'title.max' => __('minigame.bucket_title_max'),
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

        // partner 身分不再由開頁(GET)自動指派 —— 那會被連結預覽 bot 或任何拿到
        // 連結的路人搶先佔走,而且不可逆。改成頁面顯示一個「我是另一半,加入」的
        // 按鈕,由使用者明確 POST /claim 才發放(見 claim())。
        $canClaim = $role === 'viewer' && is_null($list->partner_token);

        $items = $list->items()->get();

        return response()->view('bucket-list.show', [
            'list' => $list,
            'role' => $role,
            'items' => $items,
            'canClaim' => $canClaim,
        ]);
    }

    /**
     * 明確認領 partner 身分。只有在還沒有人是 partner 時才成立,先到先得,
     * 但一定要是使用者主動送出的 POST(CSRF 保護 + 限速),bot 不會觸發。
     */
    public function claim(Request $request, string $shareCode)
    {
        $list = BucketList::where('share_code', $shareCode)->firstOrFail();
        $role = $this->resolveRole($request, $list);

        if (in_array($role, ['owner', 'partner'], true)) {
            return back();   // 已經是成員,不需認領
        }

        if (! is_null($list->partner_token)) {
            return back()->withErrors(['claim' => __('minigame.bucket_partner_taken')]);
        }

        $partnerToken = Str::random(48);
        $list->update(['partner_token' => $partnerToken]);

        return back()->withCookie(cookie(
            self::ROLE_COOKIE_PREFIX.$list->share_code,
            $partnerToken,
            self::ROLE_COOKIE_DAYS * 24 * 60
        ));
    }

    public function addItem(Request $request, string $shareCode)
    {
        $list = BucketList::where('share_code', $shareCode)->firstOrFail();
        $role = $this->resolveRole($request, $list);

        if (! in_array($role, ['owner', 'partner'])) {
            return back()->withErrors(['content' => __('minigame.bucket_only_members_add')]);
        }

        $data = $request->validate([
            'content' => ['required', 'string', 'min:1', 'max:200', new NoBlockedWords],
        ], [
            'content.required' => __('minigame.bucket_content_required'),
            'content.max' => __('minigame.bucket_content_max'),
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
            return back()->withErrors(['vote' => __('minigame.bucket_no_vote_permission')]);
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
            return back()->withErrors(['delete' => __('minigame.bucket_delete_own_only')]);
        }

        $item->delete();

        return back();
    }

    /**
     * Determine viewer's role for this list.
     *
     * @return 'owner'|'partner'|'viewer'
     *                                    - 'owner'   cookie token matches owner_token
     *                                    - 'partner' cookie token matches partner_token
     *                                    - 'viewer'  everyone else (read-only). A viewer who
     *                                    arrives before any partner is set can become the
     *                                    partner via the explicit POST /claim, never here.
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

        return 'viewer';
    }
}
