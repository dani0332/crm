<?php

namespace App\Repositories;

use App\Models\HealthQuote;
use App\Traits\CentralTrait;

class HealthQuoteRepository extends BaseRepository
{
    use CentralTrait;
    public function model()
    {
        return HealthQuote::class;
    }
}
