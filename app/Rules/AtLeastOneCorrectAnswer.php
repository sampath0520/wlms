<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class AtLeastOneCorrectAnswer implements Rule
{
    public function passes($attribute, $value)
    {
        $correctAnswers = collect($value)->where('is_correct', 1);
        return $correctAnswers->isNotEmpty();
    }

    public function message()
    {
        return 'At least one answer must be marked as correct.';
    }
}
