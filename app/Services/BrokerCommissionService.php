<?php

namespace App\Services;

use App\Enums\CarRegistrationType;
use App\Enums\InsurerProviderEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\PaymentGatewayIdEnum;
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
            return [false, null, false, false];
        }

        if ($quoteTypeId == QuoteTypeId::Car && $quote && strtolower($quote->registration_type) == strtolower(CarRegistrationType::COMPANY)) {
            $quoteTypeId = QuoteTypeId::CompanyCar;
        }

        $ecommerceLinesOfBusiness = [QuoteTypeId::Car, QuoteTypeId::Travel, QuoteTypeId::Home, QuoteTypeId::Health, QuoteTypeId::Bike, QuoteTypeId::CompanyCar];
        $query = BrokerCommission::where('insurance_provider_id', $insuranceProviderId)->active();

        $planBasedQuery = null;
        $brokerCommission = null;
        if ($businessTypeId) {
            $query->where('business_type_of_insurance_id', $businessTypeId);
        } else {
            $query->where('quote_type_id', $quoteTypeId);
            if (in_array($quoteTypeId, $ecommerceLinesOfBusiness) && $planId) {
                $planBasedQuery = $query->clone();
                $planBasedQuery->where('plan_id', $planId);
            }
        }

        if ($planBasedQuery && $planBasedQuery->exists()) {
            $brokerCommission = $planBasedQuery->first();
        } elseif (! $businessTypeId) {
            $query->whereNull('plan_id');
        }

        $brokerCommission = $brokerCommission ? $brokerCommission : $query->first();
        // todo: confirm from denber
        // $commissionInPayments = $brokerCommission->commission_in_payments ?? false;

        $isCreditCardEnabled = $brokerCommission
                                ? (! $brokerCommission->enable_payment_link && $insuranceProvider->payment_gateway_id != PaymentGatewayIdEnum::PAYMENT_GATEWAY_PL)
                                : ($insuranceProvider->payment_gateway_id != PaymentGatewayIdEnum::PAYMENT_GATEWAY_PL);
        
        $isPaymentLinkEnabled = $brokerCommission
                                ? $brokerCommission->enable_payment_link
                                : false;

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

        return [$isCreditCardEnabled, $brokerCommission, false, $isPaymentLinkEnabled];
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

        // Try with all 4 parameters
        $brokerCommission = BrokerCommission::select('automatic_commission_transfer')
            ->where('insurance_provider_id', $insurerId)
            ->when($quoteTypeId, function ($q) use ($quoteTypeId) {
                $q->where('quote_type_id', $quoteTypeId);
            })
            ->when($businessTypeOfInsurance, function ($q) use ($businessTypeOfInsurance) {
                $q->where('business_type_of_insurance_id', $businessTypeOfInsurance);
            })
            ->when($planId, function ($q) use ($planId) {
                $q->where('plan_id', $planId);
            })
            ->first();

        if ($brokerCommission) {
            return $brokerCommission;
        }

        // Try with 3 parameters (without planId)
        if ($planId) {
            $brokerCommission = BrokerCommission::select('automatic_commission_transfer')
                ->where('insurance_provider_id', $insurerId)
                ->when($quoteTypeId, function ($q) use ($quoteTypeId) {
                    $q->where('quote_type_id', $quoteTypeId);
                })
                ->when($businessTypeOfInsurance, function ($q) use ($businessTypeOfInsurance) {
                    $q->where('business_type_of_insurance_id', $businessTypeOfInsurance);
                })
                ->whereNull('plan_id')
                ->first();

            if ($brokerCommission) {
                return $brokerCommission;
            }
        }

        // Try with 2 parameters (without businessTypeOfInsurance)
        if ($businessTypeOfInsurance) {
            $brokerCommission = BrokerCommission::select('automatic_commission_transfer')
                ->where('insurance_provider_id', $insurerId)
                ->when($quoteTypeId, function ($q) use ($quoteTypeId) {
                    $q->where('quote_type_id', $quoteTypeId);
                })
                ->whereNull('business_type_of_insurance_id')
                ->whereNull('plan_id')
                ->first();

            if ($brokerCommission) {
                return $brokerCommission;
            }
        }

        // Finally try with just insurer and quote type
        return BrokerCommission::select('automatic_commission_transfer')
            ->where('insurance_provider_id', $insurerId)
            ->when($quoteTypeId, function ($q) use ($quoteTypeId) {
                $q->where('quote_type_id', $quoteTypeId);
            })
            ->whereNull('business_type_of_insurance_id')
            ->whereNull('plan_id')
            ->first();
    }

    public function isAutomaticCommissionTransferEnabledForInsurer($data)
    {
        $brokerCommission = $this->getBrokerCommission($data);
        info(self::class.' fn:'.__FUNCTION__.' Broker Commission Id : '.$brokerCommission?->id.', Automatic Commission Transfer : '.$brokerCommission?->automatic_commission_transfer, ['data' => json_encode($data)]);

        return $brokerCommission?->automatic_commission_transfer;
    }
}
