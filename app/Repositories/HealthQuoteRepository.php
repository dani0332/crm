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
    public function fetchGetData()
    {
        return $this->filter()->with(
            ['advisor', 'nationality', 'insuranceProvider'])->orderBy('created_at', 'desc')->Paginate();
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
