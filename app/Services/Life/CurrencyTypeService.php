<?php

namespace App\Services\Life;

use App\Models\CurrencyType;
use App\Services\BaseService;

class CurrencyTypeService extends BaseService
{
    public function getActive()
    {
        return CurrencyType::where('is_active', 1)->select('id', 'text')->get();
    }
}
