<?php

namespace App\Repositories;

use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Facades\Capi;
use App\Models\HealthQuote;
use App\Traits\CentralTrait;

class HealthQuoteRepository extends BaseRepository
{
    use CentralTrait;

    public function model()
    {
        return HealthQuote::class;
    }

    public function fetchGetData($forExport = false, $forTotalLeadsCount = false)
    {
        $request = request();

        $sort_by = isset($request->sortBy) && $request->sortBy != '' ? $request->sortBy : 'created_at';
        $sort_type = isset($request->sortType) && $request->sortType != '' ? $request->sortType : 'desc';

        $query = $this->with([
            'advisor',
            'nationality',
            'insuranceProvider',
        ])
            ->when(\auth()->user()->hasRole(RolesEnum::HealthAdvisor), function ($query) {
                $query->where('advisor_id', \auth()->user()->id);
            })
            ->filter(! $forExport, $forTotalLeadsCount)
            ->withFakeLeadCriteria($forTotalLeadsCount)->orderBy($sort_by, $sort_type);

        if ($forTotalLeadsCount) {
            // PD Revert
            // return $query->count();
            return 0;
        }

        return ($forExport) ? $query->get() : $query->Paginate();
    }

    public function fetchExport()
    {
        return $this->filter()->with(
            ['advisor', 'nationality', 'insuranceProvider']
        )->orderBy('created_at', 'desc');
    }

    public function fetchCreateDuplicate(array $dataArr): object
    {
        return Capi::request('/api/v1-save-'.strtolower(QuoteTypes::HEALTH->value).'-quote', 'post', $dataArr);
    }
}
