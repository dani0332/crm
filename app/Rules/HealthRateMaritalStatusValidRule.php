<?php

namespace App\Rules;

use App\Enums\GenderEnum;
use App\Enums\MaritalStatusEnum;
use App\Models\HealthPlan;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class HealthRateMaritalStatusValidRule implements ValidationRule
{
    public function __construct(protected int $healthPlanId, protected string $gender) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $healthPlan = HealthPlan::find($this->healthPlanId);

        // Only required for female if plan level is enabled
        if ($healthPlan && $healthPlan->marital_status_enabled && strtolower($this->gender) == strtolower(GenderEnum::FEMALE->value)) {
            if (empty($value)) {
                $fail('The marital status is required.');
            }

            $maritalStatuses = array_column(MaritalStatusEnum::cases(), 'value');

            if (! in_array(strtolower($value), $maritalStatuses, true)) {
                $fail('The marital status is invalid.');
            }
        }
    }
}
