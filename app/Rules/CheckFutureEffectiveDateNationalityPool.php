<?php

namespace App\Rules;

use App\Models\NationalityPool;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class CheckFutureEffectiveDateNationalityPool implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $nationalityPoolScheduled = NationalityPool::where('effective_from', '>', now())
            ->where('effective_from', '!=', $value)
            ->exists();

        if ($nationalityPoolScheduled) {
            $fail('The effective date is already scheduled for a future date.');
        }
    }
}
