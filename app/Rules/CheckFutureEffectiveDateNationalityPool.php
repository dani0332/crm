<?php

namespace App\Rules;

use App\Models\NationalityPool;
use Carbon\Carbon;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class CheckFutureEffectiveDateNationalityPool implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $value = Carbon::parse($value)->toDateString();
        $today = Carbon::today()->toDateString();

        if ($value <= $today) {
            return;
        }

        $nationalityPoolScheduled = NationalityPool::whereDate('effective_from', '>', $today)
            ->whereDate('effective_from', '!=', $value)
            ->exists();

        if ($nationalityPoolScheduled) {
            $fail('The effective date is already scheduled for a future date.');
        }
    }
}
