<?php

namespace App\Rules\Concerns;

use App\Enums\HealthPlanRateSheetStatusEnum;
use App\Models\HealthRateControl;
use Carbon\Carbon;
use Closure;

trait ValidatesRateControlPublishable
{
    protected function validateRateControl(HealthRateControl $rateControl, Closure $fail): void
    {
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
        } else {
            $activeRateControl = $rateControl->healthPlan?->activeRateControl;

            if ($activeRateControl && $activeRateControl->effective_from) {
                $activeEffectiveFrom = Carbon::parse($activeRateControl->effective_from)->startOfDay();

                if (! $effectiveFrom->isAfter($activeEffectiveFrom)) {
                    $fail('Rate control effective from date must be greater than the active rate control effective from date.');
                }
            }
        }
    }
}
