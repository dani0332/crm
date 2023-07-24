<?php

namespace App\Repositories;

use App\Models\TravelQuote;
use App\Traits\CentralTrait;

class TravelQuoteRepository extends BaseRepository
{
    use CentralTrait;
    public function model()
    {
        return TravelQuote::class;
    }

    public function fetchGetData($forExport = false)
    {
        $query = $this->with([
            'travelQuoteRequestDetail.lostReason',
            'quoteStatus',
            'travelCoverFor',
            'regionCoverFor',
            'advisor',
            'plan',
            'payments',
            'currentlyLocatedIn',
            'nationality',
            'destination',
            'paymentStatus'
        ])
        ->filter()
        ->withFakeLeadCriteria()
        ->orderBy('created_at', 'desc');

        return ($forExport) ? $query->get() : $query->simplePaginate();
    }
}
