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
        return QuoteStatus::whereHas('quoteStatusMap', function ($q) use ($quoteTypeId) {
            $q->where('quote_type_id', '=', $quoteTypeId);
        })
            ->withActive()
            ->get();
    }
}
