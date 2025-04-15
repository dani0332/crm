<?php

namespace App\Jobs;

use App\Enums\AmlAutomationStatus;
use App\Enums\CustomerTypeEnum;
use App\Enums\QuoteTypes;
use App\Models\AmlAutomation;
use App\Models\TravelQuote;
use App\Services\AMLService;
use App\Services\Logger\LoggerService;
use App\Services\TravelQuoteService;
use App\Traits\GenericQueriesAllLobs;
use Error;
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
    private $className = 'Aml Screening Automation Job';
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
    public function handle(): void
    {
        LoggerService::info($this->className.' - Started');

        try {
            $amlAutomation = AmlAutomation::updateOrCreate(
                ['code' => $this->quoteRefId],
                ['status' => AmlAutomationStatus::PROCESSING_STATUS]
            );

            $travelQuoteService = app(TravelQuoteService::class);

            // Get customer required travel info
            $customerTravelInfo = (array) $travelQuoteService->getCustomerTravelInfo($this->quoteRequest->id, $this->quoteType->value);

            if (empty($customerTravelInfo['id'])) {
                throw new Error('Record not found');
            }

            // Check customer required travel info is complete
            $checkCustomerTravelInfo = $travelQuoteService->checkCustomerTravelInfoIsComplete($customerTravelInfo);

            if (! $checkCustomerTravelInfo['status']) {
                throw new Error($checkCustomerTravelInfo['message']);
            }

            $idType = 'passport';
            $idNumber = $customerTravelInfo['passport'] ?? null;

            $amlService = app(AMLService::class);
            $insuredPersonData = $amlService->getInsuredPersonDetails($idType, $idNumber);
            LoggerService::info($this->className.' - reqCall: getInsuredPersonDetails - response: '.($insuredPersonData->status ? 'success' : 'error'));

            $customer = $customerTravelInfo;
            if ($insuredPersonData->status) {
                $customer = [...$customerTravelInfo, ...(array) $insuredPersonData->response];
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

            $quoteAmlProcessCall = $amlService->quoteAmlProcessCall($amlRequestData, $this->quoteType->id(), $this->quoteRequest->id);
            if (! $quoteAmlProcessCall->status) {
                throw new Error($quoteAmlProcessCall->message);
            }

            $this->quoteRequest->refresh();
            $amlAutomation->update(['status' => AmlAutomationStatus::COMPLETE_STATUS, 'result' => $quoteAmlProcessCall->message]);
            LoggerService::info($this->className.' - Completed - reqCall: quoteUpdate - response: '.$quoteAmlProcessCall->message);

        } catch (Error $e) {
            $amlAutomation->update(['status' => AmlAutomationStatus::FAILED_STATUS, 'result' => 'Error: '.$e->getMessage()]);
            LoggerService::error($this->className.' - Error: '.$e->getMessage());

        } catch (\Exception $e) {
            $amlAutomation->update(['status' => AmlAutomationStatus::FAILED_STATUS, 'result' => 'Exception: '.$e->getMessage()]);
            LoggerService::critical($this->className.' - Exception: '.$e->getMessage());

        } catch (\Throwable $e) { // Catch all other errors and exceptions
            $amlAutomation->update(['status' => AmlAutomationStatus::FAILED_STATUS, 'result' => 'Throwable: '.$e->getMessage()]);
            LoggerService::critical($this->className.' - Throwable: '.$e->getMessage());
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
