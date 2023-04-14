<?php

namespace App\Repositories;

use App\Models\QuoteStatus;

class QuoteStatusRepository extends BaseRepository
{
    public function model()
    {
        return QuoteStatus::class;
    }

    public function fetchByQuoteTypeId($quoteTypeId)
    {
        return $this->whereHas('quoteStatusMap', function ($q) use ($quoteTypeId) {
            $q->where('quote_type_id', $quoteTypeId)->orderBy('sort_order');
        });
    }
}
