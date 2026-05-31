<?php

namespace App\Jobs;

use App\Enums\AmlAutomationStatus;
use App\Enums\AMLStatusCode;
use App\Enums\ApplicationStorageEnums;
use App\Enums\CustomerTypeEnum;
use App\Enums\QuoteTypes;
use App\Events\AmlAutomationScreeningSucceeded;
use App\Models\AmlAutomation;
use App\Models\PersonalQuote;
use App\Models\TravelQuote;
use App\Services\AMLService;
use App\Services\ApplicationStorageService;
use App\Services\Logger\LoggerService;
use App\Services\Quotes\CyberQuoteService;
use App\Services\Quotes\PersonalQuoteAmlAutomationCustomerService;
use App\Services\TravelQuoteService;
use App\Support\AmlQuoteAutomation\AmlAutomatableLobRegistry;
use App\Support\AmlQuoteAutomation\AmlAutomationEligibilityService;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Symfony\Component\HttpFoundation\Response;

/**
 * Executes AML screening automation for a single quote.
 *
 * This job trusts that the caller (command or IMCRM API) already validated eligibility
 * via {@see AmlAutomationEligibilityService} and set the
 * {@see AmlAutomation} record to Queue status before dispatching.
 *
 * The only pre-condition re-checked here is:
 *   1. The global AML automation CMS flag (can be toggled at any time).
 *   2. The {@see AmlAutomation} row is still in Queue status (race-condition guard —
 *      prevents duplicate execution if two dispatches race each other).
 *
 * All other eligibility checks (api_issuance_status, aml_status, LOB constraints,
 * customer data completeness) are authorised by the Queue record itself; re-checking
 * them here would be wasteful duplication for large user volumes.
 */
class AmlScreeningAutomationJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use GenericQueriesAllLobs;

    public int $timeout = 60;
    public int $tries = 1;
    public int $uniqueFor = 60 * 15; // 15 minutes
    public string $uniqueKey = '';
    private string $className = 'AmlScreeningAutomationJob';
    private TravelQuote|PersonalQuote $quoteRequest;
    private QuoteTypes $quoteType;
    private string $quoteRefId;

    public function __construct(QuoteTypes $quoteType, TravelQuote|PersonalQuote $quoteRequest)
    {
        $this->quoteType = $quoteType;
        $this->quoteRequest = $quoteRequest;
        $this->quoteRefId = $this->quoteRequest?->code ?? '';
        $this->uniqueKey = strtolower($this->quoteRefId).'-'.strtolower($quoteType->value);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $loggerPrefix = $this->className.' - Ref-ID: '.$this->quoteRefId.' - ';
        $baseContext = ['quote_code' => $this->quoteRefId, 'quote_type' => $this->quoteType->value];

        $exitReason = $this->getExitReason();
        if ($exitReason !== null) {
            // Persist the reason for observability; look up the record by code since quoteRequest
            // may not have been refreshed yet (e.g. CMS-disabled exit happens before refresh).
            $amlAutomation = AmlAutomation::where('code', $this->quoteRefId)->first();
            LoggerService::info($loggerPrefix.'Exiting: '.$exitReason, extra: array_merge($baseContext, [
                'aml_automation_id' => $amlAutomation?->id,
                'aml_automation_status' => $amlAutomation?->status,
                'exit_reason' => $exitReason,
            ]));
            $amlAutomation?->update(['status' => AmlAutomationStatus::Failed->value, 'result' => $exitReason]);

            return;
        }

        // After getExitReason() passes we know the Queue record exists on quoteRequest.
        $queuedAutomation = $this->quoteRequest->amlAutomation;
        $baseContext['aml_automation_id'] = $queuedAutomation?->id;

        $customerInfo = $this->getCustomerInfo();
        if (empty($customerInfo)) {
            $reason = 'Customer info not found or incomplete';
            LoggerService::info($loggerPrefix.'Exiting: '.$reason, extra: $baseContext);
            $queuedAutomation?->update(['status' => AmlAutomationStatus::Failed->value, 'result' => $reason]);

            return;
        }

        $checkCustomerInfo = $this->checkCustomerInfoIsComplete($customerInfo);
        if (! $checkCustomerInfo['status']) {
            $reason = $checkCustomerInfo['message'] ?? 'Customer info incomplete';
            LoggerService::info($loggerPrefix.'Exiting: '.$reason, extra: $baseContext);
            $queuedAutomation?->update(['status' => AmlAutomationStatus::Failed->value, 'result' => $reason]);

            return;
        }

        $idType = $customerInfo['id_type'] ?? 'passport';
        $idNumber = $customerInfo['id_number'] ?? null;

        $amlAutomation = AmlAutomation::updateOrCreate(
            ['code' => $this->quoteRefId],
            ['status' => AmlAutomationStatus::Processing->value]
        );

        LoggerService::info($loggerPrefix.'Processing started', extra: array_merge($baseContext, [
            'aml_automation_id' => $amlAutomation->id,
        ]));

        try {
            $insuredPersonData = app(AMLService::class)->getInsuredPersonDetails($idType, $idNumber);
            LoggerService::info($loggerPrefix.'getInsuredPersonDetails response: '.($insuredPersonData ? Response::HTTP_OK : Response::HTTP_NOT_FOUND), extra: array_merge($baseContext, [
                'aml_automation_id' => $amlAutomation->id,
                'found' => (bool) $insuredPersonData,
            ]));

            $customer = ! empty($insuredPersonData)
                ? [...$customerInfo, ...(array) $insuredPersonData]
                : $customerInfo;

            $amlRequestData = $this->buildAmlRequestData($customer, $idType, $idNumber);

            $quoteTypeId = (int) (QuoteTypes::getId($this->quoteType) ?? 0);
            $quoteAmlProcessCall = app(AMLService::class)->quoteAmlProcessCall($amlRequestData, $quoteTypeId, $this->quoteRequest->id);

            $this->processAmlResult($quoteAmlProcessCall, $amlAutomation, $loggerPrefix, $baseContext);

        } catch (\Exception $e) {
            $amlAutomation->update(['status' => AmlAutomationStatus::Failed->value, 'result' => 'Exception: '.$e->getMessage()]);
            LoggerService::error($loggerPrefix.'Exception: '.$e->getMessage(), extra: array_merge($baseContext, [
                'aml_automation_id' => $amlAutomation->id,
            ]));
        }
    }

    /**
     * Returns an exit reason string if the job should abort, otherwise null.
     *
     * Only two lightweight guards remain here (everything else was checked by
     * the eligibility service before dispatch):
     *
     *   1. CMS flag — can be toggled between dispatch and execution.
     *   2. Queue status — authoritative signal that the caller ran eligibility checks;
     *      also prevents duplicate execution when two dispatches race.
     */
    private function getExitReason(): ?string
    {
        if (! app(ApplicationStorageService::class)->getValueByKey(ApplicationStorageEnums::AML_AUTOMATION_ENABLED)) {
            return 'AML automation is not enabled';
        }

        if (! $this->quoteRequest) {
            return 'Quote not found';
        }

        $this->quoteRequest->refresh();
        LoggerService::startQuoteLogging($this->quoteRequest);

        // The Queue status is the handshake from the eligibility service to the job:
        // it proves the caller validated the quote before dispatch.
        $amlAutomation = $this->quoteRequest->amlAutomation;
        if ($amlAutomation?->status !== AmlAutomationStatus::Queue->value) {
            return 'Automation not in Queue state (status: '.($amlAutomation?->status ?? 'null').')';
        }

        return null;
    }

    private function buildAmlRequestData(array $customer, string $idType, ?string $idNumber): array
    {
        return [
            'customer_id' => $customer['customer_id'],
            'customer_type' => CustomerTypeEnum::Individual,
            'quote_type' => $this->quoteType->value,
            'screening_id_type' => $idType,
            'screening_id_number' => $idNumber,
            'insured_first_name' => $customer['first_name'],
            'insured_last_name' => $customer['last_name'],
            'nationality_id' => $customer['nationality_id'],
            'dob' => $customer['dob'],
            'screening_gender' => $customer['gender'],
        ];
    }

    /**
     * @param  array<string, mixed>  $baseContext  Shared log context (quote_code, quote_type, aml_automation_id).
     */
    private function processAmlResult(object $quoteAmlProcessCall, AmlAutomation $amlAutomation, string $loggerPrefix, array $baseContext): void
    {
        $this->quoteRequest->refresh();

        $ctx = array_merge($baseContext, ['aml_automation_id' => $amlAutomation->id]);

        if (! $quoteAmlProcessCall->status) {
            $result = 'AML process call failed: '.($quoteAmlProcessCall->message ?? 'Unknown');
            $amlAutomation->update(['status' => AmlAutomationStatus::Failed->value, 'result' => $result]);
            LoggerService::info($loggerPrefix.'Completed - AML process call failed', extra: array_merge($ctx, ['result' => $result]));

            return;
        }

        $amlStatus = $this->quoteRequest->aml_status;
        $ctx['aml_status'] = $amlStatus;

        if ($amlStatus === AMLStatusCode::AMLScreeningCleared) {
            $result = (string) ($quoteAmlProcessCall->message ?? 'AML Screening Cleared');
            $amlAutomation->update(['status' => AmlAutomationStatus::Complete->value, 'result' => $result]);
            LoggerService::info($loggerPrefix.'Completed - AML cleared', extra: array_merge($ctx, ['result' => $result]));
            $this->dispatchScreeningSucceededEventIfApplicable();

            return;
        }

        if ($amlStatus === AMLStatusCode::AMLScreeningFailed) {
            $result = 'AML Screening Failed';
            $amlAutomation->update(['status' => AmlAutomationStatus::Failed->value, 'result' => $result]);
            LoggerService::info($loggerPrefix.'Completed - AML failed', extra: array_merge($ctx, ['result' => $result]));

            return;
        }

        $result = 'AML still pending after screening';
        $amlAutomation->update(['status' => AmlAutomationStatus::Failed->value, 'result' => $result]);
        LoggerService::info($loggerPrefix.'Completed - AML still pending after screening', extra: array_merge($ctx, ['result' => $result]));
    }

    private function dispatchScreeningSucceededEventIfApplicable(): void
    {

        if (! AmlAutomatableLobRegistry::isLobAllowedForAmlAutomationScreeningSucceededEvent($this->quoteType)) {
            return;
        }

        event(new AmlAutomationScreeningSucceeded(
            (int) $this->quoteRequest->id,
            $this->quoteRequest->uuid ?? '',
            $this->quoteRefId,
            $this->quoteType,
        ));
    }

    /**
     * Retrieve customer information for the LOB.
     *
     * Supported LOBs: Travel, Cyber, Savings, Device.
     * Returns null (and exits the job early) when no customer record is found.
     *
     * @return array<string, mixed>|null
     */
    private function getCustomerInfo(): ?array
    {
        if ($this->quoteType === QuoteTypes::TRAVEL) {
            $info = (array) app(TravelQuoteService::class)->getCustomerTravelInfo($this->quoteRequest->id, $this->quoteType->value);

            if (! empty($info['id'])) {
                return [
                    'id' => $info['id'],
                    'code' => $info['code'],
                    'customer_id' => $info['customer_id'],
                    'first_name' => $info['first_name'],
                    'last_name' => $info['last_name'],
                    'dob' => $info['dob'],
                    'nationality_id' => $info['nationality_id'],
                    'gender' => $info['gender'],
                    'id_type' => 'passport',
                    'id_number' => $info['passport'] ?? null,
                    'passport' => $info['passport'] ?? null,
                ];
            }

            return null;
        }

        if ($this->quoteType === QuoteTypes::CYBER) {
            $info = (array) app(CyberQuoteService::class)->getCustomerCyberInfo($this->quoteRequest->id, $this->quoteType->value);

            return ! empty($info['id']) ? $info : null;
        }

        // Savings and Device both use PersonalQuote; the shared service resolves both.
        if (
            in_array($this->quoteType, [QuoteTypes::SAVINGS, QuoteTypes::DEVICE], true) &&
            $this->quoteRequest instanceof PersonalQuote
        ) {
            $row = app(PersonalQuoteAmlAutomationCustomerService::class)
                ->getCustomerPersonalQuoteAmlInfo((int) $this->quoteRequest->id, (int) $this->quoteRequest->quote_type_id);

            if ($row !== false && ! empty($row->id)) {
                return array_merge((array) $row, [
                    'id_type' => $row->id_type ?? 'passport',
                ]);
            }

            return null;
        }

        return null;
    }

    /**
     * Verify that the customer record has all fields required for AML screening.
     *
     * @param  array<string, mixed>  $customerInfo
     * @return array{status: bool, message: string}
     */
    private function checkCustomerInfoIsComplete(array $customerInfo): array
    {
        if ($this->quoteType === QuoteTypes::TRAVEL) {
            return app(TravelQuoteService::class)->checkCustomerTravelInfoIsComplete($customerInfo);
        }

        if ($this->quoteType === QuoteTypes::CYBER) {
            return app(CyberQuoteService::class)->checkCustomerCyberInfoIsComplete($customerInfo);
        }

        if (in_array($this->quoteType, [QuoteTypes::SAVINGS, QuoteTypes::DEVICE], true)) {
            return app(PersonalQuoteAmlAutomationCustomerService::class)->checkCustomerPersonalQuoteAmlInfoIsComplete($customerInfo);
        }

        return ['status' => false, 'message' => 'Unknown quote type'];
    }

    public function middleware(): array
    {
        return [
            new WithoutOverlapping('aml-screening-automation-'.$this->uniqueKey),
        ];
    }

    public function uniqueId(): string
    {
        return $this->uniqueKey;
    }
}
