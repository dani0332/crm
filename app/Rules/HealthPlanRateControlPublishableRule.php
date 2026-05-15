<?php

namespace App\Rules;

use App\Models\HealthPlan;
use App\Rules\Concerns\ValidatesRateControlPublishable;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class HealthPlanRateControlPublishableRule implements ValidationRule
{
    use ValidatesRateControlPublishable;

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $healthPlan = HealthPlan::with(['draftRateControl'])
            ->find($value);

        if (! $healthPlan) {
            return;
        }

        $draftRateControl = $healthPlan->draftRateControl;

        if (! $draftRateControl) {
            $fail('Health plan does not have a rate control in draft status.');

            return;
        }

        $this->validateRateControl($draftRateControl, $fail);
    }
}
