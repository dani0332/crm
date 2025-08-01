<?php

namespace App\Repositories;

use App\Models\QuoteStatus;
use Carbon\Carbon;

class QuoteStatusRepository extends BaseRepository
{
    public function model()
    {
        return QuoteStatus::class;
    }

    public function fetchGetList()
    {
        return $this->withActive()->orderBy('sort_order')->get();
    }

    public function fetchByQuoteTypeId($quoteTypeId)
    {
        return $this->select('quote_status.id as id', 'quote_status.text as text', 'quote_status.code as code')
            ->where(['quote_status.is_active' => true, 'quote_status_map.quote_type_id' => $quoteTypeId])
            ->leftjoin('quote_status_map', 'quote_status.id', 'quote_status_map.quote_status_id')
            ->orderBy('quote_status_map.sort_order', 'asc');
    }

    public function fetchGetQuoteStatusesByIds($quoteStatusIds)
    {
        return $this->whereIn('id', $quoteStatusIds)->orderBy('sort_order')->get();
    }
    
    public function fetchCreate($quoteTypeId, $quote, $oldQuoteStatus)
    {
        $this->create([
            'quote_type_id' => $quoteTypeId,
            'quote_request_id' => $quote->id,
            'current_quote_status_id' => $quote->quote_status_id,
            'previous_quote_status_id' => $oldQuoteStatus,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
    }
}
