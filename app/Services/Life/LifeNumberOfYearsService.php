<?php

namespace App\Services\Life;

use App\Models\LifeNumberOfYears;
use App\Services\BaseService;

class LifeNumberOfYearsService extends BaseService
{
    public function getActive()
    {
        return LifeNumberOfYears::where('is_active', 1)->select('id', 'text')->get();
    }
}
