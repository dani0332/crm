<?php

namespace App\Repositories;

use App\Models\CurrencyType;

class CurrencyTypeRepository extends BaseRepository
{
    public function model()
    {
        return CurrencyType::class;
    }
}
