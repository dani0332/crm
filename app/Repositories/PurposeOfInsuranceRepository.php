<?php

namespace App\Repositories;

use App\Models\LifePurposeOfInsurance;

class PurposeOfInsuranceRepository extends BaseRepository
{
    public function model()
    {
        return LifePurposeOfInsurance::class;
    }
}
