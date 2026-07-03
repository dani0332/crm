<?php

namespace App\Rules;

use App\Enums\HealthPlanRateSheetStatusEnum;
use App\Models\HealthRateControl;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class HealthPlanRateScheduledRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $scheduledRateControl = HealthRateControl::where('health_plan_id', $value)
            ->where('status', HealthPlanRateSheetStatusEnum::SCHEDULED->value)
            ->first();

        if ($scheduledRateControl) {
            $fail('New rate cannot be added to a scheduled rate sheet');
        }
    }
}
