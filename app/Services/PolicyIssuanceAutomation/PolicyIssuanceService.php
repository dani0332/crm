<?php

namespace App\Services\PolicyIssuanceAutomation;

use App\Enums\InsuranceProvidersEnum;
use App\Enums\QuoteTypes;
use App\Services\PolicyIssuanceAutomation\Travel\AllianceInsuranceService;

class PolicyIssuanceService
{
    public function __construct() {}

    public function make($quoteType, $insurerCode)
    {
        return match (ucfirst($quoteType)) {
            QuoteTypes::TRAVEL->value => match ($insurerCode) {
                InsuranceProvidersEnum::ALNC => new AllianceInsuranceService,
                default => null,
            },
            default => null,
        };
    }

    public function getPolicyIssuanceStepsStatus($quote, $quoteType): array
    {
        $response = [
            'isPolicyAutomationEnabled' => true,
        ];
        $payment = $quote->payments()->mainLeadPayment()->first();
        $insuranceProvider = getInsuranceProvider($payment, $quoteType);
        $insuranceProviderAutomation = $this->make($quoteType, $insuranceProvider?->code);
        if (! $insuranceProviderAutomation || ! $insuranceProviderAutomation?->isPolicyIssuanceAutomationEnabled()) {
            $response['isPolicyAutomationEnabled'] = false;

            return $response;
        }

        return array_merge($response, $insuranceProviderAutomation->getStepsLockingStatus($quote));

    }

}
