<?php

declare(strict_types=1);

namespace App\Traits;

trait ResolvesCommission
{
    /**
     * Mirrors the Vue BookingDetails calculateCommission formula:
     * total_commission = commission_vat_not_applicable + commission_vat_applicable + commission_vat
     * Falls back to the stored commission column if breakdown fields are absent.
     */
    protected function resolveTotalCommission(?object $payment): ?float
    {
        if ($payment === null) {
            return null;
        }

        $vatApplicable = $payment->commission_vat_applicable;
        $vatNotApplicable = $payment->commission_vat_not_applicable;
        $vatOnCommission = $payment->commission_vat;

        if ($vatApplicable !== null || $vatNotApplicable !== null || $vatOnCommission !== null) {
            return (float) ($vatApplicable ?? 0) + (float) ($vatNotApplicable ?? 0) + (float) ($vatOnCommission ?? 0);
        }

        return $payment->commission !== null ? (float) $payment->commission : null;
    }
}
