<?php

namespace App\Rules;

use App\Enums\HealthPlanRateSheetStatusEnum;
use App\Models\HealthRateControl;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class HealthRateSheetStatusValidRule implements ValidationRule
{
    public function __construct(protected string $message) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $rateSheet = HealthRateControl::find($value);

        if (! $rateSheet) {
            $fail('Rate sheet not found');

            return;
        }

        if (! in_array($rateSheet->status, [HealthPlanRateSheetStatusEnum::DRAFT, HealthPlanRateSheetStatusEnum::SCHEDULED])) {
            $fail($this->message);
        }
    }
}
