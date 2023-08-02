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
}
