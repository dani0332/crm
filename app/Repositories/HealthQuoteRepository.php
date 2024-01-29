<?php

namespace App\Repositories;

use App\Enums\QuoteTypes;
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
        $query = $this->with([
            'advisor', 
            'nationality', 
            'insuranceProvider'
        ])
        ->filter(! $forExport, $forTotalLeadsCount)
        ->withFakeLeadCriteria($forTotalLeadsCount)
        ->orderBy('created_at', 'desc');

        if ($forTotalLeadsCount) {
            return $query->count();
        }

        return ($forExport) ? $query->get() : $query->Paginate();
    }

    public function fetchExport()
    {
        return $this->filter()->with(
            ['advisor', 'nationality', 'insuranceProvider'])->orderBy('created_at', 'desc');
    }

    public function fetchCreateDuplicate(array $dataArr): object
    {
        return Capi::request('/api/v1-save-'.strtolower(QuoteTypes::HEALTH->value).'-quote', 'post', $dataArr);
    }
}
