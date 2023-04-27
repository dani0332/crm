<?php

namespace App\Repositories;

use App\Models\LifeInsuranceTenure;

class LifeInsuranceTenureRepository extends BaseRepository
{
    public function model()
    {
        return LifeInsuranceTenure::class;
    }
}
