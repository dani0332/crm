<?php

namespace App\Services;

use App\Enums\InsurerProviderEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\QuoteTypeId;
use App\Models\BrokerCommission;
use Illuminate\Support\Facades\Log;

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
    public function fetchBrokerCommission($quoteTypeId, $insuranceProviderId, $businessTypeId = null, $planId = null, $quote = null)
    {
        // Retrieve the insurance provider entity
        $insuranceProvider = app(InsuranceProviderService::class)->getEntity($insuranceProviderId);

        // Check if the insurance provider exists and has a payment gateway ID
        if (! $insuranceProvider || $insuranceProvider->payment_gateway_id == null) {
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
        // todo: confirm from denber
        // $commissionInPayments = $brokerCommission->commission_in_payments ?? false;

        $isCreditCardEnabled = $brokerCommission ? true : false;

        $insurersWithoutCCRenewal = [
            InsurerProviderEnum::SUKOON_OMAN_INSURANCE,
        ];

        try {
            if ($quote
                && $quoteTypeId === QuoteTypeId::Home
                && $quote->source === LeadSourceEnum::RENEWAL_UPLOAD
                && in_array($insuranceProvider->code, $insurersWithoutCCRenewal)
            ) {
                $isCreditCardEnabled = false;
            }
        } catch (\Exception $e) {
            Log::error('Quote code '.$quote->code.'Error in BrokerCommissionService::fetchBrokerCommission: '.$e->getMessage());
        }

        return [$isCreditCardEnabled, $brokerCommission, false];
    }

    /**
     * Check if credit card payment is enabled for a given quote type, insurance provider and business type of insurance.
     *
     * @param  int  $quoteTypeId
     * @param  int  $insuranceProviderId
     * @param  int|null  $businessTypeOfInsuranceId
     * @return bool
     */
    public function isCreditCardEnabled($quoteTypeId, $insuranceProviderId, $businessTypeId = null, $planId = null, $quote = null)
    {
        [$isCreditCardEnabled] = $this->fetchBrokerCommission($quoteTypeId, $insuranceProviderId, $businessTypeId, $planId, $quote);

        return $isCreditCardEnabled;
    }

    public function getBrokerCommission($data)
    {
        [$insurerId, $quoteTypeId, $businessTypeOfInsurance, $planId] = $data;

        // Define query conditions in priority order
        $whereClauses = [
            ['insurance_provider_id', $insurerId],
        ];

        // Add optional conditions only if they exist
        if ($quoteTypeId) {
            $whereClauses[] = ['quote_type_id', $quoteTypeId];
        }

        if ($businessTypeOfInsurance) {
            $whereClauses[] = ['business_type_of_insurance_id', $businessTypeOfInsurance];
        }

        if ($planId) {
            $whereClauses[] = ['plan_id', $planId];
        }

        // Start with most specific query
        $query = BrokerCommission::query();

        // Try progressively less specific queries until we find a match
        while (! empty($whereClauses)) {
            $brokerCommission = $query->where($whereClauses)->first();

            if ($brokerCommission) {
                return $brokerCommission;
            }

            // Remove the last condition and try again with a less specific query
            array_pop($whereClauses);
        }

        // If we've tried all combinations and found nothing, return null
        return null;
    }

    public function isAutomaticCommissionTransferEnabledForInsurer($data)
    {
        $brokerCommission = $this->getBrokerCommission($data);

        return $brokerCommission?->automatic_commission_transfer;
    }
}
