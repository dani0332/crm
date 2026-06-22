<?php

namespace App\Services\HealthRevamp;

use Carbon\Carbon;

final class HealthQuoteRevampMigrationContext
{
    /**
     * Returns the number of complete months between the given date-of-birth string and today.
     * Returns null when dob is absent, used as a newborn threshold guard (≤ 12 months).
     */
    public function monthsSinceDob(?string $dob): ?int
    {
        if ($dob === null || $dob === '') {
            return null;
        }

        return Carbon::parse($dob)->diffInMonths(Carbon::now());
    }

    /**
     * Normalises a raw dob attribute (string, Carbon, or null) to a Y-m-d string.
     * Returns null when dob is absent, preventing downstream Carbon::parse() errors.
     *
     * @param  mixed  $dob  Raw attribute (string, Carbon, etc.)
     */
    public function dobToDateString(mixed $dob): ?string
    {
        if ($dob === null || $dob === '') {
            return null;
        }

        return Carbon::parse($dob)->format(config('constants.DATE_FORMAT_ONLY'));
    }

    /**
     * Guards age-eligibility checks (e.g. policy-holder must be ≥ 18).
     * Returns false for missing dob to treat unknown-age members as ineligible.
     */
    public function memberIsAtLeastYearsOld(mixed $dob, int $years = 18): bool
    {
        if ($dob === null || $dob === '') {
            return false;
        }

        return Carbon::parse($dob)->age >= $years;
    }
}
