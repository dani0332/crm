<?php

namespace App\Services\PolicyIssuanceAutomation;

use App\Enums\AMLStatusCode;
use App\Enums\DocumentTypeCode;
use App\Enums\InsuranceProvidersEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\QuoteTypes;
use App\Models\QuoteDocument;
use App\Services\Logger\LoggerService;

class PolicyIssuancePreChecksService
{
    public function __construct() {}

    public function validationChecks($insuranceProvider, $quoteType, $quote)
    {
        LoggerService::info(self::class.' fn: '.__FUNCTION__.' - Validation checks for '.$insuranceProvider->text.' policy issuance automation');
        $allowedQuoteTypes = [QuoteTypes::CAR->value];
        $allowedInsuranceProviders = [InsuranceProvidersEnum::AXA];
        $payment = $quote->payments->first();

        // 1. Quote type should be available in allowed quote types
        if (! in_array(ucfirst($quoteType), $allowedQuoteTypes)) {
            LoggerService::info(self::class.' fn: '.__FUNCTION__.' - Quote type not supported for '.$insuranceProvider->text.' automation');

            return ['status' => false, 'message' => 'Quote type not supported for '.$insuranceProvider->text.' automation'];
        }

        // 2. Insurance provider should be available in allowed insurance providers
        if (! in_array($insuranceProvider->code, $allowedInsuranceProviders)) {
            LoggerService::info(self::class.' fn: '.__FUNCTION__.' - Insurance provider not supported for '.$insuranceProvider->text.' automation');

            return ['status' => false, 'message' => 'Insurance provider not supported for '.$insuranceProvider->text.' automation'];
        }

        $validationChecks = match (ucfirst($quoteType)) {
            QuoteTypes::CAR->value => match ($insuranceProvider->code) {
                InsuranceProvidersEnum::AXA => $this->validationChecksForGIGCar($insuranceProvider, $quoteType, $quote, $payment),
                default => null,
            },
            default => null,
        };

        if (! $validationChecks['status']) {
            LoggerService::info(self::class.' fn: '.__FUNCTION__.' - Validation checks failed for '.$insuranceProvider->text.' automation');

            return $validationChecks;
        }

        return ['status' => true, 'message' => 'Validation checks passed for '.$insuranceProvider->text.' policy issuance automation'];
    }

    public function validationChecksForGIGCar($insuranceProvider, $quoteType, $quote, $payment)
    {
        // 1. IM AML Screening should be done and Cleared
        // 2. GIG AML Screening should be done and Cleared
        // 3. KYC details should be available
        // 4. Payment already captured or Paid (Before capturing payment, we should check if the premium is matching with the authorized amount)
        // 5. All required documents should be uploaded (Driving License, Car Registration, Emirates ID/Nationality ID)

        $quoteTypeId = QuoteTypes::getIdFromValue($quoteType);
        $requiredDocs = [
            DocumentTypeCode::HPD,
            DocumentTypeCode::LPD,
            DocumentTypeCode::HOMPD,
        ];

        // 1. IM AML Screening should be done and Cleared
        if ($quote->aml_status !== AMLStatusCode::AMLScreeningCleared) {
            LoggerService::info(self::class.' fn: '.__FUNCTION__.' -  IM AML Screening status not cleared');

            return ['status' => false, 'message' => 'IM AML Screening status not cleared for '.$insuranceProvider->text.' automation'];
        }

        // 2. GIG AML Screening should be done and Cleared
        if ($quote->insurer_aml_status !== AMLStatusCode::InsurerAMLScreeningCleared) {
            LoggerService::info(self::class.' fn: '.__FUNCTION__.' -  GIG AML Screening status not cleared');

            return ['status' => false, 'message' => 'GIG AML Screening status not cleared for policy issuance automation'];
        }

        // 3. KYC details should be available
        if (false) { // TODO: Check if KYC details are available
            LoggerService::info(self::class.' fn: '.__FUNCTION__.' -  GIG KYC Screening status not cleared');

            return ['status' => false, 'message' => 'GIG KYC Screening status not cleared for policy issuance automation'];
        }

        // 4. Payment already captured or Paid (Before capturing payment, we should check if the premium is matching with the authorized amount)
        if (! in_array($payment->status, [PaymentStatusEnum::CAPTURED, PaymentStatusEnum::PAID])) {
            LoggerService::info(self::class.' fn: '.__FUNCTION__.' -  GIG Payment not captured or paid');

            return ['status' => false, 'message' => 'GIG Payment not captured or paid for policy issuance automation'];
        }

        // 5. All required documents should be uploaded
        $policyAutomationDocumentsCheck = $this->policyAutomationDocumentsCheck($quoteTypeId, $quote, $insuranceProvider->code, $requiredDocs);
        if (! $policyAutomationDocumentsCheck['status']) {
            LoggerService::info(self::class.' fn: '.__FUNCTION__.' -  required documents not uploaded for policy issuance automation');

            return $policyAutomationDocumentsCheck;
        }

        return ['status' => true, 'message' => 'GIG policy issuance automation pre-checks passed'];
    }

    public function policyAutomationDocumentsCheck($quoteTypeId, $quote, $insuranceProviderCode, $requiredDocs)
    {
        $quoteDocuments = QuoteDocument::where('quote_documentable_type', get_class($quote))
            ->where('quote_documentable_id', $quote->id)
            ->whereHas('documentType', function ($query) use ($quoteTypeId) {
                $query->where([
                    'category' => DocumentTypeCode::QUOTE,
                    'quote_type_id' => $quoteTypeId,
                    'is_active' => 1,
                ]);
            })
            ->pluck('document_type_code')
            ->toArray();

        $isRequiredDocsUploaded = count(array_intersect($requiredDocs[$insuranceProviderCode], $quoteDocuments)) === count($requiredDocs[$insuranceProviderCode]);
        if (! $isRequiredDocsUploaded) {
            return ['status' => false, 'message' => 'GIG required documents not uploaded for policy issuance automation'];
        }

        return ['status' => true, 'message' => 'GIG required documents uploaded for policy issuance automation'];
    }
}
