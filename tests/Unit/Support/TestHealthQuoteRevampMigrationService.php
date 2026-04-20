<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use App\Models\HealthQuote;
use App\Services\HealthQuoteRevampMigrationService;

/**
 * Exposes protected/private helpers for unit testing only.
 */
final class TestHealthQuoteRevampMigrationService extends HealthQuoteRevampMigrationService
{
    public function exposeIsEntityHealthLead(HealthQuote $hqr): bool
    {
        return $this->isEntityHealthLead($hqr);
    }

    public function exposeMemberIsAtLeastYearsOld(mixed $dob, int $years = 18): bool
    {
        return $this->memberIsAtLeastYearsOld($dob, $years);
    }

    public function exposeMonthsSinceDob(?string $dob): ?int
    {
        return $this->monthsSinceDob($dob);
    }

    public function exposeDobToDateString(mixed $dob): ?string
    {
        return $this->dobToDateString($dob);
    }

    /**
     * @return list<string>
     */
    public function exposeAllowedCustomerTypesForQuote(HealthQuote $hqr): array
    {
        return $this->allowedCustomerTypesForQuote($hqr);
    }
}
