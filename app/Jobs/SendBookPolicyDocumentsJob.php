<?php

namespace App\Jobs;

use App\Enums\PolicyIssuanceEnum;
use App\Enums\QuoteTagEnums;
use App\Enums\quoteTypeCode;
use App\Models\ApplicationStorage;
use App\Models\HealthPlanCoPayment;
use App\Models\QuoteTag;
use App\Repositories\DocumentTypeRepository;
use App\Services\ActivitiesService;
use App\Services\QuoteDocumentService;
use App\Services\SendEmailCustomerService;
use App\Traits\GenericQueriesAllLobs;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendBookPolicyDocumentsJob implements ShouldQueue
{
    use Dispatchable, GenericQueriesAllLobs, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 100;
    public $tries = 3;
    public $backoff = 120;

    /**
     * Create a new job instance.
     */
    private $data = null;

    private $code = null;

    public function __construct($payload, $code)
    {
        info('Quote Code: '.$code.' job: SendBookPolicyDocumentsJob constructor called ');
        $this->data = $payload;
        $this->code = $code;
        $this->onQueue('insly');
    }

    /**
     * Execute the job.
     */
    public function handle(SendEmailCustomerService $sendEmailCustomerService, QuoteDocumentService $quoteDocumentService)
    {
        info('Quote Code: '.$this->code.' job: SendBookPolicyDocumentsJob started');
        $insuranceType = '';
        $planName = '';
        // In case of Group Medical & Corpline, modelType is used & for rest of the LOBs model_type is used
        // Basically we are different to identify the template which will send to customer after policy booking
        $modelType = ucfirst(! empty($this->data->modelType) ? $this->data->modelType : $this->data->model_type);
        $quoteTypeId = app(ActivitiesService::class)->getQuoteTypeId(strtolower($this->data->model_type));

        $quote = $this->getQuoteObject($this->data->model_type, $this->data->quote_id);

        info('job: SendBookPolicyDocumentsJob Code: '.$quote->code.' , Quote Type: '.$this->data->model_type.', Type Id: '.$quoteTypeId);

        $isDocumentEmailSentToCustomer = QuoteTag::where([
            'quote_type_id' => $quoteTypeId,
            'quote_uuid' => $quote->uuid,
            'name' => QuoteTagEnums::POLICY_SENT_TO_CUSTOMER,
            'value' => 1,
        ])->first();

        if ($isDocumentEmailSentToCustomer) {
            info('job: SendBookPolicyDocumentsJob skipped for: '.$quote->code.' as email already sent');

            return;
        }

        $handBookDocuments = [];

        try {
            // This will give handbook document from relevant policy wording table only for mentioned LOB's
            if (in_array($modelType, [quoteTypeCode::Car, quoteTypeCode::Travel, quoteTypeCode::Health])) {
                $coPaymentIds = null;
                if ($modelType == quoteTypeCode::Health) {
                    $coPaymentIds = HealthPlanCoPayment::where('health_plan_id', $quote->plan_id)->where('id', '!=', $quote->health_plan_co_payment_id)->pluck('id')->toArray();
                }
                $handBookDocuments = app(QuoteDocumentService::class)->getHandBookDocuments($quote, $coPaymentIds);
            }
            // First Retrieve document types marked for sending to the customer, then fetch the corresponding uploaded documents
            $documentTypeCodes = DocumentTypeRepository::quoteDocumentsSentToCustomerCode($this->data->model_type, $quote);
            $docs = app(QuoteDocumentService::class)->getQuoteDocuments($this->data->model_type, $this->data->quote_id, $documentTypeCodes);
        } catch (Exception $ex) {
            Log::error('Send BookPolicy Documents Job Error for:  '.$quote->code.' '.$ex->getMessage());
            $docs = [];
        }

        if (strtolower($modelType) == strtolower(quoteTypeCode::CORPLINE)) {
            $quote->load('businessTypeOfInsurance');
            $insuranceType = $quote->businessTypeOfInsurance->text;
        }
        if ($modelType == quoteTypeCode::Health) {
            $planName = $quote->plan->text;
        }

        $quote->load('advisor');

        $templateId = ApplicationStorage::where('key_name', strtoupper(str_replace(' ', '_', $modelType)).'_BOOK_POLICY_TEMPLATE')->first()->value ?? null;
        // Prepare the data to be sent to Brevo for email template dispatch
        if (! empty($templateId)) {
            // TODO:  Hard coded format and variable values should be form env file
            $roadsideAssistance = '';
            $emailData = new \stdClass;
            $emailData->code = $quote->code;
            $emailData->customerEmail = $quote->email;
            $emailData->clientFullName = $quote->first_name.' '.$quote->last_name;
            $emailData->clientFirstName = $quote->first_name;
            $emailData->policy_number = $quote->policy_number;
            $emailData->renewalDueDate = date('d/m/Y', strtotime($quote['policy_expiry_date']));
            $emailData->policyStartDate = date('d/m/Y', strtotime($quote['policy_start_date']));
            $emailData->quoteDocuments = $docs;
            $emailData->advisorName = '';
            $emailData->advisorEmail = '';
            $emailData->advisorMobileNo = '';
            $emailData->advisorLandlineNo = '';
            $emailData->googleMeet = '';
            $emailData->insuranceType = $insuranceType;
            $emailData->planName = $planName;
            $emailData->currentInsurer = '';
            $emailData->profilePicture = '';
            $emailData->isChsAdvisor = false;
            if (! empty($quote->advisor)) {
                $emailData->advisorName = $quote->advisor->name;
                $emailData->advisorEmail = $quote->advisor->email;
                $advisorMobileNo = formatMobileNo($quote->advisor->mobile_no);
                $emailData->advisorMobileNo = str_replace('+', '', $advisorMobileNo);
                $emailData->advisorLandlineNo = $quote->advisor->landline_no;
                $emailData->googleMeet = $quote->advisor->calendar_link;
                $emailData->profilePicture = $quote->advisor->profile_photo_path;
                if ($emailData->advisorEmail === PolicyIssuanceEnum::API_POLICY_ISSUANCE_AUTOMATION_USER_EMAIL) {
                    $emailData->isChsAdvisor = true;
                }
            }
            if (in_array(ucfirst($this->data->model_type), [quoteTypeCode::Car, quoteTypeCode::Health, quoteTypeCode::Travel])) {
                if (isset($quote->plan) && isset($quote->plan->insuranceProvider)) {
                    $emailData->currentInsurer = $quote->plan->insuranceProvider->text;
                    $roadsideAssistance = $quote->plan->insuranceProvider->roadside_phone_number;
                }
            } else {
                if (isset($quote->insuranceProvider)) {
                    $emailData->currentInsurer = $quote->insuranceProvider->text;
                    $roadsideAssistance = $quote->insuranceProvider->roadside_phone_number;
                }
            }

            $emailData->emailTemplateId = $templateId;
            $emailData->handBookDocuments = $handBookDocuments;
            $emailData->roadsideAssistance = $roadsideAssistance;
            $emailData->appDownloadLink = app(QuoteDocumentService::class)->getAppDownloadLink($modelType, $quote);
            $response = $sendEmailCustomerService->sendBookPolicyDocumentsEmail($emailData, 'book-policy-document');
            info('Quote Code: '.$quote->code.' Send Book Policy Documents Job Response '.$quote->uuid.' : '.json_encode($response));
        }

        $quoteTag = QuoteTag::create([
            'quote_type_id' => $quoteTypeId,
            'quote_uuid' => $quote->uuid,
            'name' => QuoteTagEnums::POLICY_SENT_TO_CUSTOMER,
            'value' => 1,
        ]);

        info('job: SendBookPolicyDocumentsJob Code: '.$quote->code.' , Quote Tag id: '.$quoteTag->id);
    }

    public function failed(Throwable $exception)
    {
        info('Quote Code: '.$this->code.' SendBookPolicyDocumentsJob Error: '.$exception->getMessage());
    }

    public function middleware()
    {
        return [(new WithoutOverlapping($this->code))->dontRelease()];
    }
}
