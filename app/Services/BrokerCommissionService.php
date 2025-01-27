<?php

namespace App\Services;

use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Models\BrokerCommission;

class BrokerCommissionService
{
     /**
     * Get the broker commission for a given quote type and insurance provider.
     *
     * @param int $quoteTypeId
     * @param int $insuranceProviderId
     * @param int|null $businessTypeOfInsuranceId
     * @return BrokerCommission|null
     */
    public function getBrokerCommission($quoteTypeId, $insuranceProviderId)
    {
        $brokerCommissionQuery = BrokerCommission::where('insurance_provider_id', $insuranceProviderId)->active();
        $brokerCommissionQuery->where('quote_type_id', $quoteTypeId);
        return $brokerCommissionQuery->first();
    }

    /**
     * Check if credit card payment is enabled for a given quote type, insurance provider and business type of insurance.
     *
     * @param int $quoteTypeId
     * @param int $insuranceProviderId
     * @param int|null $businessTypeOfInsuranceId
     * @return bool
     */
    public function isCreditCardEnabled($quoteTypeId, $insuranceProviderId)
    {
        $brokerCommission = $this->getBrokerCommission($quoteTypeId, $insuranceProviderId);
        return $brokerCommission ? $brokerCommission->credit_card_enabled : false;
    }
}
