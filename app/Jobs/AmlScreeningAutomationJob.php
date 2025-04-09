<?php

namespace App\Jobs;

use App\Enums\AMLStatusCode;
use App\Enums\CustomerTypeEnum;
use App\Enums\PolicyIssuanceEnum;
use App\Enums\QuoteTypes;
use App\Services\AMLService;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\Request;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;

class AmlScreeningAutomationJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use GenericQueriesAllLobs;

    public $timeout = 60;
    public $tries = 1;
    private $className = 'AmlScreeningAutomationJob';
    private int $quoteRequestId;
    private mixed $quoteRequest;
    private QuoteTypes $quoteType;
    private string $quoteRefId;
    public $uniqueFor = 60 * 15; // 15 minutes
    public $uniqueKey = ''; // 15 minutes

    /**
     * Create a new job instance.
     */
    public function __construct($quoteRequestId, $quoteType)
    {
        $this->quoteRequestId = $quoteRequestId;
        $this->quoteType = $quoteType;
        $this->quoteRefId = $this->quoteType->shortCode().' #'.$this->quoteRequestId;
        $this->uniqueKey = strtolower($this->quoteRefId.'id-'.$this->quoteRequestId);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->quoteRequest = $this->getQuoteObject($this->quoteType->value, $this->quoteRequestId);

        if (! $this->quoteRequest) {
            info('job:'.$this->className.' fn:'.__FUNCTION__.' - Ref-ID: '.$this->quoteRefId.' - quote not found');

            return;
        }
        $this->quoteRefId = $this->quoteRequest->code;

        if ($this->quoteRequest->api_issuance_status_id != PolicyIssuanceEnum::POLICY_ISSUANCE_API_STATUS_YES_ID) {
            info('job:'.$this->className.' fn:'.__FUNCTION__.' - Ref-ID: '.$this->quoteRefId.' - quote is not eligible for AML-Automation, due to api issuance status is not yes');

            return;
        }

        if ($this->quoteRequest->aml_status != AMLStatusCode::AMLPending) {
            info('job:'.$this->className.' fn:'.__FUNCTION__.' - Ref-ID: '.$this->quoteRefId.' - quote is not eligible for AML-Automation, due to aml status is not pending');

            return;
        }

        try {

            $amlService = app(AMLService::class);

            // Get customer required travel info
            $customerTravelInfo = (array) $amlService->getCustomerTravelInfo($this->quoteRequestId, $this->quoteType->value);

            if (empty($customerTravelInfo['id'])) {
                info('job:'.$this->className.' fn:'.__FUNCTION__.' - Ref-ID: '.$this->quoteRefId.' #'.$this->quoteRequestId.' - Record not found');
            }

            // Check customer required travel info is-complete
            $checkCustomerTravelInfo = $amlService->checkCustomerTravelInfoIsComplete($customerTravelInfo);

            if (! isset($checkCustomerTravelInfo['status'])) {
                info('job:'.$this->className.' fn:'.__FUNCTION__.' - Ref-ID: '.$this->quoteRefId.' - '.$checkCustomerTravelInfo['message']);

                return;
            }

            // Get insured person details
            $insuredPersonRequest = new Request([
                'is_automation' => true,
                'id_type' => 'passport',
                'id_number' => $customerTravelInfo['passport'] ?? null,
            ]);

            $amlController = app()->make(\App\Http\Controllers\V2\AMLController::class);
            $insuredPersonResponse = $amlController->getInsuredPersonDetails($insuredPersonRequest);
            $insuredPersonData = json_decode($insuredPersonResponse->content(), true);
            info('job:'.$this->className.' fn:'.__FUNCTION__.' - Ref-ID: '.$this->quoteRefId.' - reqFn: getInsuredPersonDetails'.' - response: '.($insuredPersonData['status'] ? 'success' : 'error'));

            $customer = $customerTravelInfo;
            if ($insuredPersonResponse->status() === 200 && $insuredPersonData['status']) {
                $customer = [...$customerTravelInfo, ...$insuredPersonData['response']];
            }

            // Prepare AML check request data
            $amlRequestData = [
                'is_automation' => true,
                'customer_id' => $customer['customer_id'],
                'customer_type' => CustomerTypeEnum::Individual,
                'quote_type' => $this->quoteType->value,
                'screening_id_type' => $insuredPersonRequest->id_type,
                'screening_id_number' => $insuredPersonRequest->id_number,
                'insured_first_name' => $customer['first_name'],
                'insured_last_name' => $customer['last_name'],
                'nationality_id' => $customer['nationality_id'],
                'dob' => $customer['dob'],
                'screening_gender' => $customer['gender'],
                // 'get_quote_email_gig' => GenericModelTypeEnum::GIG_INSURER_SCREENIN_DEFAULT_EMAIL
            ];

            // Create AML check request object
            $amlCheckRequest = new \App\Http\Requests\AMLCheckRequest($amlRequestData);

            // Force validation to pass
            $amlCheckRequest->setContainer(app())
                ->setRedirector(app()->make(\Illuminate\Routing\Redirector::class));

            // Call the AML quote update method
            $result = $amlController->quoteUpdate($amlCheckRequest, $this->quoteType->id(), $this->quoteRequestId);
            $amlResult = $result instanceof \Illuminate\Http\RedirectResponse ? 'success' : 'error';
            info('job:'.$this->className.' fn:'.__FUNCTION__.' - Ref-ID: '.$this->quoteRefId.' - reqFn: quoteUpdate'.' - response: '.$amlResult);

        } catch (\Exception $e) {
            info('job:'.$this->className.' fn:'.__FUNCTION__.' - Ref-ID: '.$this->quoteRefId.' - AML screening failed'.' - Error: '.$e->getMessage().' - line: '.$e->getLine());
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
