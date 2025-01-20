<?php

namespace App\Services\Life;

use App\Models\QuoteStatus;
use App\Services\BaseService;

class QuoteStatusService extends BaseService
{
    public function byQuoteTypeId($quoteTypeId)
    {
        return QuoteStatus::select('quote_status.id as id', 'quote_status.text as text', 'quote_status.code as code')
            ->where(['quote_status.is_active' => true, 'quote_status_map.quote_type_id' => $quoteTypeId])
            ->leftjoin('quote_status_map', 'quote_status.id', 'quote_status_map.quote_status_id')
            ->orderBy('quote_status_map.sort_order', 'asc');
    }
}
