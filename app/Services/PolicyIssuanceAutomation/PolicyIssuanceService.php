<?php

namespace App\Services\PolicyIssuanceAutomation;

use App\Factories\PolicyIssuanceFactory;

class PolicyIssuanceService
{
    public function __construct() {}

    public function getPolicyIssuanceStepsStatus($quote, $quoteType): array
    {
        $response = [
            'isPolicyAutomationEnabled' => true,
        ];
        $payment = $quote->payments()->mainLeadPayment()->first();
        $insuranceProvider = getInsuranceProvider($payment, $quoteType);
        $insuranceProviderAutomation = PolicyIssuanceFactory::make($quoteType, $insuranceProvider?->code);
        if (! $insuranceProviderAutomation || ! $insuranceProviderAutomation?->isPolicyIssuanceAutomationEnabled()) {
            $response['isPolicyAutomationEnabled'] = false;

            return $response;
        }

        $policyIssuance = $quote->policyIssuance;

        return array_merge($response, $insuranceProviderAutomation->getStepsLockingStatus($policyIssuance));

    }

}
