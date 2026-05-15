<?php

namespace App\Rules;

use App\Enums\HealthPlanRateSheetStatusEnum;
use App\Models\HealthRateControl;
use App\Rules\Concerns\ValidatesRateControlPublishable;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class HealthRateControlPublishableRule implements ValidationRule
{
    use ValidatesRateControlPublishable;

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $rateControl = HealthRateControl::with('healthPlan.activeRateControl')->find($value);

        if (! $rateControl) {
            $fail('Rate control not found.');

            return;
        }

        if (strtolower($rateControl->status) !== strtolower(HealthPlanRateSheetStatusEnum::DRAFT->value)) {
            $fail('Rate control must be in draft status to be published.');

            return;
        }

        $this->validateRateControl($rateControl, $fail);
    }
}
