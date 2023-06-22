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
        return $this->whereHas('quoteTypes', function ($quoteType) use ($quoteTypeId){
            $quoteType->where('quote_type_id', $quoteTypeId);
        })->withActive()->orderBy('sort_order')->get();
    }
}
