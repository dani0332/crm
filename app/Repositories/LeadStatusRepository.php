<?php

namespace App\Repositories;

use App\Models\QuoteStatus;

class LeadStatusRepository extends BaseRepository
{
    public function model()
    {
        return QuoteStatus::class;
    }

    public function fetchGetList($quoteTypeId)
    {
        return QuoteStatus::select([
            'quote_status.id as id',
            'quote_status.text as text',
            'quote_status.code as code'
        ])->leftjoin('quote_status_map', 'quote_status.id', 'quote_status_map.quote_status_id')
        ->where('quote_status_map.quote_type_id', $quoteTypeId)
        ->withActive()
        ->orderBy('quote_status_map.sort_order')
        ->get();
    }
}
