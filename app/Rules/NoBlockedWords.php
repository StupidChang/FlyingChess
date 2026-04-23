<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class NoBlockedWords implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value)) {
            return;
        }

        $blocked = config('moderation.blocked_words', []);
        $lower = mb_strtolower($value);

        foreach ($blocked as $word) {
            if (str_contains($lower, mb_strtolower($word))) {
                $fail('此內容包含不允許的用語，請修改後重試');
                return;
            }
        }
    }
}
