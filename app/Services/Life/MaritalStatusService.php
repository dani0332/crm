<?php

namespace App\Services\Life;

use App\Models\MartialStatus;
use App\Services\BaseService;

class MaritalStatusService extends BaseService
{
    public function getActive()
    {
        return MartialStatus::where('is_active', 1)->select('id', 'text')->get();
    }
}
