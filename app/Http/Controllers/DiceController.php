<?php

namespace App\Http\Controllers;

use App\Models\Dice;
use App\Rules\NoBlockedWords;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class DiceController extends Controller
{
    private const MAX_DICE_PER_USER = 20;

    /** Manage the logged-in user's custom dice. */
    public function index(Request $request)
    {
        $dice = $request->user()->dice()->orderBy('category')->orderBy('name')->get();

        return view('dice.index', compact('dice'));
    }

    public function store(Request $request)
    {
        $user = $request->user();

        if ($user->dice()->count() >= self::MAX_DICE_PER_USER) {
            return back()->with('error', __('minigame.dice_custom_limit', ['n' => self::MAX_DICE_PER_USER]));
        }

        $data = $this->validated($request);

        $user->dice()->create($data);

        return back()->with('success', __('minigame.dice_custom_saved'));
    }

    public function update(Request $request, Dice $dice)
    {
        abort_unless($dice->user_id === $request->user()->id, 403);

        $dice->update($this->validated($request));

        return back()->with('success', __('minigame.dice_custom_saved'));
    }

    public function destroy(Request $request, Dice $dice)
    {
        abort_unless($dice->user_id === $request->user()->id, 403);

        $dice->delete();

        return back()->with('success', __('minigame.dice_custom_deleted'));
    }

    /**
     * Validate + normalise the submitted die. Faces arrive as up to 6 text
     * inputs; blanks are dropped, each surviving face is moderated.
     */
    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'category' => ['required', Rule::in(Dice::CATEGORIES)],
            'name' => ['required', 'string', 'max:40', new NoBlockedWords],
            'faces' => ['required', 'array', 'min:2', 'max:6'],
            'faces.*' => ['nullable', 'string', 'max:30', new NoBlockedWords],
        ]);

        $faces = array_values(array_filter(array_map('trim', $validated['faces']), fn ($f) => $f !== ''));

        // Re-check the non-empty count after trimming blanks.
        if (count($faces) < 2) {
            throw ValidationException::withMessages([
                'faces' => __('minigame.dice_custom_min_faces'),
            ]);
        }

        return [
            'category' => $validated['category'],
            'name' => $validated['name'],
            'faces' => $faces,
        ];
    }
}
