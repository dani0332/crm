<?php

namespace App\Services;

use App\Enums\InsuranceProviderEnum;
use App\Enums\QuoteStatusEnum;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class AllianceHistoricalProviderService
{
    private const ALLIANCE_BRAND_SWITCH_DATE = '2026-05-25';

    /**
     * Leads booked before the Alliance→Qatar brand switch should continue displaying
     * Alliance as the provider instead of Qatar Insurance.
     *
     * Returns the effective insurance provider (Alliance if override applies, original otherwise).
     */
    public function applyHistoricalProviderOverride(mixed $record, Collection $payments, mixed $paymentEntityModel, mixed $insuranceProvider): mixed
    {
        if (
            $record->quote_status_id != QuoteStatusEnum::PolicyBooked
            || $insuranceProvider?->code !== InsuranceProviderEnum::QIC->value
        ) {
            return $insuranceProvider;
        }

        $bookingDate = $record->quote_status_date;

        $alliancePaymentProvider = $payments->first(
            fn ($payment) => $payment->insuranceProvider?->code === InsuranceProviderEnum::ALNC->value
        )?->insuranceProvider;

        if (
            $bookingDate
            && Carbon::parse($bookingDate)->lt(Carbon::parse(self::ALLIANCE_BRAND_SWITCH_DATE))
            && $alliancePaymentProvider
        ) {
            $record->travel_plan_provider_text = $alliancePaymentProvider->text;
            $paymentEntityModel->plan?->setRelation('insuranceProvider', $alliancePaymentProvider);
            $payments->each(function ($payment) use ($alliancePaymentProvider) {
                $payment->travelPlan?->setRelation('insuranceProvider', $alliancePaymentProvider);
            });

            return $alliancePaymentProvider;
        }

        return $insuranceProvider;
    }
}
