<?php

namespace App\Rules;

use App\Enums\HealthPlanRateSheetStatusEnum;
use App\Models\HealthRateControl;
use Carbon\Carbon;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class HealthRateControlPublishableRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $rateControl = HealthRateControl::with('healthPlan.activeRateControl')->find($value);

        if (! $rateControl) {
            $fail('Rate control not found.');

            return;
        }

        if (strtolower($rateControl->status) !== strtolower(HealthPlanRateSheetStatusEnum::DRAFT->value)) {
            $fail('Rate control must be in draft status to be published.');

            return;
        }

        $hasDraftRates = $rateControl->rates()
            ->where('status', HealthPlanRateSheetStatusEnum::DRAFT->value)
            ->exists();

        if (! $hasDraftRates) {
            $fail('Rate control does not have any rates in draft status.');

            return;
        }

        $effectiveFrom = Carbon::parse($rateControl->effective_from)->startOfDay();
        $today = Carbon::today();

        if (! $effectiveFrom->isAfter($today)) {
            $fail('Rate control effective from date must be greater than today.');

            return;
        }

        $activeRateControl = $rateControl->healthPlan?->activeRateControl;

        if ($activeRateControl && $activeRateControl->effective_from) {
            $activeEffectiveFrom = Carbon::parse($activeRateControl->effective_from)->startOfDay();

            if (! $effectiveFrom->isAfter($activeEffectiveFrom)) {
                $fail('Rate control effective from date must be greater than the active rate control effective from date.');
            }
        }
    }
}
