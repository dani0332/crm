<?php

namespace App\Jobs;

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
use Throwable;

use function Laravel\Prompts\error;

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
        // In case of Group Medical & Corpline, modelType is used & for rest of the LOBs model_type is used
        // Basically we are different to identify the template which will send to customer after policy booking
        $modelType = ! empty($this->data->modelType) ? $this->data->modelType : $this->data->model_type;

        $quote = $this->getQuoteObject($this->data->model_type, $this->data->quote_id);

        try {
            $documentTypeCodes = DocumentTypeRepository::quoteDocumentsSentToCustomerCode($this->data->model_type, $quote);
            $quoteDocuments = $docs = app(QuoteDocumentService::class)->getQuoteDocuments($this->data->model_type, $this->data->quote_id, $documentTypeCodes);
        } catch (Exception $ex) {
            error('SendBookPolicyDocumentsJobError '.$ex->getMessage());
            $docs = [];
        }

        $quote->load('advisor');

        $templateId = ApplicationStorage::where('key_name', strtoupper(str_replace(' ', '_', $modelType)).'_BOOK_POLICY_TEMPLATE')->first()->value ?? null;

        info('SendBookPolicyDocumentsJobData '.json_encode($quote));

        if (! empty($templateId)) {
            $emailData = new \stdClass();
            $emailData->code = $quote->code;
            $emailData->customerEmail = $quote->email;
            $emailData->clientFullName = $quote->first_name.' '.$quote->last_name;
            $emailData->policy_number = $quote->policy_number;
            $emailData->renewalDueDate = date('Y-m-d', strtotime($quote['renewal_expiry_date']));
            $emailData->quoteDocuments = $docs;
            $emailData->advisorName = '';
            $emailData->advisorEmail = '';
            if (! empty($quote->advisor)) {
                $emailData->advisorName = $quote->advisor->name;
                $emailData->advisorEmail = $quote->advisor->email;
                $emailData->mobileNo = $quote->advisor->mobile_no;
            }
            if (in_array(ucfirst($this->data->model_type), [quoteTypeCode::Car, quoteTypeCode::Health, quoteTypeCode::Travel])) {
                $emailData->currentInsurer = $quote->plan->insuranceProvider->text ?? '';
            } else {
                $emailData->currentInsurer = $quote->insuranceProvider->text ?? '';
            }
            $emailData->emailTemplateId = $templateId;
            info('SendBookPolicyDocumentsJobEmailData '.json_encode($emailData));
            $response = $sendEmailCustomerService->sendBookPolicyDocumentsEmail($emailData, 'book-policy-document');
            info('SendBookPolicyDocumentsJobResponse '.json_encode($response));
        }
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
