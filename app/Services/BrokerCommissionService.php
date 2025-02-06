<?php

namespace App\Services;

use App\Enums\QuoteTypeId;
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
    public function fetchBrokerCommission($quoteTypeId, $insuranceProviderId, $businessTypeId = null, $planId = null)
    {
        // Retrieve the insurance provider entity
        $insuranceProvider = app(InsuranceProviderService::class)->getEntity($insuranceProviderId);
    
        // Check if the insurance provider exists and has a payment gateway ID
        if (!$insuranceProvider || $insuranceProvider->payment_gateway_id == null) {
            // Return default values if the insurance provider is not valid
            return [false, null, false];
        }
    
        $ecommerceLinesOfBusiness = [QuoteTypeId::Car, QuoteTypeId::Travel, QuoteTypeId::Home, QuoteTypeId::Health, QuoteTypeId::Bike];
        $query = BrokerCommission::where('insurance_provider_id', $insuranceProviderId)->active();
    
        if ($businessTypeId) {
            $query->where('business_type_of_insurance_id', $businessTypeId);
        } else {
            $query->where('quote_type_id', $quoteTypeId);
            if (in_array($quoteTypeId, $ecommerceLinesOfBusiness) && $planId) {
                $query->where('plan_id', $planId);
            }
        }
    
        $brokerCommission = $query->first();
        $commissionInPayments = $brokerCommission->commission_in_payments ?? false;
        $isCreditCardEnabled = $brokerCommission ? true : false;
    
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
        [$isCreditCardEnabled] = $this->fetchBrokerCommission($quoteTypeId, $insuranceProviderId, $businessTypeId, $planId);

        return $isCreditCardEnabled;
    }
}
