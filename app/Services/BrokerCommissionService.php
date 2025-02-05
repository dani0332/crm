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
     * @param  int|null  $businessTypeId
     * @return BrokerCommission|null
     */
    public function getBrokerCommission($quoteTypeId, $insuranceProviderId, $businessTypeId = null, $planId = null)
    {
        $baseQuery = BrokerCommission::where('insurance_provider_id', $insuranceProviderId)->active();
        // If business type of insurance is provided, then get the broker commission for that business type of insurance & ignoring the quote type.
        if ($businessTypeId) {
            $baseQuery->where('business_type_of_insurance_id', $businessTypeId);
        } else {
            $baseQuery->where('quote_type_id', $quoteTypeId);
        }

        $brokerCommission = $baseQuery->first();
        $commissionInPayments = $brokerCommission->commission_in_payments ?? false; // todo: Check it with Denber
        $isCreditCardEnabled = $brokerCommission ? true : false;

        if ($planId) {
            $brokerCommission = (clone $baseQuery)->where('plan_id', $planId)->first();
        }

        return [$isCreditCardEnabled, $brokerCommission, $commissionInPayments];
    }

    /**
     * Check if credit card payment is enabled for a given quote type, insurance provider and business type of insurance.
     *
     * @param  int  $quoteTypeId
     * @param  int  $insuranceProviderId
     * @param  int|null  $businessTypeOfInsuranceId
     * @return bool
     */
    public function isCreditCardEnabled($quoteTypeId, $insuranceProviderId, $businessTypeId = null, $planId = null)
    {
        [$isCreditCardEnabled] = $this->getBrokerCommission($quoteTypeId, $insuranceProviderId, $businessTypeId, $planId);

        return $isCreditCardEnabled;
    }
}
