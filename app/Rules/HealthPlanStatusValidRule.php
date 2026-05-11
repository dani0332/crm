<?php

namespace App\Rules;

use App\Enums\HealthPlanRateSheetStatusEnum;
use App\Models\HealthPlan;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class HealthPlanStatusValidRule implements ValidationRule
{
    public function __construct(protected string $message) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $healthPlan = HealthPlan::find($value);

        if (! $healthPlan) {
            $fail('Health plan not found');

            return;
        }

        if (! in_array(
            strtolower($healthPlan->status),
            [
                strtolower(HealthPlanRateSheetStatusEnum::DRAFT->value),
                strtolower(HealthPlanRateSheetStatusEnum::SCHEDULED->value),
            ]
        )) {
            $fail($this->message);
        }
    }
}
