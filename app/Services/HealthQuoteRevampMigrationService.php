<?php

namespace App\Services;

use App\Enums\Logger\LoggerFeatureEnum;
use App\Enums\QuoteStatusEnum;
use App\Models\HealthQuote;
use App\Services\HealthRevamp\HealthQuoteRevampMigrationContext;
use App\Services\HealthRevamp\HealthQuoteRevampMigrationMutator;
use App\Services\HealthRevamp\HealthQuoteRevampMigrationStateLogger;
use App\Services\Logger\LoggerService;
use Exception;
use Illuminate\Support\Facades\DB;

/**
 * Applies health revamp data migration logic (health-revamp-3-migrationScript.sql) to a single lead.
 * Intentionally omits filters on is_quote_locked and quote_status_id so locked / any status can be migrated.
 *
 * Skips entirely when the lead matches “entity health lead” semantics (active health customer_insured
 * row whose insured.customer_type is Entity), consistent with exclusion via health_revamp_bak_entity_health_leads.
 */
class HealthQuoteRevampMigrationService
{
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

    public function getMirationStatuses(): array
    {
        return [
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
    }

    public function isMigrated(HealthQuote $healthQuote): bool
    {
        return $healthQuote->insure_code !== null && $healthQuote->policy_holder_code !== null;
    }

    public function migrateLead(HealthQuote $healthQuote): void
    {
        if (! $healthQuote || $this->isMigrated($healthQuote)) {
            return;
        }

        if ($this->context->isEntityHealthLead($healthQuote)) {
            return;
        }

        LoggerService::startQuoteLogging($healthQuote->code, LoggerFeatureEnum::HEALTH_QUOTE_REVAMP);

        try {
            $beforeSnapshots = $this->stateLogger->logLeadStateBefore($healthQuote);
            DB::transaction(function () use ($healthQuote, $beforeSnapshots) {
                $this->mutator->applyAll($healthQuote);
                $this->stateLogger->logLeadStateAfter(
                    $healthQuote,
                    $beforeSnapshots['health_quote'],
                    $beforeSnapshots['customer_members'],
                );
            });
        } catch (Exception $e) {
            LoggerService::error('Error migrating health quote', ['error' => $e->getMessage()]);

            return;
        } finally {
            LoggerService::endLogging();
        }
    }
}
