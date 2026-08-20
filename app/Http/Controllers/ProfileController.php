<?php

namespace App\Http\Controllers;

use App\Models\Board;
use App\Models\GamePlayer;
use App\Models\TraitResult;
use App\Models\User;
use App\Rules\NoBlockedWords;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    /**
     * 付費會員的紀錄上限。
     *
     * 說是「完整紀錄」,實際仍有上限 —— 一個玩很久的帳號可能累積上萬列,一次全撈
     * 會把整個 profile 頁拖垮。200 場遠超過任何人會往下捲的量,同時保證這個查詢
     * 的成本是有界的。真的有人撞到這個數字時,該做的是分頁而不是把上限調高。
     */
    private const PREMIUM_HISTORY_CAP = 200;

    public function index(Request $request)
    {
        $user = $request->user();
        $boards = $user->boards()->withCount('squares')->latest()->get();

        $isPremium = $user->isPremium();
        $freeLimit = max(1, (int) config('premium.free_history_limit', 5));

        // Play history: rooms this user created or joined while logged in.
        //
        // 用 has('game') 在查詢層過濾,而不是撈回來再 filter:房間被刪掉的殘列
        // 不該算進總數,否則免費會員會看到「共 30 場」但升級後只有 12 場。
        $historyQuery = GamePlayer::has('game')
            /* 連參與者一起載入。少了這行,歷史清單每一列都會為了列出同場玩家
               各發一次查詢(N+1);紀錄一多,個人頁就會明顯變慢。 */
            ->with([
                'game:id,code,game_type,status,created_at,finished_at',
                'game.players:id,game_id,player_name,user_id',
            ])
            ->where('user_id', $user->id)
            ->latest();

        $totalPlays = (clone $historyQuery)->count();

        $playHistory = $historyQuery
            ->limit($isPremium ? self::PREMIUM_HISTORY_CAP : $freeLimit)
            ->get();

        // 時間軸只給付費會員。依「當地日期」分組而不是 UTC ——
        // 深夜玩的那幾場應該落在同一天,不該被時區切成兩天。
        $timeline = $isPremium
            ? $playHistory->groupBy(fn ($p) => $p->created_at->timezone(config('app.timezone'))->format('Y-m-d'))
            : null;

        // 還有幾場被鎖住。0 的話不顯示升級提示 —— 只玩過三場的人看到
        // 「升級解鎖更多」只會覺得莫名其妙。
        $hiddenPlays = $isPremium ? 0 : max(0, $totalPlays - $playHistory->count());

        // 屬性測驗的紀錄,舊到新 —— 走勢圖是照時間往右畫的
        $traitResults = TraitResult::where('user_id', $user->id)
            ->orderBy('created_at')
            ->get();

        return view('profile.index', compact(
            'user', 'boards', 'playHistory', 'timeline',
            'isPremium', 'freeLimit', 'totalPlays', 'hiddenPlays', 'traitResults'
        ));
    }

    /** 個人化編輯頁(擁有者本人)。 */
    public function edit(Request $request)
    {
        return view('profile.edit', [
            'user' => $request->user(),
            'themes' => (array) config('profile.themes', []),
        ]);
    }

    /**
     * 儲存個人化資料 —— 單一表單一次搞定:文字、配色、公開、頭像/橫幅圖檔與焦點。
     *
     * 圖檔是選填:有選新檔就上傳(順便存這次拖曳的焦點),沒選就只更新焦點/文字。
     * 這樣「上傳時就調整、按一次儲存」是同一個動作,不用先上傳、重整、再拖、再存。
     */
    public function update(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:50', 'regex:/^[^<>]+$/'],
            'city' => ['nullable', 'string', 'max:60', new NoBlockedWords],
            'looking_for' => ['nullable', 'string', 'max:120', new NoBlockedWords],
            'bio' => ['nullable', 'string', 'max:500', new NoBlockedWords],
            'profile_theme' => ['nullable', Rule::in(array_keys((array) config('profile.themes', [])))],
            'profile_public' => ['sometimes', 'boolean'],
            'show_traits' => ['sometimes', 'boolean'],
            'show_boards' => ['sometimes', 'boolean'],
            // 頭像 / 橫幅焦點,0–100 整數百分比(拖曳決定露出哪一塊)
            'avatar_pos_x' => ['nullable', 'integer', 'between:0,100'],
            'avatar_pos_y' => ['nullable', 'integer', 'between:0,100'],
            'banner_pos_x' => ['nullable', 'integer', 'between:0,100'],
            'banner_pos_y' => ['nullable', 'integer', 'between:0,100'],
            // 選填的圖檔上傳(唯二的檔案上傳點,驗證要嚴)
            'avatar' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
            'banner' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:4096'],
            'remove_avatar' => ['sometimes', 'boolean'],
            'remove_banner' => ['sometimes', 'boolean'],
        ]);

        $user = $request->user();
        $disk = $this->uploadDisk();

        // 頭像:有新檔 → 換(先刪舊);否則勾了移除 → 刪。檔名由 store() 隨機產生。
        if ($request->hasFile('avatar')) {
            if ($user->avatar_path) {
                Storage::disk($disk)->delete($user->avatar_path);
            }
            $user->avatar_path = $request->file('avatar')->store('avatars', $disk);
        } elseif ($request->boolean('remove_avatar') && $user->avatar_path) {
            Storage::disk($disk)->delete($user->avatar_path);
            $user->avatar_path = null;
        }

        // 橫幅:同上
        if ($request->hasFile('banner')) {
            if ($user->banner_path) {
                Storage::disk($disk)->delete($user->banner_path);
            }
            $user->banner_path = $request->file('banner')->store('banners', $disk);
        } elseif ($request->boolean('remove_banner') && $user->banner_path) {
            Storage::disk($disk)->delete($user->banner_path);
            $user->banner_path = null;
        }

        $user->fill([
            'name' => $data['name'],
            'city' => $data['city'] ?? null,
            'looking_for' => $data['looking_for'] ?? null,
            'bio' => $data['bio'] ?? null,
            'profile_theme' => $data['profile_theme'] ?? $user->profile_theme,
            'profile_public' => (bool) ($data['profile_public'] ?? false),
            'show_traits' => (bool) ($data['show_traits'] ?? false),
            'show_boards' => (bool) ($data['show_boards'] ?? false),
            'avatar_pos_x' => (int) ($data['avatar_pos_x'] ?? $user->avatar_pos_x ?? 50),
            'avatar_pos_y' => (int) ($data['avatar_pos_y'] ?? $user->avatar_pos_y ?? 50),
            'banner_pos_x' => (int) ($data['banner_pos_x'] ?? $user->banner_pos_x ?? 50),
            'banner_pos_y' => (int) ($data['banner_pos_y'] ?? $user->banner_pos_y ?? 50),
        ])->save();

        return redirect()->route('profile.edit')->with('success', __('profile.saved'));
    }

    /** 頭像 / 橫幅存哪個 disk(本機 public 或 R2),見 config/profile.upload_disk。 */
    private function uploadDisk(): string
    {
        return (string) config('profile.upload_disk', 'public');
    }

    /**
     * 別人看到的公開個人頁。與擁有者自己的 /profile(含 email、棋盤、完整紀錄)
     * **不同**:這裡只顯示使用者明確公開的欄位。未公開或被停權一律 404。
     */
    public function publicShow(Request $request, User $user)
    {
        abort_unless($user->profile_public && ! $user->is_banned, 404);

        // 屬性測驗結果(使用者可關掉這個區塊)
        $topTrait = null;
        if ($user->show_traits) {
            $latest = TraitResult::where('user_id', $user->id)->latest()->first();
            if ($latest) {
                $items = (array) trans('traits.items');
                $topTrait = $items[$latest->top_trait] ?? null;
            }
        }

        // 已上架社群的棋盤(只顯示已通過審核的,可關掉這個區塊)
        $boards = $user->show_boards
            ? $user->boards()->where('publish_status', Board::PUBLISH_APPROVED)
                ->withCount('squares')->latest()->limit(12)->get()
            : collect();

        $isOwner = $request->user()?->id === $user->id;

        return view('profile.public', compact('user', 'topTrait', 'boards', 'isOwner'));
    }

    /**
     * 尋找:列出站上「公開」的個人頁(聯誼探索頁)。
     * 只顯示 profile_public 且未被停權的使用者;可用城市關鍵字篩選。
     */
    public function discover(Request $request)
    {
        $city = trim((string) $request->query('city', ''));

        $users = User::query()
            ->where('profile_public', true)
            ->where('is_banned', false)
            ->when($city !== '', fn ($q) => $q->where('city', 'like', '%'.$city.'%'))
            ->latest()
            ->paginate(24)
            ->withQueryString();

        return view('profile.discover', [
            'users' => $users,
            'city' => $city,
        ]);
    }
}
