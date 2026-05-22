<?php

namespace App\Services;

use App\Enums\HealthCoverForEnum;
use App\Enums\Logger\LoggerFeatureEnum;
use App\Enums\QuoteStatusEnum;
use App\Events\HealthQuoteMigration;
use App\Models\HealthQuote;
use App\Services\HealthRevamp\HealthQuoteRevampMigrationContext;
use App\Services\HealthRevamp\HealthQuoteRevampMigrationMutator;
use App\Services\HealthRevamp\HealthQuoteRevampMigrationStateLogger;
use App\Services\Logger\LoggerService;
use Exception;

/**
 * Applies health revamp data migration logic to a single lead.
 * Intentionally omits filters on is_quote_locked and quote_status_id so locked / any status can be migrated.
 *
 * Skips entirely when the lead matches “entity health lead” semantics (active health customer_insured
 * row whose insured.customer_type is Entity), consistent with exclusion via health_revamp_bak_entity_health_leads.
 */
class HealthQuoteRevampMigrationService
{
    public const MIGRATION_STATUSES = [
        QuoteStatusEnum::Draft,
        QuoteStatusEnum::Quoted,
        QuoteStatusEnum::AMLScreeningCleared,
        QuoteStatusEnum::AMLScreeningFailed,
        QuoteStatusEnum::NewLead,
        QuoteStatusEnum::Fake,
        QuoteStatusEnum::FTCSent,
        QuoteStatusEnum::FTCAccepted,
        QuoteStatusEnum::FTCResubmitted,
        QuoteStatusEnum::Lost,
        QuoteStatusEnum::KYCCleared,
        QuoteStatusEnum::FTCPending,
        QuoteStatusEnum::FollowedUp,
        QuoteStatusEnum::InNegotiation,
        QuoteStatusEnum::ApplicationPending,
        QuoteStatusEnum::PaymentPending,
        QuoteStatusEnum::QualificationPending,
        QuoteStatusEnum::Qualified,
        QuoteStatusEnum::TransactionDeclined,
        QuoteStatusEnum::ApplicationSubmitted,
        QuoteStatusEnum::Duplicate,
        QuoteStatusEnum::PriceTooHigh,
        QuoteStatusEnum::NotContactablePe,
        QuoteStatusEnum::FollowupCall,
        QuoteStatusEnum::Interested,
        QuoteStatusEnum::NoAnswer,
        QuoteStatusEnum::NotInterested,
        QuoteStatusEnum::NotEligibleForInsurance,
        QuoteStatusEnum::IMRenewal,
        QuoteStatusEnum::PendingQuote,
        QuoteStatusEnum::Uncontactable,
        QuoteStatusEnum::Stale,
        QuoteStatusEnum::Allocated,
        QuoteStatusEnum::RenewalTermsReceived,
        QuoteStatusEnum::PendingRenewalInformation,
        QuoteStatusEnum::AdditionalInformationRequested,
        QuoteStatusEnum::QuoteRequested,
        QuoteStatusEnum::FinalizingTerms,
        QuoteStatusEnum::SentForTransactionApproval,
        QuoteStatusEnum::RenewalTermsSent,
        QuoteStatusEnum::EarlyRenewal,
        QuoteStatusEnum::PaymentLinkRequestedByCustomer,
        QuoteStatusEnum::PaymentLinkInprogress,
        QuoteStatusEnum::PaymentLinkSentToCustomer,
        QuoteStatusEnum::PaymentInitiated,
        QuoteStatusEnum::PendingBorRequest,
    ];

    private HealthQuoteRevampMigrationStateLogger $stateLogger;
    private HealthQuoteRevampMigrationMutator $mutator;
    private HealthQuoteRevampMigrationContext $context;

    public function __construct(
        ?HealthQuoteRevampMigrationStateLogger $stateLogger = null,
        ?HealthQuoteRevampMigrationMutator $mutator = null,
        ?HealthQuoteRevampMigrationContext $context = null,
    ) {
        $this->stateLogger = $stateLogger ?? app(HealthQuoteRevampMigrationStateLogger::class);
        $this->mutator = $mutator ?? app(HealthQuoteRevampMigrationMutator::class);
        $this->context = $context ?? app(HealthQuoteRevampMigrationContext::class);
    }

    public function getMigrationStatuses(): array
    {
        return self::MIGRATION_STATUSES;
    }

    /**
     * Dispatches migration for a locked health lead whose status is in the migration set.
     * Used when saving a lead via CRUDController (status-change flow).
     */
    public function dispatchForLockedLead(int $healthQuoteId, mixed $leadStatus): void
    {
        if (! in_array($leadStatus, $this->getMigrationStatuses())) {
            return;
        }

        $quote = HealthQuote::find($healthQuoteId);

        if ($quote && $quote->is_quote_locked) {
            HealthQuoteMigration::dispatch($healthQuoteId);
        }
    }

    /**
     * Dispatches migration for a non-entity health lead whose status is in the migration set.
     * Used by AMLService after insured/customer data updates.
     */
    public function dispatchForNonEntityLead(int $healthQuoteId, mixed $quoteStatusId, bool $isEntity): void
    {
        if ($isEntity) {
            return;
        }

        if (in_array($quoteStatusId, $this->getMigrationStatuses())) {
            HealthQuoteMigration::dispatch($healthQuoteId);
        }
    }

    /**
     * Dispatches migration for a newly created child health lead (CIR flow).
     * Used by SendUpdateLogController after child lead creation.
     */
    public function dispatchForNewChildLead(?int $healthQuoteId): void
    {
        if ($healthQuoteId === null) {
            return;
        }

        HealthQuoteMigration::dispatch($healthQuoteId);
    }

    public function isMigrated(HealthQuote $healthQuote): bool
    {
        return in_array($healthQuote->cover_for_id, [
            HealthCoverForEnum::INDIVIDUAL_AND_FAMILIES->value,
            HealthCoverForEnum::DOMESTIC_HELPER->value,
        ]);
    }

    public function migrateLead(int $healthQuoteId): void
    {
        $healthQuote = HealthQuote::find($healthQuoteId);

        if (! $healthQuote || $this->isMigrated($healthQuote)) {
            return;
        }

        if ($healthQuote->isEntity()) {
            return;
        }

        LoggerService::startQuoteLogging($healthQuote->code, LoggerFeatureEnum::HEALTH_QUOTE_REVAMP);

        try {
            $beforeSnapshots = $this->stateLogger->logLeadStateBefore($healthQuote);
            $this->mutator->applyAll($healthQuote);
            $this->stateLogger->logLeadStateAfter(
                $healthQuote,
                $beforeSnapshots['health_quote'],
                $beforeSnapshots['customer_members'],
            );
        } catch (Exception $e) {
            LoggerService::error('Error migrating health quote', exception: $e);
            throw new Exception('Unable to migrate health quote data.');
        } finally {
            LoggerService::endLogging();
        }
    }
}
