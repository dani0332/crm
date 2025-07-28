<?php

namespace App\Services\Life;

use App\Models\Nationality;
use App\Services\BaseService;

class NationalityService extends BaseService
{
    public function getActive()
    {
        return Nationality::withActive()->select('id', 'text')->get();
    }
}
