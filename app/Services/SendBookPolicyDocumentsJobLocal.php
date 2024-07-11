<?php

namespace App\Services;

use App\Enums\quoteTypeCode;
use App\Models\ApplicationStorage;
use App\Repositories\DocumentTypeRepository;
use App\Traits\GenericQueriesAllLobs;
use Exception;

use function Laravel\Prompts\error;

// This class is designed for testing and developing the functionality of sending and booking policy emails.
class SendBookPolicyDocumentsJobLocal extends BaseService
{
    use GenericQueriesAllLobs;

    private $data = null;

    public function __construct($payload)
    {
        $this->data = $payload;
    }

    public function execute()
    {
        info('job: SendBookPolicyDocumentsJob started with payload: '.json_encode($this->data));

        // In case of Group Medical & Corpline, modelType is used & for rest of the LOBs model_type is used
        // Basically we are different to identify the template which will send to customer after policy booking
        $modelType = ucwords(! empty($this->data->modelType) ? $this->data->modelType : $this->data->model_type);

        $quote = $this->getQuoteObject($this->data->model_type, $this->data->quote_id);
        $handBookDocuments = [];

        try {
            // This will give handbook document from relevant policy wording table only for mentioned LOB's
            if (in_array($modelType, [quoteTypeCode::Car, quoteTypeCode::Travel, quoteTypeCode::Health])) {
                $handBookDocuments = app(QuoteDocumentService::class)->getHandBookDocuments($quote);
                info('Handbook documents retrieved: '.json_encode($handBookDocuments));
            }
            // First Retrieve document types marked for sending to the customer, then fetch the corresponding uploaded documents
            $documentTypeCodes = DocumentTypeRepository::quoteDocumentsSentToCustomerCode($this->data->model_type, $quote);
            $docs = app(QuoteDocumentService::class)->getQuoteDocuments($this->data->model_type, $this->data->quote_id, $documentTypeCodes);
            info('Quote documents which need to send to customer through email retrieved: '.json_encode($docs));
        } catch (Exception $ex) {
            error('Send BookPolicy Documents Job Error '.$ex->getMessage());
            $docs = [];
        }

        $quote->load('advisor');

        $templateId = ApplicationStorage::where('key_name', strtoupper(str_replace(' ', '_', $modelType)).'_BOOK_POLICY_TEMPLATE')->first()->value ?? null;
        // Prepare the data to be sent to Brevo for email template dispatch
        if (! empty($templateId)) {
            $roadsideAssistance = '';
            $emailData = new \stdClass();
            $emailData->code = $quote->code;
            $emailData->customerEmail = $quote->email;
            $emailData->clientFullName = $quote->first_name.' '.$quote->last_name;
            $emailData->policy_number = $quote->policy_number;
            $emailData->renewalDueDate = date('Y-m-d', strtotime($quote['policy_expiry_date']));
            $emailData->quoteDocuments = $docs;
            $emailData->advisorName = '';
            $emailData->advisorEmail = '';
            $emailData->advisorMobileNo = '';
            if (! empty($quote->advisor)) {
                $emailData->advisorName = $quote->advisor->name;
                $emailData->advisorEmail = $quote->advisor->email;
                $advisorMobileNo = formatMobileNo($quote->advisor->mobile_no);
                $emailData->advisorMobileNo = str_replace('+', '', $advisorMobileNo);
            }
            if (in_array(ucfirst($this->data->model_type), [quoteTypeCode::Car, quoteTypeCode::Health, quoteTypeCode::Travel])) {
                $emailData->currentInsurer = $quote->plan->insuranceProvider->text ?? '';
                $roadsideAssistance = $quote->plan->insuranceProvider->roadside_phone_number ?? '';
            } else {
                $emailData->currentInsurer = $quote->insuranceProvider->text ?? '';
                $roadsideAssistance = $quote->insuranceProvider->roadside_phone_number ?? '';
            }
            $emailData->emailTemplateId = $templateId;
            $emailData->handBookDocuments = $handBookDocuments;
            $emailData->roadsideAssistance = $roadsideAssistance;
            info('Send Book Policy Documents Job Email Data '.json_encode($emailData));
            $response = app(SendEmailCustomerService::class)->sendBookPolicyDocumentsEmail($emailData, 'book-policy-document');
            info('Send Book Policy Documents Job Response '.json_encode($response));
        }
    }
}
