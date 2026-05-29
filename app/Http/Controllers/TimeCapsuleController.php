<?php

namespace App\Http\Controllers;

use App\Models\CapsuleAnswer;
use App\Models\CapsuleQuestion;
use App\Models\TimeCapsule;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TimeCapsuleController extends Controller
{
    private const ROLE_COOKIE_PREFIX = 'capsule_role_';
    private const ROLE_COOKIE_DAYS = 365;

    /**
     * Default question set for new capsules. 10 prompts covering
     * past memories, present feelings, and future intentions.
     */
    private const DEFAULT_QUESTIONS = [
        '今天我們最想留給未來自己的一句話是？',
        '對方最讓我感動的一個瞬間是？',
        '我希望一年後我們還在一起做什麼事？',
        '我目前正在努力的目標是？',
        '我希望明年此時的我們，比現在多一點什麼？',
        '對方有哪個小習慣是我最喜歡的？',
        '如果可以給未來的自己一個提醒，那會是什麼？',
        '我們最近一次大笑是什麼時候、為什麼？',
        '一年後我希望我們一起去哪裡？',
        '此時此刻我最感謝對方的一件事是？',
    ];

    public function lobby()
    {
        return view('time-capsule.lobby');
    }

    public function create(Request $request)
    {
        $data = $request->validate([
            'title'        => ['required', 'string', 'min:1', 'max:100'],
            'open_at'      => ['required', 'date', 'after:today'],
            'notify_email' => ['nullable', 'email', 'max:100'],
        ], [
            'title.required'       => '請輸入膠囊標題',
            'open_at.required'     => '請選擇開封日期',
            'open_at.after'        => '開封日期必須是明天以後',
            'notify_email.email'   => 'Email 格式錯誤',
        ]);

        $ownerToken = Str::random(48);

        $capsule = DB::transaction(function () use ($data, $ownerToken) {
            $capsule = TimeCapsule::create([
                'title'        => $data['title'],
                'open_at'      => $data['open_at'],
                'notify_email' => $data['notify_email'] ?? null,
                'owner_token'  => $ownerToken,
            ]);

            // Seed default questions
            foreach (self::DEFAULT_QUESTIONS as $i => $q) {
                CapsuleQuestion::create([
                    'capsule_id' => $capsule->id,
                    'question'   => $q,
                    'position'   => $i,
                ]);
            }

            return $capsule;
        });

        return redirect()
            ->route('time-capsule.show', ['shareCode' => $capsule->share_code])
            ->withCookie(cookie(
                self::ROLE_COOKIE_PREFIX . $capsule->share_code,
                $ownerToken,
                self::ROLE_COOKIE_DAYS * 24 * 60
            ));
    }

    public function show(Request $request, string $shareCode)
    {
        $capsule = TimeCapsule::where('share_code', $shareCode)->firstOrFail();
        $role = $this->resolveRole($request, $capsule);

        $cookieJar = null;
        if ($role === 'partner-new') {
            $partnerToken = Str::random(48);
            $capsule->update(['partner_token' => $partnerToken]);
            $cookieJar = cookie(
                self::ROLE_COOKIE_PREFIX . $capsule->share_code,
                $partnerToken,
                self::ROLE_COOKIE_DAYS * 24 * 60
            );
            $role = 'partner';
        }

        // First successful open after open_at
        if ($capsule->isSealed() && Carbon::today()->greaterThanOrEqualTo($capsule->open_at) && !$capsule->opened_at) {
            $capsule->update(['opened_at' => now()]);
        }

        $questions = $capsule->questions()->with('answers')->get();

        // Build answer map: questionId => ['owner' => answer, 'partner' => answer]
        $answerMap = [];
        foreach ($questions as $q) {
            $answerMap[$q->id] = ['owner' => null, 'partner' => null];
            foreach ($q->answers as $a) {
                $answerMap[$q->id][$a->role] = $a->answer;
            }
        }

        $response = response()->view('time-capsule.show', [
            'capsule'   => $capsule,
            'role'      => $role,
            'questions' => $questions,
            'answerMap' => $answerMap,
        ]);

        return $cookieJar ? $response->withCookie($cookieJar) : $response;
    }

    public function saveAnswers(Request $request, string $shareCode)
    {
        $capsule = TimeCapsule::where('share_code', $shareCode)->firstOrFail();
        $role = $this->resolveRole($request, $capsule);

        if (!in_array($role, ['owner', 'partner'])) {
            return back()->withErrors(['answers' => '無編輯權']);
        }

        if ($capsule->isSealed()) {
            return back()->withErrors(['answers' => '膠囊已封存，無法修改']);
        }

        $answers = $request->input('answers', []);
        $questionIds = $capsule->questions()->pluck('id')->all();

        DB::transaction(function () use ($answers, $questionIds, $role) {
            foreach ($answers as $qid => $text) {
                $qid = (int) $qid;
                if (!in_array($qid, $questionIds, true)) continue;
                $text = trim((string) $text);
                if ($text === '') continue;
                if (mb_strlen($text) > 1000) {
                    $text = mb_substr($text, 0, 1000);
                }
                CapsuleAnswer::updateOrCreate(
                    ['question_id' => $qid, 'role' => $role],
                    ['answer' => $text]
                );
            }
        });

        return back()->with('success', '已儲存你的回答');
    }

    public function seal(Request $request, string $shareCode)
    {
        $capsule = TimeCapsule::where('share_code', $shareCode)->firstOrFail();
        $role = $this->resolveRole($request, $capsule);

        // Only owner can seal
        if ($role !== 'owner') {
            return back()->withErrors(['seal' => '只有創建者可以封存膠囊']);
        }

        if ($capsule->isSealed()) {
            return back()->withErrors(['seal' => '膠囊已封存']);
        }

        // Sanity check — at least one answer exists
        $hasAnyAnswer = CapsuleAnswer::whereIn(
            'question_id',
            $capsule->questions()->pluck('id')
        )->exists();

        if (!$hasAnyAnswer) {
            return back()->withErrors(['seal' => '至少要回答一題才能封存']);
        }

        $capsule->update(['sealed_at' => now()]);

        return back()->with('success', '膠囊已封存！將在 ' . $capsule->open_at->format('Y-m-d') . ' 開封');
    }

    /**
     * @return 'owner'|'partner'|'partner-new'|'viewer'
     */
    private function resolveRole(Request $request, TimeCapsule $capsule): string
    {
        $cookie = $request->cookie(self::ROLE_COOKIE_PREFIX . $capsule->share_code);

        if ($cookie && hash_equals($capsule->owner_token, $cookie)) {
            return 'owner';
        }
        if ($capsule->partner_token && $cookie && hash_equals($capsule->partner_token, $cookie)) {
            return 'partner';
        }
        if (is_null($capsule->partner_token)) {
            return 'partner-new';
        }
        return 'viewer';
    }
}
