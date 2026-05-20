<?php

namespace App\Rules;

use App\Enums\HealthPlanRateSheetStatusEnum;
use App\Models\HealthPlan;
use App\Models\HealthRateControl;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class HealthPlanUpdateRateExistsRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $plan = HealthPlan::findOrFail($value);

        // Skip if its not draft
        if ($plan->status != HealthPlanRateSheetStatusEnum::DRAFT->value) {
            return;
        }

        // Check if rate sheet exists under plan
        $rateSheet = HealthRateControl::where('health_plan_id', $value)->count();

        if ($rateSheet > 0) {
            $fail('Plan cannot be updated if it has rate sheet. Please delete rate sheet first.');
        }
    }
}
