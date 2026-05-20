<?php

namespace App\Services\HealthRevamp;

use App\Enums\CustomerTypeEnum;
use App\Enums\QuoteTypeId;
use App\Models\CustomerInsured;
use App\Models\CustomerMembers;
use App\Models\HealthQuote;

final class HealthQuoteRevampMigrationQueries
{
    public const QUOTE_MORPH = HealthQuote::class;

    public function __construct(
        private readonly HealthQuoteRevampMigrationContext $context,
    ) {}

    /**
     * Base builder for Individual, non-third-party-payer customer members on a health quote.
     * All other query methods on this class chain from this builder to keep filter logic in one place.
     */
    public function healthMembersQuery(HealthQuote $hqr)
    {
        return CustomerMembers::query()
            ->where('quote_type', self::QUOTE_MORPH)
            ->where('quote_id', $hqr->id)
            ->whereNull('deleted_at')
            ->notThirdPartyPayer()
            ->individual();
    }

    /**
     * Returns true when at least one eligible Individual member row already exists for the quote.
     * Used as an early-exit guard before attempting to insert synthetic member rows.
     */
    public function hasIndividualMembers(HealthQuote $hqr): bool
    {
        return $this->healthMembersQuery($hqr)->exists();
    }

    /**
     * Returns true when a policy-holder member already exists whose name matches the quote holder
     * and who is at least 18 years old. Prevents inserting a duplicate policy-holder row when
     * a valid one is already present but hasn't been flagged yet.
     */
    public function hasAdultNameMatchPolicyHolder(HealthQuote $hqr): bool
    {
        return $this->healthMembersQuery($hqr)
            ->policyHolder()
            ->where('first_name', $hqr->first_name)
            ->where('last_name', $hqr->last_name)
            ->whereNotNull('dob')
            ->select(['id', 'dob'])
            ->get()
            ->contains(fn (CustomerMembers $m) => $this->context->memberIsAtLeastYearsOld($m->dob, 18));
    }

    /**
     * Returns true when an active customer_insured row exists for this health quote and customer.
     * When true, member insertion is skipped — the insured link is the source of truth.
     */
    public function hasActiveHealthInsured(HealthQuote $hqr): bool
    {
        return CustomerInsured::query()
            ->forQuote(QuoteTypeId::Health, $hqr->id)
            ->where('customer_id', $hqr->customer_id)
            ->active()
            ->exists();
    }

    /**
     * Finds the first active customer_insured row linked to an Individual insured for the quote.
     * Used to decide whether a synthetic Individual member should be inserted from an existing insured link.
     */
    public function findActiveIndividualInsuredRow(HealthQuote $hqr): ?CustomerInsured
    {
        return CustomerInsured::query()
            ->forQuote(QuoteTypeId::Health, $hqr->id)
            ->where('customer_id', $hqr->customer_id)
            ->active()
            ->join('insured', 'insured.id', '=', 'customer_insured.insured_id')
            ->where('insured.customer_type', CustomerTypeEnum::Individual)
            ->select('customer_insured.id')
            ->first();
    }

}
