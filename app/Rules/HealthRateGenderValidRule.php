<?php

namespace App\Rules;

use App\Enums\GenderEnum;
use App\Models\HealthPlan;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class HealthRateGenderValidRule implements ValidationRule
{
    public function __construct(protected int $healthPlanId) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $healthPlan = HealthPlan::find($this->healthPlanId);

        if ($healthPlan && $healthPlan->gender_enabled) {
            $genders = array_map('strtolower', array_column(GenderEnum::cases(), 'value'));

            if (! in_array(strtolower($value), $genders, true)) {
                $fail('The gender is invalid.');
            }
        }
    }
}
