<?php

declare(strict_types=1);

namespace App\Services\AML;

use App\Enums\AmlAutomationStatus;
use App\Enums\AMLStatusCode;
use App\Enums\ApplicationStorageEnums;
use App\Enums\InsuranceProvidersEnum;
use App\Enums\PolicyIssuanceEnum;
use App\Enums\QuoteTypes;
use App\Models\AmlAutomation;
use App\Models\PersonalQuote;
use App\Services\ApplicationStorageService;
use App\Services\Quotes\CyberQuoteService;
use App\Services\Quotes\PersonalQuoteAmlAutomationCustomerService;
use App\Services\TravelQuoteService;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\HttpFoundation\Response;

/**
 * Immutable result from {@see AMLAutomationService::check()}.
 */
final class AMLAutomationEligibilityResult
{
    private function __construct(
        public readonly bool $eligible,
        /** Human-readable explanation returned to the caller (e.g. API response message). */
        public readonly string $reason,
        /** Machine-readable short code for logging and test assertions. */
        public readonly string $reasonCode,
        /** Suggested HTTP status for API callers; 200 when eligible. */
        public readonly int $httpStatus,
    ) {}

    public static function pass(): self
    {
        return new self(true, '', '', Response::HTTP_OK);
    }

    /**
     * @param  int  $httpStatus  Defaults to 422 (Unprocessable Entity).
     */
    public static function block(string $reason, string $reasonCode, int $httpStatus = 422): self
    {
        return new self(false, $reason, $reasonCode, $httpStatus);
    }

    public function isEligible(): bool
    {
        return $this->eligible;
    }
}

/**
 * Single entry point for all pre-dispatch eligibility checks for AML screening automation,
 * and single source of truth for LOBs that may receive API-triggered AML automation.
 *
 * Used by every caller that creates an {@see AmlAutomation} Queue record and dispatches
 * {@see AmlScreeningAutomationJob}, ensuring validation runs exactly once
 * regardless of entry point (IMCRM API route or the scheduled Artisan command).
 *
 * Checks are ordered fail-fast (cheapest / most likely to fail first):
 *   1. Global CMS feature flag
 *   2. Policy issuance API status (unless LOB/provider skips it)
 *   3. AML status must be null or Pending
 *   4. Automation row must not already be Complete / Processing / Queue
 *   5. Customer insured data completeness
 *
 * Callers must load the `insuranceProvider` relation on `$quote` before calling
 * {@see self::check()} so that LOB/provider-specific rules can read it without
 * triggering an extra query inside this service.
 */
final class AMLAutomationService
{
    public function __construct(
        private readonly ApplicationStorageService $applicationStorageService,
        private readonly PersonalQuoteAmlAutomationCustomerService $personalQuoteCustomerService,
    ) {}

    // -------------------------------------------------------------------------
    // Eligibility checks
    // -------------------------------------------------------------------------

    /**
     * Run all eligibility checks for the given quote.
     *
     * @param  QuoteTypes  $quoteType  The resolved LOB enum.
     * @param  Model  $quote  Quote model with `insuranceProvider` already loaded.
     */
    public function check(QuoteTypes $quoteType, Model $quote): AMLAutomationEligibilityResult
    {
        // 1. Global: AML automation feature flag (CMS)
        if (! $this->applicationStorageService->getValueByKey(ApplicationStorageEnums::AML_AUTOMATION_ENABLED)) {
            return AMLAutomationEligibilityResult::block('AML automation is not enabled', 'aml_automation_disabled');
        }

        // 2. Global: policy issuance API status must be YES (unless the LOB/provider skips it)
        $skipsApiIssuanceCheck = self::skipsApiIssuanceStatusCheckForAutomatedAml($quoteType, $quote);
        if (! $skipsApiIssuanceCheck && (int) $quote->api_issuance_status_id !== (int) PolicyIssuanceEnum::POLICY_ISSUANCE_API_STATUS_YES_ID) {
            return AMLAutomationEligibilityResult::block(
                'Policy issuance API status must be confirmed',
                'api_issuance_status_not_yes'
            );
        }

        // 3. Global: AML status must be null (never screened) or explicitly Pending
        $isAmlPending = empty($quote->aml_status) || $quote->aml_status === AMLStatusCode::AMLPending;
        if (! $isAmlPending) {
            return AMLAutomationEligibilityResult::block(
                'AML status must be pending or empty',
                'aml_status_not_pending'
            );
        }

        // 4. Global: existing automation row must not be in a non-retriable state
        $automationRowBlock = $this->checkAutomationRowState((string) $quote->code);
        if ($automationRowBlock !== null) {
            return $automationRowBlock;
        }

        // 5. Customer insured data must exist and be complete for screening
        $customerBlock = $this->checkCustomerDataCompleteness($quoteType, $quote);
        if ($customerBlock !== null) {
            return $customerBlock;
        }

        return AMLAutomationEligibilityResult::pass();
    }

    /**
     * Check whether an existing AmlAutomation row blocks re-dispatch.
     *
     * Complete and Processing states are terminal/in-flight and must not be re-triggered.
     * Queue means the job is already waiting — also blocked.
     * Failed and null are retriable.
     */
    private function checkAutomationRowState(string $quoteCode): ?AMLAutomationEligibilityResult
    {
        $automation = AmlAutomation::where('code', $quoteCode)->first();
        if ($automation === null) {
            return null;
        }

        $automationStatus = AmlAutomationStatus::tryFrom((string) $automation->status);

        if ($automationStatus === AmlAutomationStatus::Complete) {
            return AMLAutomationEligibilityResult::block(
                'AML automation already completed.',
                'automation_completed'
            );
        }

        if ($automationStatus === AmlAutomationStatus::Processing) {
            return AMLAutomationEligibilityResult::block(
                'AML automation already in progress',
                'automation_processing'
            );
        }

        if ($automationStatus === AmlAutomationStatus::Queue) {
            return AMLAutomationEligibilityResult::block(
                'AML automation already queued',
                'automation_already_queued'
            );
        }

        return null;
    }

    /**
     * Resolve customer info for the LOB and verify required AML fields are present.
     *
     * Each LOB delegates to its own customer-info service (Travel, Cyber, or the shared
     * PersonalQuote service for Savings and Device). Unknown LOBs pass through — the
     * job itself will catch missing data at execution time.
     */
    private function checkCustomerDataCompleteness(QuoteTypes $quoteType, Model $quote): ?AMLAutomationEligibilityResult
    {
        [$customerRecord, $checkResult] = match ($quoteType) {
            QuoteTypes::TRAVEL => $this->resolveTravelCustomerData((int) $quote->id, $quoteType->value),
            QuoteTypes::CYBER => $this->resolveCyberCustomerData((int) $quote->id, $quoteType->value),
            QuoteTypes::SAVINGS, QuoteTypes::DEVICE => $this->resolvePersonalQuoteCustomerData((int) $quote->id, (int) $quote->quote_type_id),
            default => [true, ['status' => true, 'message' => '']],
        };

        if ($customerRecord === false || (is_object($customerRecord) && empty($customerRecord->id))) {
            return AMLAutomationEligibilityResult::block(
                'Customer insured data not found for AML',
                'customer_insured_not_found'
            );
        }

        if (! $checkResult['status']) {
            return AMLAutomationEligibilityResult::block(
                $checkResult['message'] ?: 'Incomplete customer data for AML',
                'customer_insured_incomplete'
            );
        }

        return null;
    }

    /**
     * @return array{0: array|false, 1: array{status: bool, message: string}}
     */
    private function resolveTravelCustomerData(int $quoteId, string $quoteTypeValue): array
    {
        $service = app(TravelQuoteService::class);
        $info = (array) $service->getCustomerTravelInfo($quoteId, $quoteTypeValue);

        if (empty($info['id'])) {
            return [false, ['status' => false, 'message' => 'Customer travel info not found']];
        }

        return [$info, $service->checkCustomerTravelInfoIsComplete($info)];
    }

    /**
     * @return array{0: array|false, 1: array{status: bool, message: string}}
     */
    private function resolveCyberCustomerData(int $quoteId, string $quoteTypeValue): array
    {
        $service = app(CyberQuoteService::class);
        $info = (array) $service->getCustomerCyberInfo($quoteId, $quoteTypeValue);

        if (empty($info['id'])) {
            return [false, ['status' => false, 'message' => 'Customer cyber info not found']];
        }

        return [$info, $service->checkCustomerCyberInfoIsComplete($info)];
    }

    /**
     * Shared resolver for Savings and Device (both backed by PersonalQuote).
     *
     * @return array{0: object|false, 1: array{status: bool, message: string}}
     */
    private function resolvePersonalQuoteCustomerData(int $quoteId, int $quoteTypeId): array
    {
        $record = $this->personalQuoteCustomerService->getCustomerPersonalQuoteAmlInfo($quoteId, $quoteTypeId);

        if ($record === false || empty($record->id)) {
            return [false, ['status' => false, 'message' => 'Customer insured data not found']];
        }

        return [$record, $this->personalQuoteCustomerService->checkCustomerPersonalQuoteAmlInfoIsComplete((array) $record)];
    }

    // -------------------------------------------------------------------------
    // LOB registry (formerly AmlAutomatableLobRegistry)
    // -------------------------------------------------------------------------

    /**
     * Only these LOBs are allowed to trigger AML automation from API calls.
     *
     * @return list<QuoteTypes>
     */
    public static function allowedLobsFromAPI(): array
    {
        return [
            QuoteTypes::SAVINGS,
        ];
    }

    public static function isLobAllowedForAmlAutomationScreeningSucceededEvent(QuoteTypes $quoteType): bool
    {
        return in_array($quoteType, [QuoteTypes::SAVINGS], true);
    }

    /**
     * Whether to skip the `api_issuance_status_id = YES` pre-check for this quote.
     *
     * Savings + OIC: policy issuance API status is not expected before AML automation.
     * All other LOBs and providers keep the status check in place.
     */
    public static function skipsApiIssuanceStatusCheckForAutomatedAml(QuoteTypes $quoteType, Model $quoteRequest): bool
    {
        if ($quoteType === QuoteTypes::SAVINGS && $quoteRequest instanceof PersonalQuote) {
            return $quoteRequest->insuranceProvider?->code === InsuranceProvidersEnum::OIC;
        }

        return false;
    }
}
