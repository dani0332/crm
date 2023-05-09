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
}
