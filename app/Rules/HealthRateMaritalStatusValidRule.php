<?php

namespace App\Rules;

use App\Enums\GenderEnum;
use App\Enums\MaritalStatusEnum;
use App\Models\HealthPlan;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;

class HealthRateMaritalStatusValidRule implements ValidationRule
{
    public function __construct(protected ?int $healthPlanId = null, protected ?string $gender = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (empty($this->gender)) {
            return;
        }

        $healthPlan = $this->healthPlanId ? HealthPlan::find($this->healthPlanId) : null;

        // Only required for female if plan level is enabled
        if ($healthPlan && $healthPlan->marital_status_enabled && Str::lower($this->gender) == Str::lower(GenderEnum::FEMALE->value)) {
            $maritalStatuses = array_column(MaritalStatusEnum::cases(), 'value');

            if (! in_array(Str::lower($value), $maritalStatuses, true)) {
                $fail('The marital status is invalid.');
            }
        }
    }
}
