<?php

namespace App\Repositories;

use App\Models\CarQuote;
use App\Models\PaymentStatusLog;
use App\Traits\CentralTrait;

class CarQuoteRepository extends BaseRepository
{
    use CentralTrait;
    public function model()
    {
        return CarQuote::class;
    }
}
