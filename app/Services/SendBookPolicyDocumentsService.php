<?php

namespace App\Services;

use App\Enums\quoteTypeCode;
use App\Models\ApplicationStorage;
use App\Repositories\DocumentTypeRepository;
use App\Traits\GenericQueriesAllLobs;
use Exception;

class SendBookPolicyDocumentsService
{
    use GenericQueriesAllLobs;

    protected $data;

    public function __construct($payload)
    {
        $this->data = $payload;
    }

    public function execute()
    {
        // In case of Group Medical & Corpline, modelType is used & for rest of the LOBs model_type is used
        // Basically we are different to identify the template which will send to customer after policy booking
        $modelType = ucwords(! empty($this->data->modelType) ? $this->data->modelType : $this->data->model_type);

        $quote = $this->getQuoteObject($this->data->model_type, $this->data->quote_id);

        $handBookDocuments = [];

        try {
            if (in_array($modelType, [quoteTypeCode::Car, quoteTypeCode::Travel, quoteTypeCode::Health])) {
                $handBookDocuments = app(QuoteDocumentService::class)->getHandBookDocuments($quote);
            }
            $documentTypeCodes = DocumentTypeRepository::quoteDocumentsSentToCustomerCode($this->data->model_type, $quote);
            $quoteDocuments = app(QuoteDocumentService::class)->getQuoteDocuments($this->data->model_type, $this->data->quote_id, $documentTypeCodes);
        } catch (Exception $ex) {
            // Handle exception as per your application's requirement
            // For example, log the error, notify admins, etc.
            throw $ex; // Rethrow the exception or handle it accordingly
        }

        $quote->load('advisor');

        $templateId = ApplicationStorage::where('key_name', strtoupper(str_replace(' ', '_', $modelType)).'_BOOK_POLICY_TEMPLATE')->first()->value ?? null;

        if (! empty($templateId)) {
            $emailData = new \stdClass();
            $emailData->code = $quote->code;
            $emailData->customerEmail = $quote->email;
            $emailData->clientFullName = $quote->first_name.' '.$quote->last_name;
            $emailData->policy_number = $quote->policy_number;
            $emailData->renewalDueDate = date('Y-m-d', strtotime($quote['renewal_expiry_date']));
            $emailData->quoteDocuments = $quoteDocuments;
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
            } else {
                $emailData->currentInsurer = $quote->insuranceProvider->text ?? '';
            }
            $emailData->emailTemplateId = $templateId;
            $emailData->handBookDocuments = $handBookDocuments;

            // Assuming SendEmailCustomerService is a service class responsible for sending emails

            $response = app(SendEmailCustomerService::class)->sendBookPolicyDocumentsEmail($emailData, 'book-policy-document');

            return $response;
        }

        return null;
    }
}
