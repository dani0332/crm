<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class HealthPlanGenderValidRule implements ValidationRule
{
    public function __construct(protected ?bool $genderEnabled) {}
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value) {
            return;
        }

        if ($value && (int) $this->genderEnabled !== 1) {
            $fail('The gender enabled is required when marital status is enabled.');
        }
    }
}
