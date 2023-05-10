<?php

namespace App\Repositories;

use App\Models\CarTypeInsurance;

class CarTypeInsuranceRepository extends BaseRepository
{
    public function model()
    {
        return CarTypeInsurance::class;
    }
}
