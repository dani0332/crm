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

        if ($healthPlan->status !== HealthPlanRateSheetStatusEnum::DRAFT->value) {
            $fail($this->message);
        }
    }
}
