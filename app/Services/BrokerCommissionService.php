<?php

namespace App\Services;

use App\Models\BrokerCommission;

class BrokerCommissionService
{
    /**
     * Get the broker commission for a given quote type and insurance provider.
     *
     * @param  int  $quoteTypeId
     * @param  int  $insuranceProviderId
     * @param  int|null  $businessTypeOfInsuranceId
     * @return BrokerCommission|null
     */
    public function getBrokerCommission($quoteTypeId, $insuranceProviderId, $planId = null)
    {
        dd($quoteTypeId, $insuranceProviderId, $planId);
        $baseQuery = BrokerCommission::where('insurance_provider_id', $insuranceProviderId)
            ->where('quote_type_id', $quoteTypeId)
            ->active();

        $brokerCommission = $baseQuery->first();
        $isCreditCardEnabled = $brokerCommission ? true : false;
        
        if ($planId) {
            $brokerCommission = (clone $baseQuery)->where('plan_id', $planId)->first();
        }
        return [$isCreditCardEnabled, $brokerCommission];
    }

    /**
     * Check if credit card payment is enabled for a given quote type, insurance provider and business type of insurance.
     *
     * @param  int  $quoteTypeId
     * @param  int  $insuranceProviderId
     * @param  int|null  $businessTypeOfInsuranceId
     * @return bool
     */
    public function isCreditCardEnabled($quoteTypeId, $insuranceProviderId, $planId=null)
    {
        [$isCreditCardEnabled] = $this->getBrokerCommission($quoteTypeId, $insuranceProviderId, $planId);

        return $isCreditCardEnabled;
    }
}
