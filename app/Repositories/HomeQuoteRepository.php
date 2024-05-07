<?php

namespace App\Repositories;

use App\Enums\QuoteTypes;
use App\Facades\Capi;
use App\Models\HomeQuote;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteStatusEnum;

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

    public function fetchGetData($forExport = false, $forTotalLeadsCount = false)
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
            ->filter(! $forExport, $forTotalLeadsCount)
            ->withFakeLeadCriteria($forTotalLeadsCount)->orderBy('created_at', 'desc');

        if ($forTotalLeadsCount) {
            return $query->count();
        }

        return ($forExport) ? $query->get() : $query->simplePaginate();

    }

    public function fetchCreateDuplicate(array $dataArr): object
    {
        return Capi::request('/api/v1-save-'.strtolower(QuoteTypes::HOME->value).'-quote', 'post', $dataArr);
    }

}
