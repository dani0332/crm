<?php

namespace App\Repositories;

use App\Models\BusinessQuote;
use App\Traits\CentralTrait;

class BusinessQuoteRepository extends BaseRepository
{
    use CentralTrait;
    public function model()
    {
        return BusinessQuote::class;
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
