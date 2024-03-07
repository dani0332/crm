<?php

namespace App\Jobs;

use App\Enums\ApplicationStorageEnums;
use App\Enums\QuoteDocumentsEnum;
use App\Enums\QuoteTypes;
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
        $modelType= $this->data->model_type;
        $quote = $this->getQuoteObject($this->data->model_type, $this->data->quote_id);

        try{
            $document_type_codes= app(DocumentTypeRepository::class)->getQuoteDocumentsSentToCustomerCode($this->data->model_type);
            $quoteDocuments = app(QuoteDocumentService::class)->getQuoteDocuments($this->data->model_type, $this->data->quote_id);
            $docs = $quoteDocuments->whereIn('document_type_code', $document_type_codes);
        } catch(Exception $ex) {
            info('SendBookPolicyDocumentsJobError ' . $ex->getMessage());
            $docs= [];
        }

        info('SendBookPolicyDocumentsJobDocuments ' . json_encode($quoteDocuments));

        $quote->load('advisor');

        $templateId = null;

        if($modelType === 'business'){
            $modelType = QuoteTypes::GROUP_MEDICAL->value;
        }else if($modelType === 'Business') {
            $modelType = QuoteTypes::CORPLINE->value;
        }

        switch (ucfirst($modelType)) {
           case QuoteTypes::CAR->value:
               $templateId = ApplicationStorage::where('key_name', ApplicationStorageEnums::CAR_BOOK_POLICY_TEMPLATE)->first()->value;
               break;

            case QuoteTypes::BIKE->value:
                $templateId = ApplicationStorage::where('key_name', ApplicationStorageEnums::BIKE_BOOK_POLICY_TEMPLATE)->first()->value;
                break;

            case QuoteTypes::TRAVEL->value:
                $templateId = ApplicationStorage::where('key_name', ApplicationStorageEnums::TRAVEL_BOOK_POLICY_TEMPLATE)->first()->value;
                break;

            case QuoteTypes::HEALTH->value:
                $templateId = ApplicationStorage::where('key_name', ApplicationStorageEnums::HEALTH_BOOK_POLICY_TEMPLATE)->first()->value;
                break;

            case QuoteTypes::LIFE->value:
                $templateId = ApplicationStorage::where('key_name', ApplicationStorageEnums::LIFE_BOOK_POLICY_TEMPLATE)->first()->value;
                break;

            case QuoteTypes::HOME->value:
                $templateId = ApplicationStorage::where('key_name', ApplicationStorageEnums::HOME_BOOK_POLICY_TEMPLATE)->first()->value;
                break;

            case QuoteTypes::PET->value:
                $templateId = ApplicationStorage::where('key_name', ApplicationStorageEnums::PET_BOOK_POLICY_TEMPLATE)->first()->value;
                break;

            case QuoteTypes::CYCLE->value:
                $templateId = ApplicationStorage::where('key_name', ApplicationStorageEnums::CYCLE_BOOK_POLICY_TEMPLATE)->first()->value;
                break;

            case QuoteTypes::YACHT->value:
                $templateId = ApplicationStorage::where('key_name', ApplicationStorageEnums::YACHT_BOOK_POLICY_TEMPLATE)->first()->value;
                break;

            case QuoteTypes::GROUP_MEDICAL->value:
                $templateId = ApplicationStorage::where('key_name', ApplicationStorageEnums::GROUP_MEDIAL_BOOK_POLICY_TEMPLATE)->first()->value;
                break;

            case QuoteTypes::CORPLINE->value:
                $templateId = ApplicationStorage::where('key_name', ApplicationStorageEnums::CORPLINE_BOOK_POLICY_TEMPLATE)->first()->value;
                break;

           default:
                $templateId = null;
                break;
        }
        
        info('SendBookPolicyDocumentsJobData ' . json_encode($quote));

        if (! empty($templateId)) {

            // payload
            $dataArr = new \stdClass();
            $dataArr->code = $quote->code;
            // $dataArr->customerEmail = 'wasim.abbas@myalfred.com';
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
