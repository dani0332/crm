<?php

namespace App\Rules;

use App\Enums\HealthPlanRateSheetStatusEnum;
use App\Models\HealthPlan;
use Carbon\Carbon;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class HealthPlanRateControlPublishableRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $healthPlan = HealthPlan::with(['draftRateControl', 'activeRateControl'])
            ->find($value);

        if (! $healthPlan) {
            return;
        }

        $draftRateControl = $healthPlan->draftRateControl;

        if (! $draftRateControl) {
            $fail('Health plan does not have a rate control in draft status.');

            return;
        }

        $hasDraftRates = $draftRateControl->rates()
            ->where('status', HealthPlanRateSheetStatusEnum::DRAFT->value)
            ->exists();

        if (! $hasDraftRates) {
            $fail('Rate control does not have any rates in draft status.');

            return;
        }

        $effectiveFrom = Carbon::parse($draftRateControl->effective_from)->startOfDay();
        $today = Carbon::today();

        if (! $effectiveFrom->isAfter($today)) {
            $fail('Rate control effective from date must be greater than today.');

            return;
        }

        $activeRateControl = $healthPlan->activeRateControl;

        if ($activeRateControl && $activeRateControl->effective_from) {
            $activeEffectiveFrom = Carbon::parse($activeRateControl->effective_from)->startOfDay();

            if (! $effectiveFrom->isAfter($activeEffectiveFrom)) {
                $fail('Rate control effective from date must be greater than the active rate control effective from date.');
            }
        }
    }
}
