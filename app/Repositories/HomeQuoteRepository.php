<?php

namespace App\Repositories;

use App\Models\HomeQuote;

class HomeQuoteRepository extends BaseRepository
{

    public function model()
    {
        return HomeQuote::class;
    }

    public function fetchGetData($isForExport = false)
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

        return ($isForExport) ? $query->get() : $query->simplePaginate();

    }

}
