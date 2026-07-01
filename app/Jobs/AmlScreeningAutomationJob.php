<?php

namespace App\Jobs;

use App\Enums\AmlAutomationStatus;
use App\Enums\AMLStatusCode;
use App\Enums\ApplicationStorageEnums;
use App\Enums\CustomerTypeEnum;
use App\Enums\PolicyIssuanceEnum;
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
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;

class AmlScreeningAutomationJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use GenericQueriesAllLobs;

    public $timeout = 60;
    public $tries = 1;
    public $uniqueFor = 60 * 15; // 15 minutes
    public $uniqueKey = ''; // 15 minutes
    private $className = 'AmlScreeningAutomationJob';
    private TravelQuote|PersonalQuote $quoteRequest;
    private QuoteTypes $quoteType;
    private string $quoteRefId;
    private bool $isFromAPI;

    /**
     * Create a new job instance.
     */
    public function __construct(QuoteTypes $quoteType, TravelQuote|PersonalQuote $quoteRequest, bool $isFromAPI = false)
    {
        $this->quoteType = $quoteType;
        $this->quoteRequest = $quoteRequest;
        $this->quoteRefId = $this->quoteRequest?->code ?? '';
        $this->uniqueKey = strtolower($this->quoteRefId).'-'.strtolower($quoteType->value);
        $this->isFromAPI = $isFromAPI;
    }

    /**
     * Execute the job.
     *
     * Refactored to reduce Cognitive Complexity as per SonarQube guidelines.
     */
    public function handle()
    {
        $exitReason = $this->getExitReason();
        $loggerPrefix = $this->className.' - Ref-ID: '.$this->quoteRefId.' - ';

        if ($exitReason !== null) {
            LoggerService::info($loggerPrefix.' Exiting job with reason: '.$exitReason);

            return;
        }

        $customerInfo = $this->getCustomerInfo();
        if (empty($customerInfo)) {
            $this->logAndReturn($loggerPrefix, 'Customer info not found or incomplete');

            return;
        }

        $checkCustomerInfo = $this->checkCustomerInfoIsComplete($customerInfo);
        if (! $checkCustomerInfo['status']) {
            $msg = $checkCustomerInfo['message'] ?? 'Customer info incomplete';
            $this->logAndReturn($loggerPrefix, $msg, $msg);

            return;
        }

        $idType = $customerInfo['id_type'] ?? 'passport';
        $idNumber = $customerInfo['id_number'] ?? null;

        $amlAutomation = AmlAutomation::updateOrCreate(
            ['code' => $this->quoteRefId],
            ['status' => AmlAutomationStatus::Processing->value]
        );

        try {
            $insuredPersonData = app(AMLService::class)->getInsuredPersonDetails($idType, $idNumber);
            LoggerService::info($loggerPrefix.' getInsuredPersonDetails - response: '.($insuredPersonData ? '200' : '404'));

            $customer = ! empty($insuredPersonData)
                ? [...$customerInfo, ...(array) $insuredPersonData]
                : $customerInfo;

            $amlRequestData = $this->buildAmlRequestData($customer, $idType, $idNumber);

            $quoteTypeId = (int) (QuoteTypes::getId($this->quoteType) ?? 0);
            $quoteAmlProcessCall = app(AMLService::class)->quoteAmlProcessCall($amlRequestData, $quoteTypeId, $this->quoteRequest->id, $this->isFromAPI);

            $this->processAmlResult($quoteAmlProcessCall, $amlAutomation, $loggerPrefix);

        } catch (\Exception $e) {
            $amlAutomation->update(['status' => AmlAutomationStatus::Failed->value, 'result' => 'Exception: '.$e->getMessage()]);
            LoggerService::error($loggerPrefix.' Exception: '.$e->getMessage());
        }
    }

    /**
     * Determines if early exit is needed and returns exit reason if so, otherwise null.
     * Limits returns to a maximum of 3.
     */
    private function getExitReason(): ?string
    {
        $exitReason = null;

        if (! $this->isAmlAutomationEnabled()) {
            $exitReason = 'AML Automation is not enabled';
        } elseif (! $this->quoteRequest) {
            LoggerService::info($this->className.' Quote not found');
            $exitReason = 'Quote not found';
        } else {
            $this->quoteRequest->refresh();
            LoggerService::startQuoteLogging($this->quoteRequest);

            if (! $this->preconditionsMet() && ! $this->isFromAPI) {
                $exitReason = 'Preconditions not met';
            }
        }

        return $exitReason;
    }

    /**
     * Helper to log customer info errors and exit.
     */
    private function logAndReturn(string $loggerPrefix, string $infoMessage, ?string $exitReason = null): void
    {
        LoggerService::info($loggerPrefix.' '.$infoMessage);
        LoggerService::info($loggerPrefix.' Exiting job with reason: '.($exitReason ?? $infoMessage));
    }

    private function isAmlAutomationEnabled(): bool
    {
        $enabled = app(ApplicationStorageService::class)->getValueByKey(ApplicationStorageEnums::AML_AUTOMATION_ENABLED);
        if (! $enabled) {
            LoggerService::info($this->className.' is not enabled from cms. Ref-ID: '.$this->quoteRefId);
        }

        return (bool) $enabled;
    }

    private function preconditionsMet(): bool
    {
        $this->quoteRequest->loadMissing('insuranceProvider');

        $skipApiIssuanceYes = AmlAutomatableLobRegistry::skipsApiIssuanceStatusCheckForAutomatedAml($this->quoteType, $this->quoteRequest);
        $isApiIssuanceStatusYes = $skipApiIssuanceYes
            || $this->quoteRequest->api_issuance_status_id == PolicyIssuanceEnum::POLICY_ISSUANCE_API_STATUS_YES_ID;
        $isAMLPending = empty($this->quoteRequest->aml_status) ?: $this->quoteRequest->aml_status == AMLStatusCode::AMLPending;

        $isAutomationInQueue = false;
        if ($this->quoteType === QuoteTypes::TRAVEL) {
            $isAutomationInQueue = $this->quoteRequest->amlAutomation?->status === AmlAutomationStatus::Queue->value;
        } elseif ($this->quoteRequest instanceof PersonalQuote && in_array($this->quoteType, [QuoteTypes::CYBER, QuoteTypes::SAVINGS], true)) {
            $isAutomationInQueue = $this->quoteRequest->amlAutomation?->status === AmlAutomationStatus::Queue->value;
        }

        return $isApiIssuanceStatusYes && $isAMLPending && $isAutomationInQueue;
    }

    private function buildAmlRequestData($customer, $idType, $idNumber): array
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

    private function processAmlResult($quoteAmlProcessCall, $amlAutomation, string $loggerPrefix): void
    {
        $this->quoteRequest->refresh();

        if (! $quoteAmlProcessCall->status) {
            $message = 'Failed: '.($quoteAmlProcessCall->message ?? 'Unknown');
            $amlAutomation->update(['status' => AmlAutomationStatus::Failed->value, 'result' => $message]);
            LoggerService::info($loggerPrefix.' Completed - AmlProcessCall - failed: '.$message);

            return;
        }

        $amlStatus = $this->quoteRequest->aml_status;
        if ($amlStatus === AMLStatusCode::AMLScreeningCleared) {
            $amlAutomation->update(['status' => AmlAutomationStatus::Complete->value, 'result' => (string) ($quoteAmlProcessCall->message ?? 'AML Screening Cleared')]);
            LoggerService::info($loggerPrefix.' Completed - AML cleared');
            $this->dispatchScreeningSucceededEventIfApplicable();

            return;
        }

        if ($amlStatus === AMLStatusCode::AMLScreeningFailed) {
            $msg = 'AML Screening Failed';
            $amlAutomation->update(['status' => AmlAutomationStatus::Failed->value, 'result' => $msg]);
            LoggerService::info($loggerPrefix.' Completed - AML failed');

            return;
        }

        $pendingMsg = 'AML still pending after screening';
        $amlAutomation->update(['status' => AmlAutomationStatus::Failed->value, 'result' => $pendingMsg]);
        LoggerService::info($loggerPrefix.' Completed - '.$pendingMsg);
    }

    private function dispatchScreeningSucceededEventIfApplicable(): void
    {
        if (! AmlAutomatableLobRegistry::allows($this->quoteType)) {
            return;
        }
        $uuid = $this->quoteRequest->uuid ?? '';
        $id = (int) $this->quoteRequest->id;
        event(new AmlAutomationScreeningSucceeded(
            $id,
            $uuid,
            $this->quoteRefId,
            $this->quoteType,
        ));
    }

    /**
     * Get customer information based on quote type.
     */
    private function getCustomerInfo(): ?array
    {
        $customerInfo = null;

        if ($this->quoteType === QuoteTypes::TRAVEL) {
            $travelQuoteService = app(TravelQuoteService::class);
            $customerTravelInfo = (array) $travelQuoteService->getCustomerTravelInfo($this->quoteRequest->id, $this->quoteType->value);

            if (! empty($customerTravelInfo['id'])) {
                $customerInfo = [
                    'id' => $customerTravelInfo['id'],
                    'code' => $customerTravelInfo['code'],
                    'customer_id' => $customerTravelInfo['customer_id'],
                    'first_name' => $customerTravelInfo['first_name'],
                    'last_name' => $customerTravelInfo['last_name'],
                    'dob' => $customerTravelInfo['dob'],
                    'nationality_id' => $customerTravelInfo['nationality_id'],
                    'gender' => $customerTravelInfo['gender'],
                    'id_type' => 'passport',
                    'id_number' => $customerTravelInfo['passport'] ?? null,
                ];
            }
        } elseif ($this->quoteType === QuoteTypes::CYBER) {
            $cyberQuoteService = app(CyberQuoteService::class);
            $customerCyberInfo = (array) $cyberQuoteService->getCustomerCyberInfo($this->quoteRequest->id, $this->quoteType->value);

            if (! empty($customerCyberInfo['id'])) {
                $customerInfo = $customerCyberInfo;
            }
        } elseif ($this->quoteType === QuoteTypes::SAVINGS && $this->quoteRequest instanceof PersonalQuote) {
            $row = app(PersonalQuoteAmlAutomationCustomerService::class)
                ->getCustomerPersonalQuoteAmlInfo((int) $this->quoteRequest->id, (int) $this->quoteRequest->quote_type_id);
            if ($row !== false && ! empty($row->id)) {
                $customerInfo = array_merge((array) $row, [
                    'id_type' => $row->id_type ?? 'passport',
                ]);
            }
        }

        return $customerInfo;
    }

    /**
     * Check if customer information is complete.
     */
    private function checkCustomerInfoIsComplete(array $customerInfo): array
    {
        if ($this->quoteType === QuoteTypes::TRAVEL) {
            $travelQuoteService = app(TravelQuoteService::class);

            return $travelQuoteService->checkCustomerTravelInfoIsComplete($customerInfo);
        } elseif ($this->quoteType === QuoteTypes::CYBER) {
            $cyberQuoteService = app(CyberQuoteService::class);

            return $cyberQuoteService->checkCustomerCyberInfoIsComplete($customerInfo);
        } elseif ($this->quoteType === QuoteTypes::SAVINGS) {
            return app(PersonalQuoteAmlAutomationCustomerService::class)->checkCustomerPersonalQuoteAmlInfoIsComplete($customerInfo);
        }

        return ['status' => false, 'message' => 'Unknown quote type'];
    }

    public function middleware()
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
