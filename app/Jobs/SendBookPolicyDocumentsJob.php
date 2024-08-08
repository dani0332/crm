<?php

namespace App\Jobs;

use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Models\ApplicationStorage;
use App\Repositories\DocumentTypeRepository;
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

    /**
     * Create a new job instance.
     */
    private $data = null;
    public function __construct($payload)
    {
        $this->data = $payload;
    }

    /**
     * Execute the job.
     */
    public function handle(SendEmailCustomerService $sendEmailCustomerService, QuoteDocumentService $quoteDocumentService)
    {
        info('job: SendBookPolicyDocumentsJob started');

        // In case of Group Medical & Corpline, modelType is used & for rest of the LOBs model_type is used
        // Basically we are different to identify the template which will send to customer after policy booking
        $modelType = ! empty($this->data->modelType) ? $this->data->modelType : $this->data->model_type;

        $quote = $this->getQuoteObject($this->data->model_type, $this->data->quote_id);
        $handBookDocuments = [];

        try {
            // This will give handbook document from relevant policy wording table only for mentioned LOB's
            if (in_array($modelType, [quoteTypeCode::Car, quoteTypeCode::Travel, quoteTypeCode::Health])) {
                $handBookDocuments = app(QuoteDocumentService::class)->getHandBookDocuments($quote);
                info('Handbook documents retrieved: '.$quote->uuid.' : '.json_encode($handBookDocuments));
            }
            // First Retrieve document types marked for sending to the customer, then fetch the corresponding uploaded documents
            $documentTypeCodes = DocumentTypeRepository::quoteDocumentsSentToCustomerCode($this->data->model_type, $quote);
            $docs = app(QuoteDocumentService::class)->getQuoteDocuments($this->data->model_type, $this->data->quote_id, $documentTypeCodes);
            info('Quote documents which need to send to customer through email retrieved: '.json_encode($docs));
        } catch (Exception $ex) {
            Log::error('Send BookPolicy Documents Job Error '.$quote->uuid.' : '.$ex->getMessage());
            $docs = [];
        }

        $quote->load('advisor');

        $templateId = ApplicationStorage::where('key_name', strtoupper(str_replace(' ', '_', $modelType)).'_BOOK_POLICY_TEMPLATE')->first()->value ?? null;
        // Prepare the data to be sent to Brevo for email template dispatch
        if (! empty($templateId)) {
            // TODO:  Hard coded format and variable values should be form env file
            $roadsideAssistance = '';
            $emailData = new \stdClass();
            $emailData->code = $quote->code;
            $emailData->customerEmail = $quote->email;
            $emailData->clientFullName = $quote->first_name.' '.$quote->last_name;
            $emailData->policy_number = $quote->policy_number;
            $emailData->renewalDueDate = date('d/m/Y', strtotime($quote['renewal_expiry_date']));
            $emailData->policyStartDate = date('d/m/Y', strtotime($quote['policy_start_date']));
            $emailData->quoteDocuments = $docs;
            $emailData->advisorName = '';
            $emailData->advisorEmail = '';
            $emailData->advisorMobileNo = '';
            $emailData->advisorLandlineNo = '';
            $emailData->googleMeet = '';
            if (! empty($quote->advisor)) {
                $emailData->advisorName = $quote->advisor->name;
                $emailData->advisorEmail = $quote->advisor->email;
                $advisorMobileNo = formatMobileNo($quote->advisor->mobile_no);
                $emailData->advisorMobileNo = str_replace('+', '', $advisorMobileNo);
                $emailData->advisorLandlineNo = $quote->advisor->landline_no;
                $emailData->googleMeet = $quote->advisor->calendar_link;
            }
            if (in_array(ucfirst($this->data->model_type), [quoteTypeCode::Car, quoteTypeCode::Health, quoteTypeCode::Travel])) {
                $emailData->currentInsurer = $quote->plan->insuranceProvider->text;
                $roadsideAssistance = $quote->plan->insuranceProvider->roadside_phone_number;
            } else {
                $emailData->currentInsurer = $quote->insuranceProvider->text;
                $roadsideAssistance = $quote->insuranceProvider->roadside_phone_number;
            }

            $emailData->emailTemplateId = $templateId;
            $emailData->handBookDocuments = $handBookDocuments;
            $emailData->roadsideAssistance = $roadsideAssistance;
            $emailData->appDownloadLink = app(QuoteDocumentService::class)->getAppDownloadLink($modelType, $quote);
            info('Send Book Policy Documents Job Email Data '.$quote->uuid.' : '.json_encode($emailData));
            $response = $sendEmailCustomerService->sendBookPolicyDocumentsEmail($emailData, 'book-policy-document');
            info('Send Book Policy Documents Job Response '.$quote->uuid.' : '.json_encode($response));
        }

        $quote->update([
            'quote_status_id' => QuoteStatusEnum::PolicySentToCustomer,
        ]);
    }

    public function failed(Throwable $exception)
    {
        info('SendBookPolicyDocumentsJob -: '.$this->data->quote_id.' Error: '.$exception->getMessage());
    }

    public function middleware()
    {
        return [(new WithoutOverlapping($this->data->quote_id))->dontRelease()];
    }
}
