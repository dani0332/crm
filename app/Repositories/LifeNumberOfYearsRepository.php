<?php

namespace App\Repositories;

use App\Models\LifeNumberOfYears;

class MaritalStatusRepository extends BaseRepository
{
    public function model()
    {
        return LifeNumberOfYears::class;
    }
}
