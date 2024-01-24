<?php

namespace App\Jobs;

use App\Enums\ApplicationStorageEnums;
use App\Enums\QuoteDocumentsEnum;
use App\Enums\QuoteTypes;
use App\Models\ApplicationStorage;
use App\Services\QuoteDocumentService;
use App\Services\SendEmailCustomerService;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

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

        $quote = $this->getQuoteObject($this->data->model_type, $this->data->quote_id);
        $quoteDocuments = $quoteDocumentService->getQuoteDocuments($this->data->model_type, $this->data->quote_id);
        $filtered = $quoteDocuments->filter(function ($value, $key) {
            return in_array($value->document_type_code, [QuoteDocumentsEnum::CAR_POLICY_CERTIFICATE, QuoteDocumentsEnum::POLICY_SCHEDULE, QuoteDocumentsEnum::POLICY_HANDBOOK]);
        });

        $docs = $filtered->all();
        info('SendBookPolicyDocumentsJobDocuments ' . json_encode($quoteDocuments));

        $quote->load('advisor');

        $templateId = null;
        switch (ucfirst($this->data->model_type)) {
            case QuoteTypes::CAR->value:
                $templateId = ApplicationStorage::where('key_name', ApplicationStorageEnums::CAR_BOOK_POLICY_TEMPLATE)->first()->value;
                break;

            case QuoteTypes::BIKE->value:
                break;

            default:
                $templateId = null;
                break;
        }
        info('SendBookPolicyDocumentsJobData ' . json_encode($quote));

        if (!empty($templateId)) {

            // payload
            $dataArr = new \stdClass();
            $dataArr->code = $quote->code;
            // $dataArr->customerEmail = 'wasim.abbas@myalfred.com';
            // $dataArr->customerEmail = 'nouman.hussain@myalfred.com';
            $dataArr->customerEmail = $quote->email;
            $dataArr->clientFullName = $quote->first_name . ' ' . $quote->last_name;
            $dataArr->policy_number = $quote->policy_number;
            $dataArr->renewalDueDate = date('Y-m-d', strtotime($quote['renewal_expiry_date']));
            $dataArr->quoteDocuments = $docs;
            $dataArr->advisorName = '';
            $dataArr->advisorEmail = '';
            if (!empty($quote->advisor)) {
                $dataArr->advisorName = $quote->advisor->name;
                $dataArr->advisorEmail = $quote->advisor->email;
            }

            $dataArr->currentInsurer = 'Insurance market';
            $dataArr->emailTemplateId = $templateId;

            info('SendBookPolicyDocumentsJobEmailData ' . json_encode($dataArr));
            $response = $sendEmailCustomerService->sendBookPolicyDocumentsEmail($dataArr, 'book-policy-document');

            info('SendBookPolicyDocumentsJobResponse ' . json_encode($response));
        }
    }

    public function failed(Throwable $exception)
    {
        info('SendBookPolicyDocumentsJob -: ' . $this->data->quote_id . ' Error: ' . $exception->getMessage());
    }
}
