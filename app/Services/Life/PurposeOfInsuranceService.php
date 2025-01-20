<?php

namespace App\Services\Life;

use App\Models\LifePurposeOfInsurance;
use App\Services\BaseService;

class PurposeOfInsuranceService extends BaseService
{
    public function getActive()
    {
        return LifePurposeOfInsurance::where('is_active', 1)->select('id', 'text')->get();
    }
}
