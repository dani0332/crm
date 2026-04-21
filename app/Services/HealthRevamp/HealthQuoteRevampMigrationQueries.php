<?php

namespace App\Services\HealthRevamp;

use App\Models\CustomerMembers;
use App\Models\HealthQuote;

final class HealthQuoteRevampMigrationQueries
{
    public const QUOTE_MORPH = HealthQuote::class;

    public function healthMembersBaseQuery(HealthQuote $hqr)
    {
        return $this->healthMembersBaseQueryByQuoteId((int) $hqr->id);
    }

    public function healthMembersBaseQueryByQuoteId(int $quoteId)
    {
        return CustomerMembers::query()
            ->where('quote_type', self::QUOTE_MORPH)
            ->where('quote_id', $quoteId)
            ->whereNull('deleted_at');
    }

    public function nextIndividualCodeSuffix(int $customerEntityId): int
    {
        return 1 + CustomerMembers::query()
            ->where('customer_entity_id', $customerEntityId)
            ->where('customer_type', 'Individual')
            ->whereNull('deleted_at')
            ->count();
    }
}
