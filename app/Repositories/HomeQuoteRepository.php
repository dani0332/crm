<?php

namespace App\Repositories;

use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Facades\Capi;
use App\Models\HomeQuote;
use App\Traits\GenericQueriesAllLobs;

class HomeQuoteRepository extends BaseRepository
{
    private $query;
    use GenericQueriesAllLobs;

    public function model()
    {
        return HomeQuote::class;
    }

    public function fetchExport()
    {
        return $this->filter()->with(
            ['advisor', 'nationality', 'insuranceProvider']
        )->orderBy('created_at', 'desc');
    }

    public function fetchGetData($forExport = false, $forTotalLeadsCount = false)
    {
        $this->query = $this->with([
            'quoteStatus',
            'homeQuoteRequestDetail.lostReason',
            'accommodationType:id,text',
            'possessionType:id,text',
            'advisor',
            'nationality',
            'insuranceProvider',
        ])
            ->when(\auth()->user()->hasRole(RolesEnum::HomeAdvisor), function ($query) {
                $query->where('advisor_id', \auth()->user()->id);
            })
            ->filter(! $forExport, $forTotalLeadsCount)
            ->withFakeLeadCriteria($forTotalLeadsCount);
        $this->adjustQueryByDateFilters('home_quote_request');
        $this->query->orderBy('home_quote_request.created_at', 'desc');

        if ($forTotalLeadsCount) {
            //PD Revert
            return 0;

            // return $query->count();
        }

        return ($forExport) ? $this->query->get() : $this->query->simplePaginate();
    }

    public function fetchCreateDuplicate(array $dataArr): object
    {
        return Capi::request('/api/v1-save-'.strtolower(QuoteTypes::HOME->value).'-quote', 'post', $dataArr);
    }
}
