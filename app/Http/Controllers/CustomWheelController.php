<?php

namespace App\Http\Controllers;

use App\Models\CustomWheel;
use App\Rules\NoBlockedWords;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * 自訂轉盤的儲存 / 讀取 / 刪除。
 *
 * 全部以 JSON 回應,由轉盤編輯器用 fetch 呼叫 —— 頁面本身仍是 SSR,
 * 這裡只負責資料,不渲染任何畫面(符合 CLAUDE.md 的 SSR 原則)。
 *
 * 內容審核:轉盤只有本人看得到,但仍會存進資料庫,所以名稱與每個選項
 * 都套 NoBlockedWords,與專案其他使用者輸入欄位一致。
 */
class CustomWheelController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $wheels = CustomWheel::where('user_id', $request->user()->id)
            ->orderByDesc('updated_at')
            ->get(['id', 'name', 'items', 'updated_at']);

        return response()->json(['wheels' => $wheels]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:40', new NoBlockedWords],
            'items' => ['required', 'array', 'min:2', 'max:'.CustomWheel::MAX_ITEMS],
            'items.*.t' => ['required', 'string', 'max:24', new NoBlockedWords],
            'items.*.p' => ['required', 'integer', 'min:1', 'max:100'],
            // 有帶 id 就是覆蓋既有的那一組
            'id' => ['nullable', 'integer', Rule::exists('custom_wheels', 'id')
                ->where('user_id', $request->user()->id)],
        ]);

        $userId = $request->user()->id;

        if (! empty($data['id'])) {
            $wheel = CustomWheel::where('user_id', $userId)->findOrFail($data['id']);
        } else {
            if (CustomWheel::where('user_id', $userId)->count() >= CustomWheel::MAX_PER_USER) {
                return response()->json([
                    'message' => __('minigame.cw_save_limit', ['n' => CustomWheel::MAX_PER_USER]),
                ], 422);
            }
            $wheel = new CustomWheel(['user_id' => $userId]);
        }

        $wheel->name = $data['name'];
        // 只留下需要的欄位,避免把前端多送的東西存進去
        $wheel->items = array_map(
            fn ($i) => ['t' => $i['t'], 'p' => (int) $i['p']],
            $data['items']
        );
        $wheel->save();

        return response()->json([
            'wheel' => $wheel->only(['id', 'name', 'items', 'updated_at']),
            'message' => __('minigame.cw_saved'),
        ]);
    }

    public function destroy(Request $request, CustomWheel $customWheel): JsonResponse
    {
        abort_unless($customWheel->user_id === $request->user()->id, 403);

        $customWheel->delete();

        return response()->json(['ok' => true]);
    }
}
