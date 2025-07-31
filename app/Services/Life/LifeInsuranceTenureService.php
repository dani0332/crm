<?php

namespace App\Services\Life;

use App\Models\LifeInsuranceTenure;
use App\Services\BaseService;

class LifeInsuranceTenureService extends BaseService
{
    public function getActive()
    {
        return LifeInsuranceTenure::withActive()->select('id', 'text', 'code')->get();
    }
}
