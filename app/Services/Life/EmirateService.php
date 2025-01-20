<?php

namespace App\Services\Life;

use App\Models\Emirate;
use App\Services\BaseService;

class EmirateService extends BaseService
{
    public function getActive()
    {
        return Emirate::where('is_active', 1)->select('id', 'text')->get();
    }
}
