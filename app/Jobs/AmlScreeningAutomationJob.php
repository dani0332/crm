<?php

namespace App\Jobs;

use App\Enums\AmlAutomationStatus;
use App\Enums\AMLStatusCode;
use App\Enums\ApplicationStorageEnums;
use App\Enums\CustomerTypeEnum;
use App\Enums\PolicyIssuanceEnum;
use App\Enums\QuoteTypes;
use App\Models\AmlAutomation;
use App\Models\TravelQuote;
use App\Services\AMLService;
use App\Services\ApplicationStorageService;
use App\Services\Logger\LoggerService;
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
    private TravelQuote $quoteRequest;
    private QuoteTypes $quoteType;
    private string $quoteRefId;

    /**
     * Create a new job instance.
     */
    public function __construct(QuoteTypes $quoteType, TravelQuote $quoteRequest)
    {
        $this->quoteType = $quoteType;
        $this->quoteRequest = $quoteRequest;
        $this->quoteRefId = $this->quoteRequest?->code ?? '';
        $this->uniqueKey = strtolower($this->quoteRefId);
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        $isAmlAutomationEnabled = app(ApplicationStorageService::class)->getValueByKey(ApplicationStorageEnums::AML_AUTOMATION_ENABLED);
        if (! $isAmlAutomationEnabled) {
            LoggerService::info($this->className.' is not enabled from cms');

            return;
        }

        $this->quoteRequest->refresh();

        $isApiIssuanceStatusYes = $this->quoteRequest->api_issuance_status_id == PolicyIssuanceEnum::POLICY_ISSUANCE_API_STATUS_YES_ID;
        $isAMLPending = empty($this->quoteRequest->aml_status) ?: $this->quoteRequest->aml_status == AMLStatusCode::AMLPending;
        $isAutomationInQueue = $this->quoteRequest->amlAutomation?->status == AmlAutomationStatus::QUEUE_STATUS;

        if (! $isApiIssuanceStatusYes || ! $isAMLPending || ! $isAutomationInQueue) {
            return;
        }

        $travelQuoteService = app(TravelQuoteService::class);

        // Get customer required travel info
        $customerTravelInfo = (array) $travelQuoteService->getCustomerTravelInfo($this->quoteRequest->id, $this->quoteType->value);
        if (empty($customerTravelInfo['id'])) {
            return;
        }

        // Check customer required travel info is complete
        $checkCustomerTravelInfo = $travelQuoteService->checkCustomerTravelInfoIsComplete($customerTravelInfo);
        if (! $checkCustomerTravelInfo['status']) {
            LoggerService::error($this->className.' - '.$checkCustomerTravelInfo['message']);

            return;
        }

        $idType = 'passport';
        $idNumber = $customerTravelInfo['passport'] ?? null;

        $amlAutomation = AmlAutomation::updateOrCreate(
            ['code' => $this->quoteRefId],
            ['status' => AmlAutomationStatus::PROCESSING_STATUS]
        );

        try {
            $insuredPersonData = app(AMLService::class)->getInsuredPersonDetails($idType, $idNumber);
            LoggerService::info($this->className.' - getInsuredPersonDetails - response: '.($insuredPersonData ? '200' : '404'));

            $customer = $customerTravelInfo;
            if (! empty($insuredPersonData)) {
                $customer = [...$customerTravelInfo, ...(array) $insuredPersonData];
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
                $amlAutomation->update(['status' => AmlAutomationStatus::FAILED_STATUS, 'result' => 'Error: '.$quoteAmlProcessCall->message]);
                LoggerService::error($this->className.' - Completed - AmlProcessCall - error: '.$quoteAmlProcessCall->message);

                return;
            }

            $this->quoteRequest->refresh();
            $amlAutomation->update(['status' => AmlAutomationStatus::COMPLETE_STATUS, 'result' => $quoteAmlProcessCall->message]);
            LoggerService::info($this->className.' - Completed - AmlProcessCall - response: '.$quoteAmlProcessCall->message);

        } catch (\Exception $e) {
            $amlAutomation->update(['status' => AmlAutomationStatus::FAILED_STATUS, 'result' => 'Exception: '.$e->getMessage()]);
            LoggerService::error($this->className.' - Exception: '.$e->getMessage());
        }
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
