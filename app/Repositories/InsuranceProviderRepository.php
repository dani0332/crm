<?php

namespace App\Repositories;

use App\Models\InsuranceProvider;

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
        return \DB::table('insurer_quote_type_mapping')
            ->select([
                'insurance_provider.id',
                'insurance_provider.code',
                'insurance_provider.text',
                'insurance_provider.text_lms',
                'insurer_quote_type_mapping.insurance_provider_id',
                'insurer_quote_type_mapping.quote_type_id'
            ])
            ->where('quote_type_id', $quoteTypeId)
            ->join('insurance_provider', 'insurance_provider.id', '=', 'insurer_quote_type_mapping.insurance_provider_id')
            ->get();
    }
}
