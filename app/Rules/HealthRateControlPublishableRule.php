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
            $fail('Rate sheet not found.');

            return;
        }

        if ($rateControl->status !== HealthPlanRateSheetStatusEnum::DRAFT) {
            $fail('Only draft rate sheets can be published.');

            return;
        }

        $this->validateRateControl($rateControl, $fail);
    }
}
