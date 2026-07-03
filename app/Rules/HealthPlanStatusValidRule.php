<?php

namespace App\Rules;

use App\Models\HealthPlan;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;

class HealthPlanStatusValidRule implements ValidationRule
{
    public function __construct(protected string $message, protected array $statuses) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $healthPlan = HealthPlan::find($value);

        if (! $healthPlan) {
            $fail('Health plan not found');

            return;
        }

        if (! in_array(
            Str::lower($healthPlan->status),
            $this->statuses,
        )) {
            $fail($this->message);
        }
    }
}
