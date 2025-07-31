<?php

namespace App\Services\Life;

use App\Services\BaseService;
use Illuminate\Support\Facades\DB;

class InsuranceProviderService extends BaseService
{
    public function byQuoteTypeMapping($quoteTypeId)
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
            ->where('insurance_provider.is_active', 1)
            ->where('insurance_provider.is_deleted', 0)
            ->join('insurance_provider', 'insurance_provider.id', '=', 'insurance_provider_quote_type.insurance_provider_id')
            ->orderBy('text')
            ->get();
    }
}
