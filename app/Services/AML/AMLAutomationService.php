<?php

declare(strict_types=1);

namespace App\Services\AML;

use App\Enums\AmlAutomationStatus;
use App\Enums\AMLStatusCode;
use App\Enums\ApplicationStorageEnums;
use App\Enums\InsuranceProvidersEnum;
use App\Enums\Logger\LoggerFeatureEnum;
use App\Enums\PolicyIssuanceEnum;
use App\Enums\QuoteTypes;
use App\Jobs\AmlScreeningAutomationJob;
use App\Models\AmlAutomation;
use App\Models\PersonalQuote;
use App\Services\ApplicationStorageService;
use App\Services\Logger\LoggerService;
use App\Services\Quotes\CyberQuoteService;
use App\Services\Quotes\PersonalQuoteAmlAutomationCustomerService;
use App\Services\TravelQuoteService;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\HttpFoundation\Response;

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
 * After calling {@see self::check()}, inspect the result via {@see self::isEligible()},
 * {@see self::$reason}, {@see self::$reasonCode}, and {@see self::$httpStatus}.
 *
 * Callers must load the `insuranceProvider` relation on `$quote` before calling
 * {@see self::check()} so that LOB/provider-specific rules can read it without
 * triggering an extra query inside this service.
 */
class AMLAutomationService
{
    use GenericQueriesAllLobs;

    // -------------------------------------------------------------------------
    // Result state — populated by check(); read by callers after check() returns
    // -------------------------------------------------------------------------

    /** Whether the quote passed all eligibility checks. */
    public bool $eligible = false;

    /** Human-readable explanation returned to the caller (e.g. API response message). */
    public string $reason = '';

    /** Machine-readable short code for logging and test assertions. */
    public string $reasonCode = '';

    /** Suggested HTTP status for API callers; 200 when eligible. */
    public int $httpStatus = Response::HTTP_UNPROCESSABLE_ENTITY;

    // -------------------------------------------------------------------------

    public function __construct(
        private readonly ApplicationStorageService $applicationStorageService,
        private readonly PersonalQuoteAmlAutomationCustomerService $personalQuoteCustomerService,
    ) {}

    /**
     * IMCRM API entry point: validate eligibility then synchronously run AML automation
     * for a single quote identified by UUID and explicit LOB.
     *
     * All eligibility checks are delegated to {@see AMLAutomationService} so
     * they are shared with the Artisan command path and never duplicated.
     *
     * The LOB-match guard (quote's stored type vs. the requested type) is the only check
     * kept here because it is specific to this API surface — the command already knows
     * the LOB from its own query loop.
     *
     * @return array{success: bool, http_status: int, message: string, data?: array<string, mixed>}
     */
    public function initiateAutomatedAmlByQuoteUuid(string $quoteUuid, QuoteTypes $quoteType): array
    {
        LoggerService::startFeatureLogging(LoggerFeatureEnum::AML_AUTOMATION_BY_QUOTE_UUID);
        LoggerService::info('AML automate-by-uuid: request received', extra: [
            'quoteUuid' => $quoteUuid,
            'quoteType' => $quoteType->value,
        ]);

        $respond = function (bool $success, int $httpStatus, string $message, ?array $data = null): array {
            $payload = ['success' => $success, 'http_status' => $httpStatus, 'message' => $message];
            if ($data !== null) {
                $payload['data'] = $data;
            }

            return $payload;
        };

        try {
            $quote = $this->getQuoteObject($quoteType->value, $quoteUuid);

            $quoteContext = [
                'quoteUuid' => $quoteUuid,
                'quoteType' => $quoteType->value,
                'quoteId' => (int) $quote->id,
                'quoteCode' => $quote->code,
                'quoteTypeId' => (int) $quote->quote_type_id,
                'customerId' => $quote->customer_id !== null ? (int) $quote->customer_id : null,
                'amlStatus' => $quote->aml_status,
                'apiIssuanceStatusId' => $quote->api_issuance_status_id !== null ? (int) $quote->api_issuance_status_id : null,
                'insuranceProviderCode' => $quote->insuranceProvider?->code,
            ];

            // EligibilityCheck: CMS flag, issuance status, AML status, automation row state, LOB-specific constraints, and customer data completeness
            $eligibilityCheck = $this->eligibilityCheck($quoteType, $quote);
            if (! $eligibilityCheck->isEligible()) {
                LoggerService::info('AML automate-by-uuid: blocked — eligibility check failed', extra: array_merge($quoteContext, [
                    'outcome' => 'blocked',
                    'reason' => $eligibilityCheck->reasonCode,
                    'message' => $eligibilityCheck->reason,
                    'http_status' => $eligibilityCheck->httpStatus,
                ]));

                return $respond(false, $eligibilityCheck->httpStatus, $eligibilityCheck->reason);
            }

            LoggerService::info('AML automate-by-uuid: all eligibility checks passed', extra: array_merge($quoteContext, [
                'outcome' => 'progress',
                'step' => 'eligibility_passed',
            ]));

            if ($quote === false) {
                LoggerService::info('AML automate-by-uuid: blocked — quote not found', extra: array_merge($quoteContext, [
                    'quoteUuid' => $quoteUuid,
                    'quoteType' => $quoteType->value,
                    'outcome' => 'blocked',
                    'reason' => 'quote_not_found',
                    'http_status' => Response::HTTP_NOT_FOUND,
                ]));

                return $respond(false, Response::HTTP_NOT_FOUND, 'Quote not found');
            }

            LoggerService::startQuoteLogging($quote, LoggerFeatureEnum::AML_AUTOMATION_BY_QUOTE_UUID);

            $quote->loadMissing('insuranceProvider');

            LoggerService::info('AML automate-by-uuid: quote loaded', extra: array_merge($quoteContext, [
                'outcome' => 'progress',
                'step' => 'quote_loaded',
            ]));
            $quoteTypeFromQuote = QuoteTypes::getName((int) $quote->quote_type_id);
            if (! $quoteTypeFromQuote instanceof QuoteTypes || $quoteTypeFromQuote !== $quoteType) {
                LoggerService::info('AML automate-by-uuid: blocked — quote LOB does not match requested quoteType', extra: array_merge($quoteContext, [
                    'outcome' => 'blocked',
                    'reason' => 'quote_lob_mismatch',
                    'resolvedQuoteType' => $quoteTypeFromQuote instanceof QuoteTypes ? $quoteTypeFromQuote->value : null,
                    'http_status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                ]));

                return $respond(false, Response::HTTP_UNPROCESSABLE_ENTITY, 'Quote does not match the requested line of business');
            }

            try {
                AmlAutomation::updateOrCreate(
                    ['code' => $quote->code],
                    ['status' => AmlAutomationStatus::Queue->value]
                );

                LoggerService::info('AML automate-by-uuid: running job via dispatchSync', extra: array_merge($quoteContext, [
                    'outcome' => 'progress',
                    'step' => 'dispatch_sync',
                ]));

                AmlScreeningAutomationJob::dispatchSync($quoteType, $quote);

                LoggerService::info('AML automate-by-uuid: successfully — dispatched', extra: array_merge($quoteContext, [
                    'outcome' => 'success',
                    'step' => 'dispatch_sync_complete',
                    'http_status' => Response::HTTP_OK,
                ]));

                return $respond(true, Response::HTTP_OK, 'AML screening automation dispatched', [
                    'dispatch_sync' => true,
                    'quote_code' => $quote->code,
                ]);
            } catch (\Throwable $e) {
                LoggerService::error('AML automate-by-uuid: post-validation failure', array_merge($quoteContext, [
                    'outcome' => 'error',
                    'reason' => 'post_validation_exception',
                    'exceptionMessage' => $e->getMessage(),
                ]), $e);

                return $respond(false, Response::HTTP_INTERNAL_SERVER_ERROR, 'Unable to complete AML automation: '.$e->getMessage());
            }
        } finally {
            LoggerService::endLogging();
        }
    }

    // -------------------------------------------------------------------------
    // Eligibility checks
    // -------------------------------------------------------------------------

    /**
     * Run all eligibility checks for the given quote.
     *
     * Returns $this so callers can read result state immediately:
     *   $result = $service->check($type, $quote);
     *   if (! $result->isEligible()) { ... $result->reason ... }
     *
     * @param  QuoteTypes  $quoteType  The resolved LOB enum.
     * @param  Model  $quote  Quote model with `insuranceProvider` already loaded.
     */
    public function eligibilityCheck(QuoteTypes $quoteType, Model $quote): self
    {
        // 1. Global: AML automation feature flag (CMS)
        if (! $this->applicationStorageService->getValueByKey(ApplicationStorageEnums::AML_AUTOMATION_ENABLED)) {
            return $this->block('AML automation is not enabled', 'aml_automation_disabled');
        }

        // 2. Global: policy issuance API status must be YES (unless the LOB/provider skips it)
        $skipsApiIssuanceCheck = self::skipsApiIssuanceStatusCheckForAutomatedAml($quoteType, $quote);
        if (! $skipsApiIssuanceCheck && (int) $quote->api_issuance_status_id !== (int) PolicyIssuanceEnum::POLICY_ISSUANCE_API_STATUS_YES_ID) {
            return $this->block('Policy issuance API status must be confirmed', 'api_issuance_status_not_yes');
        }

        // 3. Global: AML status must be null (never screened) or explicitly Pending
        $isAmlPending = empty($quote->aml_status) || $quote->aml_status === AMLStatusCode::AMLPending;
        if (! $isAmlPending) {
            return $this->block('AML status must be pending or empty', 'aml_status_not_pending');
        }

        // 4. Global: existing automation row must not be in a non-retriable state
        if ($this->checkAutomationRowState((string) $quote->code)) {
            return $this;
        }

        // 5. Customer insured data must exist and be complete for screening
        if ($this->checkCustomerDataCompleteness($quoteType, $quote)) {
            return $this;
        }

        return $this->pass();
    }

    public function isEligible(): bool
    {
        return $this->eligible;
    }

    // -------------------------------------------------------------------------
    // Internal result helpers
    // -------------------------------------------------------------------------

    private function pass(): self
    {
        $this->eligible = true;
        $this->reason = '';
        $this->reasonCode = '';
        $this->httpStatus = Response::HTTP_OK;

        return $this;
    }

    /**
     * @param  int  $httpStatus  Defaults to 422 (Unprocessable Entity).
     */
    private function block(string $reason, string $reasonCode, int $httpStatus = Response::HTTP_UNPROCESSABLE_ENTITY): self
    {
        $this->eligible = false;
        $this->reason = $reason;
        $this->reasonCode = $reasonCode;
        $this->httpStatus = $httpStatus;

        return $this;
    }

    // -------------------------------------------------------------------------
    // Private check helpers
    // -------------------------------------------------------------------------

    /**
     * Check whether an existing AmlAutomation row blocks re-dispatch.
     *
     * Complete and Processing states are terminal/in-flight and must not be re-triggered.
     * Queue means the job is already waiting — also blocked.
     * Failed and null are retriable.
     *
     * Returns true when a block was set (caller should return $this immediately).
     */
    private function checkAutomationRowState(string $quoteCode): bool
    {
        $automation = AmlAutomation::where('code', $quoteCode)->first();
        if ($automation === null) {
            return false;
        }

        $automationStatus = AmlAutomationStatus::tryFrom((string) $automation->status);

        /** @var array{0: string, 1: string}|null $blocked */
        $blocked = match ($automationStatus) {
            AmlAutomationStatus::Complete => ['AML automation already completed.', 'automation_completed'],
            AmlAutomationStatus::Processing => ['AML automation already in progress', 'automation_processing'],
            AmlAutomationStatus::Queue => ['AML automation already queued', 'automation_already_queued'],
            default => null,
        };

        if ($blocked !== null) {
            $this->block(...$blocked);
        }

        return $blocked !== null;
    }

    /**
     * Resolve customer info for the LOB and verify required AML fields are present.
     *
     * Returns true when a block was set (caller should return $this immediately).
     */
    private function checkCustomerDataCompleteness(QuoteTypes $quoteType, Model $quote): bool
    {
        [$customerRecord, $checkResult] = match ($quoteType) {
            QuoteTypes::TRAVEL => $this->resolveTravelCustomerData((int) $quote->id, $quoteType->value),
            QuoteTypes::CYBER => $this->resolveCyberCustomerData((int) $quote->id, $quoteType->value),
            QuoteTypes::SAVINGS, QuoteTypes::DEVICE => $this->resolvePersonalQuoteCustomerData((int) $quote->id, (int) $quote->quote_type_id),
            default => [true, ['status' => true, 'message' => '']],
        };

        if ($customerRecord === false || (is_object($customerRecord) && empty($customerRecord->id))) {
            $this->block('Customer insured data not found for AML', 'customer_insured_not_found');

            return true;
        }

        if (! $checkResult['status']) {
            $this->block(
                $checkResult['message'] ?: 'Incomplete customer data for AML',
                'customer_insured_incomplete'
            );

            return true;
        }

        return false;
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
