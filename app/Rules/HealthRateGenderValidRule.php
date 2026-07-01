<?php

namespace App\Rules;

use App\Enums\GenderEnum;
use App\Models\HealthPlan;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;

class HealthRateGenderValidRule implements ValidationRule
{
    public function __construct(protected ?int $healthPlanId) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $healthPlan = $this->healthPlanId ? HealthPlan::find($this->healthPlanId) : null;

        if ($healthPlan && $healthPlan->gender_enabled) {
            $genders = array_map(Str::lower(...), array_column(GenderEnum::cases(), 'value'));

            if (! in_array(Str::lower($value), $genders, true)) {
                $fail('The gender is invalid.');
            }
        }
    }
}
