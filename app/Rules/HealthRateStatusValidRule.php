<?php

namespace App\Rules;

use App\Enums\HealthPlanRateSheetStatusEnum;
use App\Models\HealthRate;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;

class HealthRateStatusValidRule implements ValidationRule
{
    public function __construct(protected string $message) {}
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $rate = HealthRate::find($value);

        if (! $rate) {
            $fail('Health rate not found');

            return;
        }

        if (
            ! in_array(
                Str::lower($rate->status),
                [
                    Str::lower(HealthPlanRateSheetStatusEnum::DRAFT->value),
                    Str::lower(HealthPlanRateSheetStatusEnum::SCHEDULED->value),
                ]
            )

        ) {

            $fail($this->message);
        }
    }
}
