<?php

namespace App\Repositories;

use App\Models\HomeQuote;

class HomeQuoteRepository extends BaseRepository
{

    public function model()
    {
        return HomeQuote::class;
    }

    public function fetchGetData($forExport = false)
    {
        $query = $this->with([
            'quoteStatus',
            'homeQuoteRequestDetail.lostReason',
            'accommodationType:id,text',
            'possessionType:id,text',
            'advisor'
        ])
        ->filter()
        ->withFakeLeadCriteria()
        ->orderBy('created_at', 'desc');

        return ($forExport) ? $query->get() : $query->simplePaginate();

    }

}
