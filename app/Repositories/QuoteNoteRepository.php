<?php

namespace App\Repositories;

use App\Models\QuoteNote;
use App\Traits\GenericQueriesAllLobs;

class QuoteNoteRepository extends BaseRepository
{
    use GenericQueriesAllLobs;
    public function model()
    {
        return QuoteNote::class;
    }

    public function fetchGetBy($quote_request_id, $quoteType)
    {
        $quote = $this->getQuoteObject($quoteType, $quote_request_id);
        
        return $quote ? $quote->notes()->with('createdBy:id,name', 'quoteStatus:id,text')->simplePaginate(10) : [];
        
    }
}
