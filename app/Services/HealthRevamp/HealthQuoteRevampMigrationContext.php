<?php

namespace App\Services\HealthRevamp;

use App\Enums\CustomerTypeEnum;
use App\Enums\QuoteTypeId;
use App\Models\CustomerInsured;
use App\Models\HealthQuote;
use Carbon\Carbon;

final class HealthQuoteRevampMigrationContext
{
    public function monthsSinceDob(?string $dob): ?int
    {
        if ($dob === null || $dob === '') {
            return null;
        }

        return Carbon::parse($dob)->diffInMonths(Carbon::now());
    }

    /**
     * @param  mixed  $dob  Raw attribute (string, Carbon, etc.)
     */
    public function dobToDateString(mixed $dob): ?string
    {
        if ($dob === null || $dob === '') {
            return null;
        }

        return Carbon::parse($dob)->format('Y-m-d');
    }

    /**
     * Same notion as health_revamp_bak_entity_health_leads in health-revamp-2-migrationScript-backup.sql.
     */
    public function isEntityHealthLead(HealthQuote $hqr): bool
    {
        if ($hqr->customer_id === null) {
            return false;
        }

        $insured = $hqr->latestInsured()
            ->where('customer_insured.customer_id', $hqr->customer_id)
            ->first();

        return $insured !== null && $insured->customer_type === CustomerTypeEnum::Entity;
    }

    public function memberIsAtLeastYearsOld(mixed $dob, int $years = 18): bool
    {
        if ($dob === null || $dob === '') {
            return false;
        }

        return Carbon::parse($dob)->age >= $years;
    }

    /**
     * @return list<string>
     */
    public function allowedCustomerTypesForQuote(HealthQuote $hqr): array
    {
        $types = CustomerInsured::query()
            ->where('quote_request_id', $hqr->id)
            ->where('quote_type_id', QuoteTypeId::Health)
            ->where('is_active', true)
            ->join('insured', 'insured.id', '=', 'customer_insured.insured_id')
            ->distinct()
            ->pluck('insured.customer_type')
            ->filter()
            ->values()
            ->all();

        if ($types === []) {
            return ['Individual'];
        }

        return $types;
    }
}
