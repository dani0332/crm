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
                InsuranceProvidersEnum::AXA => $this->validationChecksForGIGCar($insuranceProvider, $quote),
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

    public function validationChecksForGIGCar($insuranceProvider, $quote)
    {
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

        return ['status' => true, 'message' => 'GIG policy issuance automation pre-checks passed'];
    }

    
}
