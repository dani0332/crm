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
}
