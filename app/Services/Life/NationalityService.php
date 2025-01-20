<?php

namespace App\Services\Life;

use App\Models\Nationality;
use App\Services\BaseService;

class NationalityService extends BaseService
{
    public function getActive()
    {
        return Nationality::where('is_active', 1)->select('id', 'text')->get();
    }
}
