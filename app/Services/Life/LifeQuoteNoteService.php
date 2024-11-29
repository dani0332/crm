<?php

namespace App\Services\Life;

use App\Models\LifeQuote;
use App\Services\BaseService;

class LifeQuoteNoteService extends BaseService
{
    /**
     * Get the notes for a life quote.
     *
     * @param LifeQuote $quote The life quote.
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getNotes(LifeQuote $quote)
    {
        return $quote->notes()
            ->with([
                'createdBy:id,name',
                'quoteStatus:id,text',
                'documents:doc_name,doc_url,original_name',
            ])
            ->orderBy('updated_at', 'desc')
            ->simplePaginate(5);
    }
}