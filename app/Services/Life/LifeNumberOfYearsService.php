<?php

namespace App\Services\Life;

use App\Models\LifeNumberOfYears;
use App\Services\BaseService;

class LifeNumberOfYearsService extends BaseService
{
    public function getActive()
    {
        return LifeNumberOfYears::withActive()->select('id', 'text')->get();
    }
}
