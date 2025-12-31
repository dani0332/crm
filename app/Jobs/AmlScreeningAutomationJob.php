<?php

namespace App\Jobs;

use App\Enums\AmlAutomationStatus;
use App\Enums\AMLStatusCode;
use App\Enums\ApplicationStorageEnums;
use App\Enums\CustomerTypeEnum;
use App\Enums\PolicyIssuanceEnum;
use App\Enums\QuoteTypes;
use App\Models\AmlAutomation;
use App\Models\PersonalQuote;
use App\Models\TravelQuote;
use App\Services\AMLService;
use App\Services\ApplicationStorageService;
use App\Services\Logger\LoggerService;
use App\Services\Quotes\CyberQuoteService;
use App\Services\TravelQuoteService;
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

    /**
     * Create a new job instance.
     */
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
    public function handle()
    {
        $isAmlAutomationEnabled = app(ApplicationStorageService::class)->getValueByKey(ApplicationStorageEnums::AML_AUTOMATION_ENABLED);
        if (! $isAmlAutomationEnabled) {
            LoggerService::info($this->className.' is not enabled from cms. Ref-ID: '.$this->quoteRefId);

            return;
        }

        if (! $this->quoteRequest) {
            LoggerService::info($this->className.' Quote not found');

            return;
        }

        $this->quoteRequest->refresh();
        LoggerService::startQuoteLogging($this->quoteRequest);

        $isApiIssuanceStatusYes = $this->quoteRequest->api_issuance_status_id == PolicyIssuanceEnum::POLICY_ISSUANCE_API_STATUS_YES_ID;
        $isAMLPending = empty($this->quoteRequest->aml_status) ?: $this->quoteRequest->aml_status == AMLStatusCode::AMLPending;

        // Check amlAutomation status based on quote type
        $isAutomationInQueue = false;
        if ($this->quoteType === QuoteTypes::TRAVEL) {
            $isAutomationInQueue = $this->quoteRequest->amlAutomation?->status == AmlAutomationStatus::QUEUE_STATUS;
        } elseif ($this->quoteType === QuoteTypes::CYBER) {
            $amlAutomation = AmlAutomation::where('code', $this->quoteRefId)->first();
            $isAutomationInQueue = $amlAutomation?->status == AmlAutomationStatus::QUEUE_STATUS;
        }

        if (! $isApiIssuanceStatusYes || ! $isAMLPending || ! $isAutomationInQueue) {
            return;
        }

        // Get customer info based on quote type
        $customerInfo = $this->getCustomerInfo();
        if (empty($customerInfo)) {
            LoggerService::info($this->className.' - Ref-ID: '.$this->quoteRefId.' - Customer info not found or incomplete');

            return;
        }

        // Check customer info is complete
        $checkCustomerInfo = $this->checkCustomerInfoIsComplete($customerInfo);
        if (! $checkCustomerInfo['status']) {
            LoggerService::info($this->className.' - Ref-ID: '.$this->quoteRefId.' - '.$checkCustomerInfo['message']);

            return;
        }

        $idType = $customerInfo['id_type'] ?? 'passport';
        $idNumber = $customerInfo['id_number'] ?? null;

        $amlAutomation = AmlAutomation::updateOrCreate(
            ['code' => $this->quoteRefId],
            ['status' => AmlAutomationStatus::PROCESSING_STATUS]
        );

        try {
            $insuredPersonData = app(AMLService::class)->getInsuredPersonDetails($idType, $idNumber);
            LoggerService::info($this->className.' - Ref-ID: '.$this->quoteRefId.' - getInsuredPersonDetails - response: '.($insuredPersonData ? '200' : '404'));

            $customer = $customerInfo;
            if (! empty($insuredPersonData)) {
                $customer = [...$customerInfo, ...(array) $insuredPersonData];
            }

            // Prepare AML check request data
            $amlRequestData = [
                'is_automation' => true,
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

            $quoteAmlProcessCall = app(AMLService::class)->quoteAmlProcessCall($amlRequestData, $this->quoteType->id(), $this->quoteRequest->id);
            if (! $quoteAmlProcessCall->status) {
                $amlAutomation->update(['status' => AmlAutomationStatus::FAILED_STATUS, 'result' => 'Failed: '.$quoteAmlProcessCall->message]);
                LoggerService::info($this->className.' - Ref-ID: '.$this->quoteRefId.' - Completed - AmlProcessCall - failed: '.$quoteAmlProcessCall->message);

                return;
            }

            $this->quoteRequest->refresh();
            $amlAutomation->update(['status' => AmlAutomationStatus::COMPLETE_STATUS, 'result' => $quoteAmlProcessCall->message]);
            LoggerService::info($this->className.' - Ref-ID: '.$this->quoteRefId.' - Completed - AmlProcessCall - response: '.$quoteAmlProcessCall->message);

        } catch (\Exception $e) {
            $amlAutomation->update(['status' => AmlAutomationStatus::FAILED_STATUS, 'result' => 'Exception: '.$e->getMessage()]);
            LoggerService::error($this->className.' - Ref-ID: '.$this->quoteRefId.' - Exception: '.$e->getMessage());
        }
    }

    /**
     * Get customer information based on quote type.
     */
    private function getCustomerInfo(): ?array
    {
        if ($this->quoteType === QuoteTypes::TRAVEL) {
            $travelQuoteService = app(TravelQuoteService::class);
            $customerTravelInfo = (array) $travelQuoteService->getCustomerTravelInfo($this->quoteRequest->id, $this->quoteType->value);

            if (empty($customerTravelInfo['id'])) {
                return null;
            }

            return [
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
        } elseif ($this->quoteType === QuoteTypes::CYBER) {
            $cyberQuoteService = app(CyberQuoteService::class);
            $customerCyberInfo = (array) $cyberQuoteService->getCustomerCyberInfo($this->quoteRequest->id, $this->quoteType->value);

            if (empty($customerCyberInfo['id'])) {
                return null;
            }

            return $customerCyberInfo;
        }

        return null;
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
