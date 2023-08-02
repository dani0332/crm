<?php

namespace App\Repositories;

use App\Models\HomeQuote;
use App\Traits\CentralTrait;

class HomeQuoteRepository extends BaseRepository
{
    use CentralTrait;
    public function model()
    {
        return HomeQuote::class;
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
