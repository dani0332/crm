<?php

namespace App\Repositories;

use App\Enums\QuoteTypes;
use App\Facades\Capi;
use App\Models\HomeQuote;

class HomeQuoteRepository extends BaseRepository
{
    public function model()
    {
        return HomeQuote::class;
    }
    public function fetchExport()
    {
        return $this->filter()->with(
            ['advisor', 'nationality', 'insuranceProvider'])->orderBy('created_at', 'desc');
    }

    public function fetchGetData($forExport = false)
    {
        $query = $this->with([
            'quoteStatus',
            'homeQuoteRequestDetail.lostReason',
            'accommodationType:id,text',
            'possessionType:id,text',
            'advisor',
            'nationality',
            'insuranceProvider',
        ])
            ->filter(! $forExport)
            ->withFakeLeadCriteria()
            ->orderBy('created_at', 'desc');

        return ($forExport) ? $query->get() : $query->simplePaginate();

    }

    public function fetchCreateDuplicate(array $dataArr): object
    {
        return Capi::request('/api/v1-save-'.strtolower(QuoteTypes::HOME->value).'-quote', 'post', $dataArr);
    }

}
