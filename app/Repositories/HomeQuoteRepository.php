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
}
