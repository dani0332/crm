<?php

namespace App\Services\Life;

use App\Models\PersonalQuote;
use App\Services\BaseService;
use Illuminate\Pagination\LengthAwarePaginator;

class LifeQuoteNoteService extends BaseService
{
    /**
     * Get the notes for a life quote.
     *
     * @param  PersonalQuote  $quote  The life quote.
     * @return LengthAwarePaginator
     */
    public function getNotes(PersonalQuote $quote)
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
