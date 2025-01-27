<?php

namespace App\Services\Life;

use App\Models\CurrencyType;
use App\Services\BaseService;

class CurrencyTypeService extends BaseService
{
    public function getActive()
    {
        return CurrencyType::withActive()->select('id', 'text')->get();
    }
}
