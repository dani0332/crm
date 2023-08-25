<?php

namespace App\Repositories;

use App\Models\HealthRatingEligibility;
use App\Models\InsuranceProvider;
use DB;

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
        return DB::table('insurance_provider_quote_type')
            ->select([
                'insurance_provider.id',
                'insurance_provider.code',
                'insurance_provider.text',
                'insurance_provider.text_lms',
                'insurance_provider_quote_type.insurance_provider_id',
                'insurance_provider_quote_type.quote_type_id',
            ])
            ->where('quote_type_id', $quoteTypeId)
            ->join('insurance_provider', 'insurance_provider.id', '=', 'insurance_provider_quote_type.insurance_provider_id')
            ->get();
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
