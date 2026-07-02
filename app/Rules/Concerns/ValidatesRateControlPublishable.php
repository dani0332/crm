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
            $fail('Rate sheet does not have any draft rates.');

            return;
        }

        $effectiveFrom = Carbon::parse($rateControl->effective_from)->startOfDay();
        $today = Carbon::today();

        if (! $effectiveFrom->isAfter($today)) {
            $fail('Effective from date must be greater than today.');
        } else {
            $activeRateControl = $rateControl->healthPlan?->activeRateControl;

            if ($activeRateControl && $activeRateControl->effective_from) {
                $activeEffectiveFrom = Carbon::parse($activeRateControl->effective_from)->startOfDay();

                if (! $effectiveFrom->isAfter($activeEffectiveFrom)) {
                    $fail('Effective from date must be greater than the existing active rate sheet effective from date.');
                }
            }
        }
    }
}
