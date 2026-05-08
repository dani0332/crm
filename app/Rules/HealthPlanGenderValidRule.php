<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class HealthPlanGenderValidRule implements ValidationRule
{
    public function __construct(protected ?bool $maritalStatusEnabled) {}
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $this->maritalStatusEnabled) {
            return;
        }

        if ($this->maritalStatusEnabled && (int) $value !== 1) {
            $fail('The gender is required when marital status is enabled.');
        }
    }
}
