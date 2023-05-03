<?php

namespace App\Repositories;

use App\Models\LifeNumberOfYears;

class LifeNumberOfYearsRepository extends BaseRepository
{
    public function model()
    {
        return LifeNumberOfYears::class;
    }
}
