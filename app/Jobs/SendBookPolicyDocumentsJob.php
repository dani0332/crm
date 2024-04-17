<?php

namespace App\Jobs;

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
use Illuminate\Queue\SerializesModels;
use Throwable;

use function Laravel\Prompts\error;

class SendBookPolicyDocumentsJob implements ShouldQueue
{
    use Dispatchable, GenericQueriesAllLobs, InteractsWithQueue, Queueable, SerializesModels;

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
        $modelType = !empty($this->data->modelType) ? $this->data->modelType : $this->data->model_type;

        $quote = $this->getQuoteObject($this->data->model_type, $this->data->quote_id);

        try {
            $documentTypeCodes = DocumentTypeRepository::quoteDocumentsSentToCustomerCode($this->data->model_type);
            $quoteDocuments = $docs = app(QuoteDocumentService::class)->getQuoteDocuments($this->data->model_type, $this->data->quote_id, $documentTypeCodes);
        } catch (Exception $ex) {
            error('SendBookPolicyDocumentsJobError '.$ex->getMessage());
            $docs = [];
        }

        $quote->load('advisor');

        $templateId = ApplicationStorage::where('key_name', strtoupper(str_replace(' ', '_', $modelType)).'_BOOK_POLICY_TEMPLATE')->first()->value ?? null;

        info('SendBookPolicyDocumentsJobData '.json_encode($quote));

        if (! empty($templateId)) {

            // payload
            $dataArr = new \stdClass();
            $dataArr->code = $quote->code;
            // $dataArr->customerEmail = 'muhammad.waris@myalfred.com';
            // $dataArr->customerEmail = 'nouman.hussain@myalfred.com';
            $dataArr->customerEmail = $quote->email;
            $dataArr->clientFullName = $quote->first_name.' '.$quote->last_name;
            $dataArr->policy_number = $quote->policy_number;
            $dataArr->renewalDueDate = date('Y-m-d', strtotime($quote['renewal_expiry_date']));
            $dataArr->quoteDocuments = $docs;
            $dataArr->advisorName = '';
            $dataArr->advisorEmail = '';
            if (! empty($quote->advisor)) {
                $dataArr->advisorName = $quote->advisor->name;
                $dataArr->advisorEmail = $quote->advisor->email;
            }

            $dataArr->currentInsurer = 'Insurance market';
            $dataArr->emailTemplateId = $templateId;

            info('SendBookPolicyDocumentsJobEmailData '.json_encode($dataArr));
            $response = $sendEmailCustomerService->sendBookPolicyDocumentsEmail($dataArr, 'book-policy-document');

            info('SendBookPolicyDocumentsJobResponse '.json_encode($response));
        }
    }

    public function failed(Throwable $exception)
    {
        info('SendBookPolicyDocumentsJob -: '.$this->data->quote_id.' Error: '.$exception->getMessage());
    }
}
