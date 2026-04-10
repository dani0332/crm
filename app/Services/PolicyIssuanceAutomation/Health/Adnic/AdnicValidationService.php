<?php

declare(strict_types=1);

namespace App\Services\PolicyIssuanceAutomation\Health\Adnic;

use App\Enums\DocumentTypeCode;
use App\Services\Logger\LoggerService;

class AdnicValidationService
{
    /**
     * Validate required data for policy issuance
     *
     * @param  mixed  $quote
     */
    public function validateRequiredData($quote): array
    {
        $customer = $quote->customer;
        $insurerGenerateQuoteRequestResponse = $quote->insurerGenerateQuoteRequestResponse;
        $insurerGenerateQuoteResponse = $insurerGenerateQuoteRequestResponse ? json_decode($insurerGenerateQuoteRequestResponse->response) : null;
        $insurerQuoteNumber = $insurerGenerateQuoteResponse?->QuoteInfo?->QuotationNo;

        $missing = [];

        if (! $quote->healthUmafResponse) {
            $missing[] = 'Health UMAF Response';
        }

        if ($quote->payments->isEmpty()) {
            $missing[] = 'payments';
        }

        if (! $insurerQuoteNumber) {
            $missing[] = 'Insurer Quote Number';
        }
        // Only check emirates id if customer exists (not null)
        if ($customer === null) {
            $missing[] = 'customer';
        }

        if (! empty($missing)) {
            LoggerService::error('Missing required data', extra: [
                'has_payments' => (bool) $quote->payments,
                'has_insurer_quote_number' => (bool) $insurerQuoteNumber,
                'has_customer' => (bool) $customer,
            ]);

            $missingDesc = implode(', ', $missing);

            return [
                'status' => false,
                'error' => "Missing required data: $missingDesc",
                'message' => "Missing required data: $missingDesc",
            ];
        }

        return ['status' => true];
    }

    public function validateUploadDocuments($quote, $quoteDocuments, $insuredInfoDetails): array
    {
        $result = ['status' => true];

        if (! $quoteDocuments || $quoteDocuments->isEmpty()) {
            $result = [
                'status' => false,
                'error' => 'Required documents not uploaded',
                'message' => 'Required documents not uploaded',
            ];
        } elseif (! $insuredInfoDetails || empty($insuredInfoDetails)) {
            $result = [
                'status' => false,
                'error' => 'Insured info details not found',
                'message' => 'Insured info details not found',
            ];
        } else {
            // Define mandatory document types based on ADNIC requirements
            $mandatoryDocuments = [
                DocumentTypeCode::HEA_MEDICAL_APPLICATION_FORM, // Medical application form
                DocumentTypeCode::HEA_CUSTOMER_DUE_DILIGENCE, // Others (Customer Due Diligence)
                DocumentTypeCode::HEA_EMIRATE_ID_COPY, // Emirates ID
                DocumentTypeCode::HEA_PAS, // Passport
                DocumentTypeCode::HEA_VISA, // Visa
            ];

            // Group uploaded documents by type code
            $uploadedDocumentCodes = $quoteDocuments->pluck('document_type_code')->unique()->toArray();

            // Check for missing mandatory documents
            $missingDocuments = [];
            foreach ($mandatoryDocuments as $docCode) {
                if (! in_array($docCode, $uploadedDocumentCodes, true)) {
                    $missingDocuments[] = $docCode;
                }
            }

            if (! empty($missingDocuments)) {
                $missingList = implode(', ', $missingDocuments);
                LoggerService::error('Missing mandatory documents for ADNIC policy issuance', extra: [
                    'quote_uuid' => $quote->uuid ?? null,
                    'missing_documents' => $missingDocuments,
                    'uploaded_documents' => $uploadedDocumentCodes,
                ]);

                $result = [
                    'status' => false,
                    'error' => "Missing required documents: {$missingList}",
                    'message' => "Missing required documents: {$missingList}",
                ];
            }
        }

        return $result;
    }
}
