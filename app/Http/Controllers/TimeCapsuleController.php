<?php

namespace App\Http\Controllers;

use App\Models\CapsuleAnswer;
use App\Models\CapsuleQuestion;
use App\Models\TimeCapsule;
use App\Rules\NoBlockedWords;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class TimeCapsuleController extends Controller
{
    private const ROLE_COOKIE_PREFIX = 'capsule_role_';

    private const ROLE_COOKIE_DAYS = 365;

    /**
     * Number of default questions seeded into new capsules. Texts live in
     * lang/<locale>/minigame.php (capsule_default_q1..q10) so they follow the
     * creator's locale, covering past memories, present feelings, and
     * future intentions.
     */
    private const DEFAULT_QUESTION_COUNT = 10;

    public function lobby()
    {
        return view('time-capsule.lobby');
    }

    public function create(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'min:1', 'max:100', new NoBlockedWords],
            'open_at' => ['required', 'date', 'after:today'],
            'notify_email' => ['nullable', 'email', 'max:100'],
        ], [
            'title.required' => __('minigame.capsule_title_required'),
            'open_at.required' => __('minigame.capsule_open_at_required'),
            'open_at.after' => __('minigame.capsule_open_at_after'),
            'notify_email.email' => __('minigame.capsule_email_invalid'),
        ]);

        $ownerToken = Str::random(48);

        $capsule = DB::transaction(function () use ($data, $ownerToken) {
            $capsule = TimeCapsule::create([
                'title' => $data['title'],
                'open_at' => $data['open_at'],
                'notify_email' => $data['notify_email'] ?? null,
                'owner_token' => $ownerToken,
            ]);

            // Seed default questions in the creator's locale
            for ($i = 1; $i <= self::DEFAULT_QUESTION_COUNT; $i++) {
                CapsuleQuestion::create([
                    'capsule_id' => $capsule->id,
                    'question' => __('minigame.capsule_default_q'.$i),
                    'position' => $i - 1,
                ]);
            }

            return $capsule;
        });

        return redirect()
            ->route('time-capsule.show', ['shareCode' => $capsule->share_code])
            ->withCookie(cookie(
                self::ROLE_COOKIE_PREFIX.$capsule->share_code,
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
                self::ROLE_COOKIE_PREFIX.$capsule->share_code,
                $partnerToken,
                self::ROLE_COOKIE_DAYS * 24 * 60
            );
            $role = 'partner';
        }

        // First successful open after open_at
        if ($capsule->isSealed() && Carbon::today()->greaterThanOrEqualTo($capsule->open_at) && ! $capsule->opened_at) {
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
            'capsule' => $capsule,
            'role' => $role,
            'questions' => $questions,
            'answerMap' => $answerMap,
        ]);

        return $cookieJar ? $response->withCookie($cookieJar) : $response;
    }

    public function saveAnswers(Request $request, string $shareCode)
    {
        $capsule = TimeCapsule::where('share_code', $shareCode)->firstOrFail();
        $role = $this->resolveRole($request, $capsule);

        if (! in_array($role, ['owner', 'partner'])) {
            return back()->withErrors(['answers' => __('minigame.capsule_no_edit_permission')]);
        }

        if ($capsule->isSealed()) {
            return back()->withErrors(['answers' => __('minigame.capsule_sealed_no_edit')]);
        }

        $answers = $request->input('answers', []);

        $validator = Validator::make(
            ['answers' => $answers],
            ['answers.*' => ['nullable', 'string', new NoBlockedWords]]
        );
        if ($validator->fails()) {
            return back()->withErrors(['answers' => $validator->errors()->first()]);
        }

        $questionIds = $capsule->questions()->pluck('id')->all();

        DB::transaction(function () use ($answers, $questionIds, $role) {
            foreach ($answers as $qid => $text) {
                $qid = (int) $qid;
                if (! in_array($qid, $questionIds, true)) {
                    continue;
                }
                $text = trim((string) $text);
                if ($text === '') {
                    continue;
                }
                if (mb_strlen($text) > 1000) {
                    $text = mb_substr($text, 0, 1000);
                }
                CapsuleAnswer::updateOrCreate(
                    ['question_id' => $qid, 'role' => $role],
                    ['answer' => $text]
                );
            }
        });

        return back()->with('success', __('minigame.capsule_answers_saved'));
    }

    public function seal(Request $request, string $shareCode)
    {
        $capsule = TimeCapsule::where('share_code', $shareCode)->firstOrFail();
        $role = $this->resolveRole($request, $capsule);

        // Only owner can seal
        if ($role !== 'owner') {
            return back()->withErrors(['seal' => __('minigame.capsule_owner_only_seal')]);
        }

        if ($capsule->isSealed()) {
            return back()->withErrors(['seal' => __('minigame.capsule_already_sealed')]);
        }

        // Sanity check — at least one answer exists
        $hasAnyAnswer = CapsuleAnswer::whereIn(
            'question_id',
            $capsule->questions()->pluck('id')
        )->exists();

        if (! $hasAnyAnswer) {
            return back()->withErrors(['seal' => __('minigame.capsule_need_one_answer')]);
        }

        $capsule->update(['sealed_at' => now()]);

        return back()->with('success', __('minigame.capsule_sealed_success', ['date' => $capsule->open_at->format('Y-m-d')]));
    }

    /**
     * @return 'owner'|'partner'|'partner-new'|'viewer'
     */
    private function resolveRole(Request $request, TimeCapsule $capsule): string
    {
        $cookie = $request->cookie(self::ROLE_COOKIE_PREFIX.$capsule->share_code);

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
