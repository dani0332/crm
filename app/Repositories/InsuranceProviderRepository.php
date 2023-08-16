<?php

namespace App\Repositories;

use App\Models\HealthRatingEligibility;
use App\Models\InsuranceProvider;
use App\Models\QuoteType;

class InsuranceProviderRepository extends BaseRepository
{
    public function model()
    {
        return InsuranceProvider::class;
    }

    public function fetchGetList()
    {
        return $this->withActive()->orderBy('sort_order')->get();
    }
    public function fetchByQuoteTypeMapping($quoteTypeId)
    {
        $quote = QuoteType::find($quoteTypeId);

        if (! empty($quote) && count($quote->insurerProviders)) {
            return $quote->insurerProviders;
        }
    }

    public function fetchNetworksByInsuranceProviders($request)
    {
        $networks = [];
        $insuranceProvidersIds = explode(',', $request['insuranceProviderId']);
        if (! empty($insuranceProvidersIds)) {
            $data = HealthRatingEligibility::whereIn('insurance_provider_id', $insuranceProvidersIds)
                ->get();
            $networks = $data->map(function ($item) {
                return [
                    'value' => $item->text,
                    'label' => $item->text,
                ];
            })->values();
        }

        return $networks;
    }
}
